# Add Custom Header Images
Contributors: afragen
Tags: headers, rotate headers, images
Requires at least: 5.0
Requires PHP: 5.6
Tested up to: 5.6
Stable tag: 2.0.3
Plugin URI: https://github.com/afragen/add-custom-header-images
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Remove default header images and load custom header images from 'The Headers' page. Allows for easy selection of random header images in your theme.

## Description

A plugin that should be able to remove default headers for a theme and add custom headers based upon the article written by <a href="http://juliobiason.net/2011/10/25/twentyeleven-with-easy-rotating-header-images/">Julio Biason</a> who was inspired by <a href="http://wpti.ps/?p=107">wpti.ps</a>.

Create a Page named **The Headers**. Then upload header images (media files) to the the page. The page may have a visibility of private. All images displayed on the page will be parsed, those attached to the page and those added via image blocks.

Once the custom header images are loaded, just go to `Appearance > Header` or `Customize > Header Image` and select `Randomize suggested headers`.

The plugin will display an error notice if there is **not** a page titled, **The Headers**.

## Installation

1. Create a new page. It can be private. It must be titled `The Headers`. Add any images that you want to use as custom header images to this page. Header images should be cropped appropriately for the base theme.
2. Upload `add-custom-header-images` directory to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to `Appearance > Header` or `Customize > Header Image` and select `Randomize` from the Default Images section.

## Attribution

Thanks to Andrijana Nikolic at [Web Hosting Geeks](http://webhostinggeeks.com) for translation help.

## Changelog

#### [unreleased]

#### 2.0.3 / 2020-08-01
* add check for PHP warning

#### 2.0.2 / 2020-03-28
* initialize some variables

#### 2.0.1 / 2020-03-03
* add header image support to theme if none exists
* parse images from page blocks
* refactor class methods

#### 1.9.0
* always load `after_theme_setup` filter

#### 1.8.1
* correctly initialize `load_plugin_textdomain()`

#### 1.8.0
* simplify admin notice
* WPCS compliant
* fixed to use `wp_get_attachment_url()`, thanks @poulh

#### 1.7.0
* use WP_Query instead of `get_children()`
* only load `after_theme_setup` hook on front end

#### 1.6.1
* update _Tested to_
* simplify conditional

#### 1.6.0
* don't run from constructor
* requires PHP 5.3, sorta

#### 1.5.2
* use class variables to hold title and page data to reduce number of calls to database

#### 1.5.1
* set `after_theme_setup` hook to use later priority to ensure $_wp_default_headers is set, fixes removal of default images

#### 1.5.0
* removed specific srcset code as it was unnecessary and caused failures. `srcset` needs to be set correctly in `header.php`

#### 1.4.2
* fixed malformed closing `strong` tag in error message

#### 1.4.1
* escape translations of page name

#### 1.4.0
* added srcset for responsive image sizes
* tested and updated for WP 4.4.0

#### 1.3.3
* tested to 4.3

#### 1.3.2
* load textdomain early so translations work.

#### 1.3.1
* fix readme.txt as plugin name generic
* simplify warning, remove nested if statements
* update .pot

#### 1.3.0
* better i18n strings, updated POT

#### 1.2.0
* move `remove_default_header_images` to run only if **The Headers** page is present. Should fix a PHP Notice too.

#### 1.1.0
* remove `deactivate_plugins` to and just display an error notice for better compatibility.

#### 1.0.4
* added some error checking

#### 1.0.3
* more graceful exit and return

#### 1.0.2
* exit after deactivating plugin when not able to be activated

#### 1.0.1
* Add .pot files
* Fix short description by removing Markdown

#### 1.0.0
* Initial commit to WordPress repository
