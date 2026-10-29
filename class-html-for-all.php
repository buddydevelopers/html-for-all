<?php
/**
 * Adds a .html suffix to selected post type permalinks.
 *
 * @package HFA
 */

namespace BUDDY_SEO_URL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rewrites public content permalinks so they end in .html.
 */
class Html_For_All {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Post types that should use a .html suffix.
	 *
	 * @var string[]
	 */
	private array $selected_post_types = array();

	/**
	 * Hooks permalink and rewrite behavior.
	 */
	private function __construct() {
		$this->load_selected_post_types();

		add_action( 'init', array( $this, 'apply_html_structures' ), 99 );
		add_action( 'wp_loaded', array( $this, 'apply_html_structures' ), 0 );
		add_action( 'init', array( $this, 'maybe_upgrade' ), 100 );

		add_filter( 'pre_post_link', array( $this, 'filter_pre_post_link' ), 10, 3 );
		add_filter( 'post_type_link', array( $this, 'filter_post_type_link' ), 99, 2 );
		add_filter( 'post_rewrite_rules', array( $this, 'filter_post_rewrite_rules' ) );
		add_filter( 'rewrite_rules_array', array( $this, 'filter_geodir_rewrite_rules' ), 1000 );
		add_filter( 'rewrite_rules_array', array( $this, 'filter_buddypress_rewrite_rules' ), 1001 );
		add_filter( 'bp_rewrites_get_url', array( $this, 'filter_buddypress_url' ), 20, 3 );
		add_filter( 'bp_get_canonical_url', array( $this, 'filter_buddypress_canonical_url' ), 20, 2 );
		add_filter( 'user_trailingslashit', array( $this, 'filter_trailing_slash' ), 10, 2 );
		add_filter( 'redirect_canonical', array( $this, 'filter_canonical_redirect' ), 10, 2 );
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
	 * Builds rewrite rules for the current selection.
	 */
	public function activate(): void {
		$this->load_selected_post_types();
		$this->migrate_legacy_permalink_structure();
		$this->apply_html_structures();
		flush_rewrite_rules( false );
		update_option( 'hfa_version', HFA_VERSION );
	}

	/**
	 * Restores permalink structures created by this plugin.
	 */
	public function deactivate(): void {
		$this->selected_post_types = array();
		$this->apply_html_structures();
		flush_rewrite_rules( false );
	}

	/**
	 * Saves the selected post types and refreshes rewrite rules once.
	 *
	 * @param string[] $post_types Post type names.
	 */
	public function update_selected_post_types( array $post_types ): void {
		$supported = $this->get_supported_post_types();
		$clean     = array();

		foreach ( $post_types as $post_type ) {
			if ( ! is_string( $post_type ) ) {
				continue;
			}

			$post_type = sanitize_key( $post_type );
			if ( '' !== $post_type && in_array( $post_type, $supported, true ) ) {
				$clean[] = $post_type;
			}
		}

		$this->selected_post_types = array_values( array_unique( $clean ) );
		update_option( 'hfa_selected_post_type', $this->selected_post_types );
		$this->apply_html_structures();
		flush_rewrite_rules( false );
	}

	/**
	 * Public post types whose permalinks can take a .html suffix.
	 *
	 * @return string[]
	 */
	public function get_supported_post_types(): array {
		$types = array();

		foreach ( array( 'post', 'page' ) as $builtin ) {
			if ( get_post_type_object( $builtin ) ) {
				$types[] = $builtin;
			}
		}

		$custom = get_post_types(
			array(
				'public'   => true,
				'_builtin' => false,
			),
			'objects'
		);

		foreach ( $custom as $name => $object ) {
			if ( $object instanceof \WP_Post_Type && ! empty( $object->rewrite ) ) {
				$types[] = $name;
			}
		}

		foreach ( $this->get_buddypress_components() as $name => $component ) {
			unset( $component );
			$types[] = $name;
		}

		return $types;
	}

	/**
	 * BuddyPress directories whose main URLs can end in .html.
	 *
	 * @return array<string, array{component: string, label: string, slug: string}>
	 */
	public function get_buddypress_components(): array {
		if ( ! function_exists( 'buddypress' ) || ! function_exists( 'bp_is_active' ) ) {
			return array();
		}

		$bp         = buddypress();
		$components = array(
			'bp_members'  => array(
				'component' => 'members',
				'label'     => __( 'Members', 'html-for-all' ),
			),
			'bp_groups'   => array(
				'component' => 'groups',
				'label'     => __( 'Groups', 'html-for-all' ),
			),
			'bp_activity' => array(
				'component' => 'activity',
				'label'     => __( 'Activity', 'html-for-all' ),
			),
		);
		$available  = array();

		foreach ( $components as $name => $component ) {
			if ( ! bp_is_active( $component['component'] ) || ! isset( $bp->{$component['component']} ) ) {
				continue;
			}

			$object = $bp->{$component['component']};
			$slug   = isset( $object->root_slug ) ? (string) $object->root_slug : '';
			if ( '' === $slug ) {
				continue;
			}

			$component['slug']    = trim( $slug, '/' );
			$available[ $name ]   = $component;
		}

		return $available;
	}

	/**
	 * Applies page and custom post type structures for the current request.
	 */
	public function apply_html_structures(): void {
		$this->apply_page_structure();
		$this->apply_cpt_structures();
	}

	/**
	 * Flushes rules once after an upgrade from the 1.2 permalink storage.
	 */
	public function maybe_upgrade(): void {
		$stored = get_option( 'hfa_version' );
		if ( is_string( $stored ) && HFA_VERSION === $stored ) {
			return;
		}

		$this->migrate_legacy_permalink_structure();
		$this->apply_html_structures();
		flush_rewrite_rules( false );
		update_option( 'hfa_version', HFA_VERSION );
	}

	/**
	 * Appends .html to the post permalink structure.
	 *
	 * @param mixed $permalink Permalink structure.
	 * @param mixed $post      Post object.
	 * @param mixed $leavename Whether the post name token is left in place.
	 * @return mixed
	 */
	public function filter_pre_post_link( $permalink, $post, $leavename ) {
		unset( $post, $leavename );

		if ( ! $this->is_selected( 'post' ) || ! is_string( $permalink ) || ! str_contains( $permalink, '%' ) ) {
			return $permalink;
		}

		if ( $this->ends_with_html( $permalink ) ) {
			return $permalink;
		}

		return $this->with_html_suffix( $permalink );
	}

	/**
	 * Appends .html to a finished GeoDirectory listing URL.
	 *
	 * GeoDirectory replaces the link at priority 0, so this runs after that.
	 *
	 * @param mixed $post_link Generated permalink.
	 * @param mixed $post      Post object.
	 * @return mixed
	 */
	public function filter_post_type_link( $post_link, $post ) {
		if ( ! $post instanceof \WP_Post || ! is_string( $post_link ) || '' === $post_link ) {
			return $post_link;
		}

		if ( ! $this->is_selected( $post->post_type ) || ! $this->is_geodir_post_type( $post->post_type ) ) {
			return $post_link;
		}

		if ( str_contains( $post_link, '?' ) ) {
			return $post_link;
		}

		return $this->append_html_to_url( $post_link );
	}

	/**
	 * Adds post rewrite rules for the .html form without replacing the saved permalink structure.
	 *
	 * Page and custom post type structures are applied here as well, because rewrite
	 * generation reads them while this filter is running.
	 *
	 * @param mixed $rules Existing post rewrite rules.
	 * @return array<string, string>
	 */
	public function filter_post_rewrite_rules( $rules ) {
		if ( ! is_array( $rules ) ) {
			$rules = array();
		}

		$this->apply_html_structures();

		global $wp_rewrite;

		if ( ! $this->is_selected( 'post' ) || ! $wp_rewrite instanceof \WP_Rewrite ) {
			return $rules;
		}

		$structure = $wp_rewrite->permalink_structure;
		if ( ! is_string( $structure ) || ! str_contains( $structure, '%' ) || $this->ends_with_html( $structure ) ) {
			return $rules;
		}

		$html_rules = $wp_rewrite->generate_rewrite_rules(
			$this->with_html_suffix( $structure ),
			EP_PERMALINK,
			true,
			true,
			false,
			false
		);

		if ( ! is_array( $html_rules ) || array() === $html_rules ) {
			return $rules;
		}

		return $html_rules + $rules;
	}

	/**
	 * Adds listing detail rules that match a GeoDirectory permalink ending in .html.
	 *
	 * Archive, location, search, and taxonomy rules are left unchanged.
	 *
	 * @param mixed $rules Generated rewrite rules.
	 * @return mixed
	 */
	public function filter_geodir_rewrite_rules( $rules ) {
		if ( ! is_array( $rules ) || ! function_exists( 'geodir_get_posttypes' ) || ! function_exists( 'geodir_get_permalink_structure' ) ) {
			return $rules;
		}

		$post_types = geodir_get_posttypes( 'array' );
		if ( ! is_array( $post_types ) || array() === $post_types ) {
			return $rules;
		}

		$structure = geodir_get_permalink_structure();
		if ( ! is_string( $structure ) || '' === $structure ) {
			$structure = '/%postname%/';
		}

		$extra = array();
		foreach ( $post_types as $post_type => $object ) {
			if ( ! is_string( $post_type ) || ! $this->is_selected( $post_type ) || ! is_array( $object ) ) {
				continue;
			}

			$rule = $this->geodir_html_rule( $post_type, $object, $structure );
			if ( null !== $rule ) {
				$extra[ $rule['regex'] ] = $rule['query'];
			}
		}

		if ( array() === $extra ) {
			return $rules;
		}

		return $extra + $rules;
	}

	/**
	 * Appends .html to BuddyPress directory, member, group, and activity permalinks.
	 *
	 * Profile tabs, group screens, registration, and search URLs stay unchanged.
	 *
	 * @param mixed $url         Generated BuddyPress URL.
	 * @param mixed $path_chunks URL pieces BuddyPress used to build the link.
	 * @param mixed $args        Original arguments.
	 * @return mixed
	 */
	public function filter_buddypress_url( $url, $path_chunks, $args ) {
		unset( $args );

		if ( ! is_string( $url ) || '' === $url || ! is_array( $path_chunks ) || str_contains( $url, '?' ) ) {
			return $url;
		}

		$component = isset( $path_chunks['component_id'] ) && is_string( $path_chunks['component_id'] ) ? $path_chunks['component_id'] : '';
		if ( '' === $component || ! $this->is_selected( 'bp_' . $component ) || ! $this->is_buddypress_primary_url( $path_chunks ) ) {
			return $url;
		}

		return $this->append_html_to_url( $url );
	}

	/**
	 * Sends a plain BuddyPress directory URL to its .html permalink.
	 *
	 * @param mixed $canonical_url Canonical URL BuddyPress assembled.
	 * @param mixed $args          Canonical URL arguments.
	 * @return mixed
	 */
	public function filter_buddypress_canonical_url( $canonical_url, $args ) {
		unset( $args );

		if ( ! is_string( $canonical_url ) || '' === $canonical_url || ! function_exists( 'bp_current_component' ) ) {
			return $canonical_url;
		}

		$component = bp_current_component();
		if ( ! is_string( $component ) || ! $this->is_selected( 'bp_' . $component ) ) {
			return $canonical_url;
		}

		if ( ! $this->is_buddypress_directory_request( $component ) ) {
			return $canonical_url;
		}

		$components = $this->get_buddypress_components();
		$key        = 'bp_' . $component;
		if ( ! isset( $components[ $key ]['slug'] ) ) {
			return $canonical_url;
		}

		return home_url( '/' . $components[ $key ]['slug'] . '.html' );
	}

	/**
	 * Adds rewrite rules for BuddyPress URLs that end in .html.
	 *
	 * @param mixed $rules Generated rewrite rules.
	 * @return mixed
	 */
	public function filter_buddypress_rewrite_rules( $rules ) {
		if ( ! is_array( $rules ) ) {
			return $rules;
		}

		$extra = array();
		foreach ( $this->get_buddypress_components() as $name => $component ) {
			if ( ! $this->is_selected( $name ) ) {
				continue;
			}

			$slug = preg_quote( $component['slug'], '#' );
			if ( 'members' === $component['component'] ) {
				$extra[ '^' . $slug . '\\.html/?$' ] = 'index.php?bp_members=1';
				if ( ! function_exists( 'bp_core_enable_root_profiles' ) || ! bp_core_enable_root_profiles() ) {
					$extra[ '^' . $slug . '/([^/]+)\\.html/?$' ] = 'index.php?bp_members=1&bp_member=$matches[1]';
				}
			} elseif ( 'groups' === $component['component'] ) {
				$extra[ '^' . $slug . '\\.html/?$' ]           = 'index.php?bp_groups=1';
				$extra[ '^' . $slug . '/([^/]+)\\.html/?$' ] = 'index.php?bp_groups=1&bp_group=$matches[1]';
			} elseif ( 'activity' === $component['component'] ) {
				$extra[ '^' . $slug . '\\.html/?$' ]             = 'index.php?bp_activities=1';
				$extra[ '^' . $slug . '/p/([0-9]+)\\.html/?$' ] = 'index.php?bp_activities=1&bp_activity_action=p&bp_activity_action_variables=$matches[1]';
			}
		}

		if ( array() === $extra ) {
			return $rules;
		}

		return $extra + $rules;
	}

	/**
	 * Keeps a trailing slash off URLs that already end in .html.
	 *
	 * @param mixed $url  URL being slashed.
	 * @param mixed $type URL type passed by WordPress.
	 * @return mixed
	 */
	public function filter_trailing_slash( $url, $type ) {
		if ( ! is_string( $url ) || ! $this->ends_with_html( $url ) ) {
			return $url;
		}

		$type = is_string( $type ) ? $type : '';
		if ( 'page' === $type || 'single' === $type || '' === $type || $this->is_selected( $type ) ) {
			return untrailingslashit( $url );
		}

		return $url;
	}

	/**
	 * Stops canonical redirects from removing a .html suffix.
	 *
	 * @param mixed $redirect_url  Proposed redirect.
	 * @param mixed $requested_url Requested URL.
	 * @return mixed
	 */
	public function filter_canonical_redirect( $redirect_url, $requested_url ) {
		if ( ! is_string( $redirect_url ) || '' === $redirect_url || ! is_string( $requested_url ) ) {
			return $redirect_url;
		}

		$requested_path = wp_parse_url( $requested_url, PHP_URL_PATH );
		if ( ! $this->ends_with_html( is_string( $requested_path ) ? $requested_path : '' ) ) {
			return $redirect_url;
		}

		$redirect_path = wp_parse_url( $redirect_url, PHP_URL_PATH );
		if ( $this->ends_with_html( is_string( $redirect_path ) ? $redirect_path : '' ) ) {
			return $redirect_url;
		}

		return false;
	}

	/**
	 * Reads the saved post type selection.
	 */
	private function load_selected_post_types(): void {
		$selected = get_option( 'hfa_selected_post_type', array() );
		if ( ! is_array( $selected ) ) {
			$this->selected_post_types = array();
			return;
		}

		$clean = array();
		foreach ( $selected as $post_type ) {
			if ( ! is_string( $post_type ) ) {
				continue;
			}

			$post_type = sanitize_key( $post_type );
			if ( '' !== $post_type ) {
				$clean[] = $post_type;
			}
		}

		$this->selected_post_types = array_values( array_unique( $clean ) );
	}

	/**
	 * Puts the permalink structure back when version 1.2 stored a .html copy.
	 */
	private function migrate_legacy_permalink_structure(): void {
		$legacy = get_option( 'old_permalink_structure' );
		if ( ! is_string( $legacy ) || '' === $legacy ) {
			if ( false !== get_option( 'old_permalink_structure', false ) ) {
				delete_option( 'old_permalink_structure' );
			}
			return;
		}

		if ( ! $this->ends_with_html( $legacy ) ) {
			$current = get_option( 'permalink_structure' );
			if ( is_string( $current ) && untrailingslashit( $current ) === $this->with_html_suffix( $legacy ) ) {
				global $wp_rewrite;
				if ( $wp_rewrite instanceof \WP_Rewrite ) {
					$wp_rewrite->set_permalink_structure( $legacy );
				} else {
					update_option( 'permalink_structure', $legacy );
				}
			}
		}

		delete_option( 'old_permalink_structure' );
	}

	/**
	 * Sets the in-memory page permalink structure.
	 */
	private function apply_page_structure(): void {
		global $wp_rewrite;

		if ( ! $wp_rewrite instanceof \WP_Rewrite || ! $wp_rewrite->using_permalinks() ) {
			return;
		}

		$structure = $wp_rewrite->root . '%pagename%';
		if ( $this->is_selected( 'page' ) ) {
			$structure .= '.html';
		}

		$wp_rewrite->page_structure = $structure;
	}

	/**
	 * Sets custom post type permastructs from their registered rewrite slugs.
	 */
	private function apply_cpt_structures(): void {
		global $wp_rewrite;

		if ( ! $wp_rewrite instanceof \WP_Rewrite || ! $wp_rewrite->using_permalinks() ) {
			return;
		}

		$custom = get_post_types(
			array(
				'public'   => true,
				'_builtin' => false,
			),
			'objects'
		);

		foreach ( $custom as $name => $object ) {
			if ( ! $object instanceof \WP_Post_Type || empty( $object->rewrite['slug'] ) ) {
				continue;
			}

			// GeoDirectory owns these permastructs and builds listing links itself.
			if ( $this->is_geodir_post_type( $name ) ) {
				continue;
			}

			if ( empty( $wp_rewrite->extra_permastructs[ $name ] ) || ! is_array( $wp_rewrite->extra_permastructs[ $name ] ) ) {
				continue;
			}

			$with_front = ! isset( $object->rewrite['with_front'] ) || $object->rewrite['with_front'];
			$base       = $with_front ? $wp_rewrite->front : $wp_rewrite->root;
			$struct     = $base . $object->rewrite['slug'] . '/%' . $name . '%';

			if ( $this->is_selected( $name ) ) {
				$struct = $this->with_html_suffix( $struct );
			}

			$wp_rewrite->extra_permastructs[ $name ]['struct'] = $struct;
		}
	}

	/**
	 * Whether the current request is a BuddyPress component directory.
	 */
	private function is_buddypress_directory_request( string $component ): bool {
		if ( ! in_array( $component, array( 'members', 'groups', 'activity' ), true ) ) {
			return false;
		}

		if ( function_exists( 'bp_is_directory_homepage' ) && bp_is_directory_homepage( $component ) ) {
			return false;
		}

		if ( function_exists( 'bp_is_user' ) && bp_is_user() ) {
			return false;
		}

		if ( 'groups' === $component && function_exists( 'bp_is_group' ) && bp_is_group() ) {
			return false;
		}

		if ( function_exists( 'bp_current_action' ) && bp_current_action() ) {
			return false;
		}

		if ( function_exists( 'bp_get_current_member_type' ) && bp_get_current_member_type() ) {
			return false;
		}

		return true;
	}

	/**
	 * Whether a BuddyPress URL is a directory or a single member, group, or activity item.
	 *
	 * @param array<string, mixed> $chunks URL pieces from BuddyPress.
	 */
	private function is_buddypress_primary_url( array $chunks ): bool {
		foreach ( array( 'directory_type', 'single_item_component', 'create_single_item', 'member_register', 'member_activate', 'community_search' ) as $key ) {
			if ( ! empty( $chunks[ $key ] ) ) {
				return false;
			}
		}

		$component = isset( $chunks['component_id'] ) && is_string( $chunks['component_id'] ) ? $chunks['component_id'] : '';
		$single    = isset( $chunks['single_item'] ) && is_string( $chunks['single_item'] ) ? $chunks['single_item'] : '';
		$action    = isset( $chunks['single_item_action'] ) && is_string( $chunks['single_item_action'] ) ? $chunks['single_item_action'] : '';
		$variables = isset( $chunks['single_item_action_variables'] ) && is_array( $chunks['single_item_action_variables'] ) ? $chunks['single_item_action_variables'] : array();

		if ( '' === $single && '' === $action && array() === $variables ) {
			return true;
		}

		if ( '' !== $single && '' === $action && array() === $variables && in_array( $component, array( 'members', 'groups' ), true ) ) {
			if ( 'members' === $component && function_exists( 'bp_core_enable_root_profiles' ) && bp_core_enable_root_profiles() ) {
				return false;
			}

			return true;
		}

		return 'activity' === $component
			&& '' === $single
			&& 'p' === $action
			&& 1 === count( $variables )
			&& is_numeric( $variables[0] );
	}

	/**
	 * Whether GeoDirectory registered this post type.
	 */
	private function is_geodir_post_type( string $post_type ): bool {
		return function_exists( 'geodir_is_gd_post_type' ) && geodir_is_gd_post_type( $post_type );
	}

	/**
	 * Builds one listing rewrite rule for a GeoDirectory post type.
	 *
	 * @param array<string, mixed> $object Post type settings from GeoDirectory.
	 * @return array{regex: string, query: string}|null
	 */
	private function geodir_html_rule( string $post_type, array $object, string $structure ): ?array {
		$slug = isset( $object['rewrite']['slug'] ) ? $object['rewrite']['slug'] : '';
		if ( ! is_string( $slug ) || '' === $slug ) {
			return null;
		}

		$parts = explode( '/', trim( $structure, '/' ) );
		foreach ( $parts as $index => $part ) {
			if ( '%category%' === $part ) {
				$parts[ $index ] = '%' . $post_type . 'category%';
			}
		}

		$parts = apply_filters( 'geodir_post_permalink_structure_params', $parts, $post_type, $object );
		if ( ! is_array( $parts ) || array() === $parts ) {
			return null;
		}

		$regex = '^' . preg_quote( trim( $slug, '/' ), '#' );
		$query = array();
		$match = 1;
		$total = count( $parts );
		$index = 0;

		foreach ( $parts as $part ) {
			++$index;
			if ( ! is_string( $part ) || '' === $part ) {
				continue;
			}

			$regex .= '/';
			$is_last = $index === $total;
			$token   = trim( $part );

			if ( str_starts_with( $token, '%' ) && str_ends_with( $token, '%' ) ) {
				$name = trim( $token, '%' );
				if ( 'post_id' === $name ) {
					$regex .= '([0-9]+)';
				} else {
					$regex .= '([^/]+)';
				}

				if ( $is_last ) {
					$regex .= '\.html';
				}

				if ( 'postname' === $name ) {
					$query[] = $post_type . '=$matches[' . $match . ']';
				} else {
					$query[] = $name . '=$matches[' . $match . ']';
				}
				++$match;
				continue;
			}

			$regex .= preg_quote( $token, '#' );
			if ( $is_last ) {
				$regex .= '\.html';
			}
		}

		if ( array() === $query || ! str_contains( $regex, '\.html' ) ) {
			return null;
		}

		return array(
			'regex' => $regex . '/?$',
			'query' => 'index.php?' . implode( '&', $query ),
		);
	}

	/**
	 * Appends .html to a permalink while keeping a query string or fragment.
	 */
	private function append_html_to_url( string $url ): string {
		$fragment = '';
		$query    = '';

		if ( str_contains( $url, '#' ) ) {
			$pieces   = explode( '#', $url, 2 );
			$url      = $pieces[0];
			$fragment = '#' . $pieces[1];
		}

		if ( str_contains( $url, '?' ) ) {
			$pieces = explode( '?', $url, 2 );
			$url    = $pieces[0];
			$query  = '?' . $pieces[1];
		}

		return $this->with_html_suffix( $url ) . $query . $fragment;
	}

	/**
	 * Whether a post type is selected in the settings.
	 */
	private function is_selected( string $post_type ): bool {
		return in_array( $post_type, $this->selected_post_types, true );
	}

	/**
	 * Whether a path or permalink structure already ends in .html.
	 */
	private function ends_with_html( string $value ): bool {
		return str_ends_with( untrailingslashit( $value ), '.html' );
	}

	/**
	 * Appends .html and removes a trailing slash.
	 */
	private function with_html_suffix( string $value ): string {
		$value = untrailingslashit( $value );
		if ( str_ends_with( $value, '.html' ) ) {
			return $value;
		}

		return $value . '.html';
	}
}
