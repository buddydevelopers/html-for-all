=== .html for all url ===
Contributors: 1naveengiri, buddydevelopers, piyushmultidots
Donate link: https://buddydevelopers.com/
Tags: html, permalink, url, seo, migration
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add a .html ending to the URLs of posts, pages, custom post types, GeoDirectory listings and BuddyPress profiles, groups and activity.

== Description ==

**.html for all url** adds a `.html` ending to your WordPress permalinks. You choose which content types get it, so you can turn `yoursite.com/about-us/` into `yoursite.com/about-us.html`.

The most common reason to use it is **moving an old website to WordPress without breaking its links**. Many older sites were built from static HTML files, Dreamweaver or another CMS, and every page URL ended in `.html`. If you rebuild that site in WordPress and the URLs change, you lose:

* backlinks from other websites
* bookmarks and links shared on social media, in emails and in printed material
* search rankings, while Google works out where every page moved to

With this plugin the WordPress pages can keep exactly the same URLs, so nothing needs to be redirected.

= When is this plugin useful? =

* **Migrating from a static HTML site.** Keep `/services.html`, `/contact.html` and every other old URL working after the move.
* **Migrating from another platform** such as Blogger, Joomla or an older site builder that used `.html` URLs.
* **Redesigning a client website** where the client wants their existing URLs kept.
* **Directory websites** built with GeoDirectory, where listing pages can end in `.html` while category, location and search URLs stay unchanged.
* **Community websites** built with BuddyPress, where member profiles, groups and activity can end in `.html`.
* **Keeping one URL style** across a site that already uses `.html` for some pages.

= Features =

* Add `.html` to posts, pages and any public custom post type.
* Turn it on for each content type separately.
* GeoDirectory support: listing pages end in `.html`, and category, location and search URLs are left alone.
* BuddyPress support: member profiles, groups, activity and their directories can end in `.html`. Profile tabs, group screens and registration URLs stay unchanged.
* Old links without `.html` keep working. WordPress either loads the page or redirects to the new `.html` URL.
* Rewrite rules refresh automatically when you save, activate or deactivate the plugin. You don't need to re-save permalinks by hand.
* A simple settings screen under the **.html For All** menu in wp-admin.

= Does .html help SEO? =

Not by itself. Google ranks `/about-us.html` and `/about-us/` the same way. The SEO value comes from **keeping URLs that already have rankings and backlinks**, which is what this plugin is for. On a brand new site there is no ranking benefit, so only use it if you prefer the style.

= Watch the demo =

https://www.youtube.com/watch?v=k4FzgxeYu2g

= Need help with your WordPress site? =

This plugin is built by [BuddyDevelopers](https://www.buddydevelopers.com/), a WordPress agency that builds and maintains **directory**, **community** and **eCommerce** websites.

* [Book a free discovery call](https://calendly.com/1naveengiri/discovery) to talk about a new site or ongoing maintenance.
* [Book a paid consultation](https://calendly.com/1naveengiri/consultation) for an in-depth session on your project.

== Installation ==

1. Upload the `html-for-all-url` folder to `/wp-content/plugins/`, or install it from **Plugins → Add New**.
2. Activate the plugin.
3. Make sure pretty permalinks are on: go to **Settings → Permalinks** and choose any option other than **Plain**.
4. Open the **.html For All** menu in wp-admin.
5. Select the content types that should end in `.html` and click **Save changes**.

That's it. Visit any selected page, post or listing to see the new URL.

== Frequently Asked Questions ==

= Is it compatible with the current versions of WordPress and PHP? =

Yes. Version 1.3 is tested with WordPress 7.1 and PHP 8.5, and needs WordPress 6.0 and PHP 7.4 or newer. Pretty permalinks must be turned on.

= Will my old URLs stop working? =

No. If someone opens `/about-us/` for a page that now uses `/about-us.html`, WordPress sends them to the `.html` version. Posts still load at both addresses. Links inside WordPress, menus and the canonical URL all use the new `.html` version.

= What happens if I deactivate the plugin? =

Your URLs go back to the normal WordPress format, and the `.html` URLs will stop working. If those URLs are already indexed or linked to, add redirects before you deactivate.

= A page shows "Not found" after I change the settings. What should I do? =

The plugin refreshes rewrite rules automatically. If a page still doesn't load, go to **Settings → Permalinks** and click **Save Changes** once. If you use a caching plugin or a CDN, clear its cache too.

= Does it work with GeoDirectory? =

Yes. Select your GeoDirectory post type and its listing pages will end in `.html`. Category, location and search pages keep their normal URLs.

= Does it work with BuddyPress? =

Yes. Select Members, Groups or Activity to add `.html` to those URLs. Profile tabs, group screens and registration pages are not changed.

= Can I add .html to category or tag archive URLs? =

Not yet. The plugin currently changes single content URLs only.

== Screenshots ==

1. The .html For All settings screen, where you choose which content types end in .html.
2. A page loading with a .html URL.

== Changelog ==

= 1.3 =
* Compatible with WordPress 7.1 and PHP 8.5.
* New: GeoDirectory listing URLs can end in .html without changing category, location or search URLs.
* New: BuddyPress member profiles, groups, activity and their directories can end in .html. Profile tabs, group screens and registration URLs stay unchanged.
* New: Settings have their own top-level .html For All menu in wp-admin, with a redesigned screen.
* Improved: Rewrite rules refresh when settings change, on activation and on deactivation.
* Security: Settings are saved with a capability check, a nonce and a sanitized post type list.

= 1.2 [August 3, 2017] =
* Fixed: post permalink issue.

= 1.1 [July 29, 2017] =
* Fixed: warnings on the Add New Page screen.
* Fixed: URL not found for new pages.

= 1.0 [July 28, 2017] =
* First release. Add .html to post, page and custom post type URLs.
* Settings to turn .html on or off for each post type.

== Upgrade Notice ==

= 1.3 =
Requires WordPress 6.0 and PHP 7.4 or newer. Adds GeoDirectory and BuddyPress support. Rewrite rules refresh automatically after the upgrade.

= 1.2 =
Fixes an issue with post permalinks.
