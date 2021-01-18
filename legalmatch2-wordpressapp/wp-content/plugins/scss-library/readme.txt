=== SCSS-Library ===

Contributors: sebaxtian  
Tags: SASS, compiler, SCSS  
Requires at least: 4.4  
Tested up to: 5.5  
Stable tag: trunk  
Requires PHP: 7.1  
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add support for using SCSS style files with wp\_enqueue\_style.

== Description ==

This plugin allows you to use SCSS files directly in **wp\_enqueue\_style**. Just add the file to the list of styles and the plugin will compile it when necessary.

The base of this plugin is strongly influenced by the [WP-SCSS](https://wordpress.org/plugins/wp-scss/) code of and extracts some ideas from [Sassify](https://wordpress.org/plugins/sassify/). The goal is to keep the plugin updated with the latest version of [scssphp](https://packagist.org/packages/scssphp/scssphp), remove configuration options from the graphical interface, and use the **scssphp** capabilities to create debug files.

This plugin is not intended to be installed by a conventional user, but to be required by templates or plugins that wish to include **SCSS** style files and therefore the configuration is expected to be done in the code.

== Installation ==

1. Decompress scss-library.zip and upload `/scss-library/` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the __Plugins__ menu in WordPress.

== Frequently Asked Questions ==

= Performance =
This plugin adds many extra steps for something as simple as printing a style link tag inside a site:

* Check the creation time of the compiled file.
* Interacts with the database.
* Converts a SCSS file into a style file.

Obviously it will add a few thousandths of a second to the loading time of the site.

= How much will performance be affected? =
It depends on how many **SCSS** files you add to the list of styles and how complex they are.

= So I shouldn't use it in production? =
Of course you can use it. If you are looking for a fast site then you should also add a cache or optimization plugin to your production environment, although it is very likely that you have already done so. Personally I have worked with [Comet Cache](https://wordpress.org/plugins/comet-cache/) and [Autoptimize](https://wordpress.org/plugins/autoptimize/) without any inconvenience. Any problems you encounter with another cache plugin don't hesitate to write down the details to replicate the error. Remember that the more information you include in the report the easier it will be to fix it.

= Then what are you looking for with this plugin? =
What I want is to emulate for the style files the ease of development offered by [Timber](https://wordpress.org/plugins/timber-library/). Let **SCSS-Library** be to **SCSS** what **Timber** is to **Twig**.

My goal with this plugin is to be able to change the SCSS file directly and see the result immediately. No previous compilations or commands in a terminal. It is intended for development teams that include graphic designers who understand **CSS** and **HTML** but prefer not to have to open a terminal; and to support lazy programmers who like me prefer to leave repetitive tasks to the machines.

= Is this plugin bug free? =
I don't think so. Feedbacks would be appreciated.

== Changelog ==
= 0.2.7 =
* Update scssphp to 1.0.9.

= 0.2.6 =
* Solving bugs.

= 0.2 =
* Feature: develop environment options.

= 0.1.6 =
* Solving bugs.

= 0.1.5 =
* Solving multisite bugs.

= 0.1.4 =
* Testing: Test environment added.

= 0.1.3 =
* Bug fixed: Autoptimize does not aggregate compiled files.

= 0.1.2 =
* Create compiled file if the file does not exist.
* Bug fixed: new version number in the wp\_enqueue\_style declaration recreates build file with a new name.

= 0.1.1 =
* Bug fixed: multisite pages lost the path to the compiled files.

= 0.1 =
* First release.
