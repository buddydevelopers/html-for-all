=== .html for all url ===
Contributors: 1naveengiri, buddydevelopers
Donate link: https://buddydevelopers.com/
Tags: html, permalink, url, custom post type
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

convert your url in .html easaly for post, page, custom post type ...

== Description ==

Adds .html to pages, post, custom post type
1) Login your wordpress account

2) Activate .html for all plugin 

2) Open the .html For All menu in wp-admin

3) Select the Post type you want to have .html extension in it and save them.

Thats all, Now just refresh your page, post or CPT and see the magic.

This Plugin closely related with rewrite rules so if anything problem seem in loading page with .html, refresh the permalink once 
Go to settings->.permalink and update it.

[www.buddydevelopers.com](http://www.buddydevelopers.com/)




== Installation ==

1) Upload `html-for-all-url` to the `/wp-content/plugins/` directory

2) Activate the plugin  

3) Login your wordpress account


4) Open the .html For All menu in wp-admin


Soon this plugin coming with some more feature to make .html in all WordPress URL

Enjoy the feature of 1st version and support with your review and rating.

 
== Frequently asked questions ==

= is it compatible with current WordPress and PHP? =

Yes. Version 1.5 is tested with WordPress 7.1 and PHP 8.5, and requires WordPress 6.0 and PHP 7.4 or newer. Pretty permalinks must be enabled. GeoDirectory listings and BuddyPress members, groups, and activity use their own permalink structures.

= can we change category archive link with .html at the end? =

No, Still this feature is not included in this plugin. 

== Demo ==
 https://www.youtube.com/watch?v=k4FzgxeYu2g
 
== Screenshots ==

1. Setting tab for .html for all by BuddyDeveloper[http://buddydevelopers.com]
2. Setting screen of .html for all post, page, custom post type 

== Changelog ==

= 1.0 [July 28, 2017]  =
* Allow to manage and change post, page, custom post type url. 
* Provide a handle in setting to enable and disable .html for different custom post type.

= 1.1 [July 29, 2017] =

* FIXED

 * Warnings on add new page screen
 * Issue with URL not found for new pages.

 = 1.2 [August 3, 2017] =

* FIXED

 * Fixed issue for post Permalink

= 1.3 =
* Compatible with WordPress 7.1 and PHP 8.5.
* Rewrite rules are refreshed when settings change, on activation, and on deactivation.
* Settings are saved with a capability check, nonce, and sanitized post type list.
* Settings open from a top-level .html For All admin menu.
* BuddyPress member profiles, groups, activity permalinks, and those directories can end in .html. Profile tabs, group screens, and registration URLs stay unchanged.
* GeoDirectory listing permalinks can end in .html without changing category, location, or search URLs.


== Upgrade Notice ==

= 1.3 =
Requires WordPress 6.0 and PHP 7.4 or newer. Rewrite rules refresh automatically after the upgrade.
GeoDirectory listing URLs use .html when that post type is selected. Rewrite rules refresh automatically after the upgrade.
Select Members, Groups, or Activity to add .html to those BuddyPress URLs. Rewrite rules refresh when you save.


= 1.1 =
.html for all url 1.2, Minor release which fixes issues post permalink.
