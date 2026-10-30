<?php
/**
 * Settings screen for choosing which post types use a .html suffix.
 *
 * @package HFA
 */

namespace BUDDY_SEO_URL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin settings page.
 */
class Html_For_All_AdminSettings {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Hooks the settings screen.
	 */
	private function __construct() {
		add_filter( 'plugin_action_links_' . HFA_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_settings_save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Returns the shared instance.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Loads the settings stylesheet on this screen only.
	 *
	 * @param mixed $hook Current admin page hook.
	 */
	public function enqueue_styles( $hook ): void {
		if ( 'toplevel_page_hfa-settings' !== $hook ) {
			return;
		}

		$style_path = plugin_dir_path( __FILE__ ) . 'style.css';
		$version    = HFA_VERSION;
		if ( is_readable( $style_path ) ) {
			$version .= '.' . filemtime( $style_path );
		}

		wp_enqueue_style(
			'hfa-setting-style',
			plugins_url( 'style.css', __FILE__ ),
			array( 'dashicons' ),
			$version
		);

		$script_path = plugin_dir_path( __FILE__ ) . 'script.js';
		$script_ver  = HFA_VERSION;
		if ( is_readable( $script_path ) ) {
			$script_ver .= '.' . filemtime( $script_path );
		}

		wp_enqueue_script(
			'hfa-setting-script',
			plugins_url( 'script.js', __FILE__ ),
			array(),
			$script_ver,
			true
		);
	}

	/**
	 * Adds a Settings link on the Plugins screen.
	 *
	 * @param mixed $links Plugin action links.
	 * @return array<int, string>
	 */
	public function add_settings_link( $links ) {
		if ( ! is_array( $links ) ) {
			$links = array();
		}

		$links[] = '<a href="' . esc_url( $this->get_settings_page_url() ) . '">' . esc_html__( 'Settings', 'html-for-all' ) . '</a>';

		return $links;
	}

	/**
	 * Registers a top-level admin menu.
	 */
	public function register_menu(): void {
		add_menu_page(
			__( '.html For All', 'html-for-all' ),
			__( '.html For All', 'html-for-all' ),
			'manage_options',
			'hfa-settings',
			array( $this, 'render_page' ),
			'dashicons-admin-links',
			81
		);

		add_submenu_page(
			'hfa-settings',
			__( 'Post types', 'html-for-all' ),
			__( 'Post types', 'html-for-all' ),
			'manage_options',
			'hfa-settings',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Stores the form submission before the page is rendered.
	 */
	public function handle_settings_save(): void {
		if ( ! isset( $_POST['hfa_save_post_types'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to manage these settings.', 'html-for-all' ),
				'',
				array( 'response' => 403 )
			);
		}

		check_admin_referer( 'hfa_save_settings' );

		$raw = array();
		if ( isset( $_POST['hfa_post_types'] ) ) {
			$raw = wp_unslash( $_POST['hfa_post_types'] );
		}

		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		Html_For_All::get_instance()->update_selected_post_types( $raw );

		wp_safe_redirect( add_query_arg( 'hfa-updated', '1', $this->get_settings_page_url() ) );
		exit;
	}

	/**
	 * Renders the settings page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$updated = '';
		if ( isset( $_GET['hfa-updated'] ) ) {
			$updated = sanitize_text_field( wp_unslash( $_GET['hfa-updated'] ) );
		}

		$post_types = Html_For_All::get_instance()->get_supported_post_types();
		$selected   = get_option( 'hfa_selected_post_type', array() );
		if ( ! is_array( $selected ) ) {
			$selected = array();
		}

		$selected = array_values(
			array_filter(
				array_map(
					static function ( $post_type ) {
						return is_string( $post_type ) ? sanitize_key( $post_type ) : '';
					},
					$selected
				)
			)
		);
		$selected_count = count( array_intersect( $post_types, $selected ) );
		?>
		<div class="wrap hfa-wrap">
			<hr class="wp-header-end" />
			<?php if ( '1' === $updated ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html__( 'Settings saved.', 'html-for-all' ); ?></p>
				</div>
			<?php endif; ?>
			<?php if ( ! get_option( 'permalink_structure' ) ) : ?>
				<div class="notice notice-warning">
					<p>
						<?php echo esc_html__( 'Pretty permalinks are turned off. Choose a permalink structure other than Plain before .html suffixes can be applied.', 'html-for-all' ); ?>
						<a href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>"><?php echo esc_html__( 'Open Permalink Settings', 'html-for-all' ); ?></a>
					</p>
				</div>
			<?php endif; ?>
			<div class="hfa-layout">
			<div class="hfa-shell">
				<header class="hfa-hero">
					<div class="hfa-hero__copy">
						<p class="hfa-kicker"><?php echo esc_html__( 'Permalink suffix', 'html-for-all' ); ?></p>
						<h1><?php echo esc_html__( '.html For All', 'html-for-all' ); ?></h1>
						<p><?php echo esc_html__( 'Choose which content keeps a .html ending on its permalink.', 'html-for-all' ); ?></p>
					</div>
					<div class="hfa-preview" aria-hidden="true">
						<span class="hfa-preview__dot"></span>
						<span class="hfa-preview__dot"></span>
						<span class="hfa-preview__dot"></span>
						<span class="hfa-preview__url">
							<span>yoursite.com/sample-page</span><strong>.html</strong>
						</span>
					</div>
				</header>
				<form class="hfa-panel" method="post" action="<?php echo esc_url( $this->get_settings_page_url() ); ?>">
					<?php wp_nonce_field( 'hfa_save_settings' ); ?>
					<div class="hfa-panel__intro">
						<div>
							<h2><?php echo esc_html__( 'Post types', 'html-for-all' ); ?></h2>
							<p><?php echo esc_html__( 'In terms of SEO and ranking, there is little benefit to keeping the .html extension present in your URLs.', 'html-for-all' ); ?></p>
						</div>
						<p
							class="hfa-pill"
							data-hfa-count
							data-one="<?php echo esc_attr__( '%s post type selected', 'html-for-all' ); ?>"
							data-many="<?php echo esc_attr__( '%s post types selected', 'html-for-all' ); ?>"
						>
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: number of selected post types. */
									_n( '%s post type selected', '%s post types selected', $selected_count, 'html-for-all' ),
									number_format_i18n( $selected_count )
								)
							);
							?>
						</p>
					</div>
					<?php if ( array() === $post_types ) : ?>
						<p class="hfa-empty"><?php echo esc_html__( 'No public post types with rewrites were found.', 'html-for-all' ); ?></p>
					<?php else : ?>
						<div class="hfa-grid">
							<?php foreach ( $post_types as $post_type ) : ?>
								<?php
								$label = $this->post_type_label( $post_type );
								$path   = $this->example_path( $post_type );
								$base   = (string) preg_replace( '/\.html$/', '', $path );
								?>
								<label class="hfa-card">
									<input type="checkbox" name="hfa_post_types[]" value="<?php echo esc_attr( $post_type ); ?>" <?php checked( in_array( $post_type, $selected, true ) ); ?> />
									<span class="hfa-card__icon dashicons <?php echo esc_attr( $this->post_type_icon( $post_type ) ); ?>" aria-hidden="true"></span>
									<span class="hfa-card__body">
										<span class="hfa-card__name"><?php echo esc_html( $label ); ?></span>
										<span class="hfa-card__path"><?php echo esc_html( $base ); ?><span class="hfa-ext">.html</span></span>
									</span>
									<span class="hfa-check" aria-hidden="true"></span>
								</label>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<div class="hfa-actions">
						<p class="hfa-hint"><?php echo esc_html__( 'Changes apply after you save. Rewrite rules refresh automatically.', 'html-for-all' ); ?></p>
						<button type="submit" name="hfa_save_post_types" class="button button-primary hfa-save" value="1"><?php echo esc_html__( 'Save changes', 'html-for-all' ); ?></button>
					</div>
				</form>
			</div>
			<?php $this->render_sidebar(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the BuddyDevelopers services sidebar.
	 */
	private function render_sidebar(): void {
		$services = array(
			array(
				'icon'  => 'dashicons-location-alt',
				'title' => __( 'Directory websites', 'html-for-all' ),
				'text'  => __( 'Listing and business directories built on GeoDirectory.', 'html-for-all' ),
			),
			array(
				'icon'  => 'dashicons-groups',
				'title' => __( 'Community websites', 'html-for-all' ),
				'text'  => __( 'Membership and social sites with BuddyPress and BuddyBoss.', 'html-for-all' ),
			),
			array(
				'icon'  => 'dashicons-cart',
				'title' => __( 'eCommerce stores', 'html-for-all' ),
				'text'  => __( 'WooCommerce stores that are fast and easy to manage.', 'html-for-all' ),
			),
		);
		?>
		<aside class="hfa-sidebar">
			<div class="hfa-side-card hfa-side-card--brand">
				<p class="hfa-kicker"><?php echo esc_html__( 'Built by BuddyDevelopers', 'html-for-all' ); ?></p>
				<h2><?php echo esc_html__( 'Need help with your WordPress site?', 'html-for-all' ); ?></h2>
				<p><?php echo esc_html__( 'We are a WordPress agency that plans, builds and maintains websites.', 'html-for-all' ); ?></p>
				<ul class="hfa-services">
					<?php foreach ( $services as $service ) : ?>
						<li>
							<span class="dashicons <?php echo esc_attr( $service['icon'] ); ?>" aria-hidden="true"></span>
							<span>
								<strong><?php echo esc_html( $service['title'] ); ?></strong>
								<?php echo esc_html( $service['text'] ); ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
				<a class="hfa-side-link" href="<?php echo esc_url( 'https://www.buddydevelopers.com/' ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Visit buddydevelopers.com', 'html-for-all' ); ?></a>
			</div>
			<div class="hfa-side-card">
				<h2><?php echo esc_html__( 'Talk to us', 'html-for-all' ); ?></h2>
				<p><?php echo esc_html__( 'Planning a new site, or need ongoing maintenance for an existing one? Book a call.', 'html-for-all' ); ?></p>
				<a class="hfa-side-button hfa-side-button--primary" href="<?php echo esc_url( 'https://calendly.com/1naveengiri/discovery' ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html__( 'Book a free discovery call', 'html-for-all' ); ?>
				</a>
				<a class="hfa-side-button" href="<?php echo esc_url( 'https://calendly.com/1naveengiri/consultation' ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html__( 'Book a paid consultation', 'html-for-all' ); ?>
				</a>
				<p class="hfa-side-note"><?php echo esc_html__( 'The discovery call is free. The consultation is a paid, in-depth session.', 'html-for-all' ); ?></p>
			</div>
			<div class="hfa-side-card">
				<h2><span class="dashicons dashicons-video-alt3" aria-hidden="true"></span><?php echo esc_html__( 'Learn on YouTube', 'html-for-all' ); ?></h2>
				<p><?php echo esc_html__( 'Free tutorials on building directory, community and eCommerce sites with WordPress.', 'html-for-all' ); ?></p>
				<a class="hfa-side-link" href="<?php echo esc_url( 'https://www.youtube.com/@buddydevelopers' ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Subscribe to our channel', 'html-for-all' ); ?></a>
			</div>
		</aside>
		<?php
	}

	/**
	 * Name shown on a content card.
	 */
	private function post_type_label( string $post_type ): string {
		$components = Html_For_All::get_instance()->get_buddypress_components();
		if ( isset( $components[ $post_type ]['label'] ) ) {
			return $components[ $post_type ]['label'];
		}

		$object = get_post_type_object( $post_type );
		if ( $object instanceof \WP_Post_Type && isset( $object->labels->name ) ) {
			return (string) $object->labels->name;
		}

		return $post_type;
	}

	/**
	 * Dashicon used on a post type card.
	 */
	private function post_type_icon( string $post_type ): string {
		if ( 'page' === $post_type ) {
			return 'dashicons-admin-page';
		}

		if ( 'post' === $post_type ) {
			return 'dashicons-admin-post';
		}

		if ( 'bp_members' === $post_type ) {
			return 'dashicons-admin-users';
		}

		if ( 'bp_groups' === $post_type ) {
			return 'dashicons-groups';
		}

		if ( 'bp_activity' === $post_type ) {
			return 'dashicons-buddicons-activity';
		}

		return 'dashicons-portfolio';
	}

	/**
	 * Sample path shown on a post type card.
	 */
	private function example_path( string $post_type ): string {
		if ( 'post' === $post_type ) {
			return '/sample-post.html';
		}

		if ( 'page' === $post_type ) {
			return '/sample-page.html';
		}

		$components = Html_For_All::get_instance()->get_buddypress_components();
		if ( isset( $components[ $post_type ]['slug'] ) ) {
			$slug = $components[ $post_type ]['slug'];
			if ( 'bp_activity' === $post_type ) {
				return '/' . $slug . '/p/1.html';
			}

			return '/' . $slug . '/sample.html';
		}

		$object = get_post_type_object( $post_type );
		$slug   = ( $object instanceof \WP_Post_Type && ! empty( $object->rewrite['slug'] ) ) ? $object->rewrite['slug'] : $post_type;
		$path   = '/' . trim( (string) $slug, '/' );

		if ( function_exists( 'geodir_is_gd_post_type' ) && geodir_is_gd_post_type( $post_type ) && function_exists( 'geodir_get_permalink_structure' ) ) {
			$structure = geodir_get_permalink_structure();
			if ( ! is_string( $structure ) || '' === $structure ) {
				$structure = '/%postname%/';
			}

			$path .= untrailingslashit( $structure );
			$path  = (string) preg_replace_callback(
				'/%([^%]+)%/',
				static function ( array $matches ): string {
					$samples = array(
						'country'  => 'country',
						'region'   => 'region',
						'city'     => 'city',
						'category' => 'category',
						'postname' => 'sample',
						'post_id'  => '1',
					);

					return $samples[ $matches[1] ] ?? $matches[1];
				},
				$path
			);

			return untrailingslashit( $path ) . '.html';
		}

		return $path . '/sample.html';
	}

	/**
	 * Settings page URL.
	 */
	private function get_settings_page_url(): string {
		return admin_url( 'admin.php?page=hfa-settings' );
	}
}
