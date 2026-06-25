=== OFG Code Editor ===
Contributors: weblogbaz
Tags: code, shortcode, gutenberg, syntax, editor
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a reusable code box block and shortcode with line numbers, copy support and a dedicated classic editor insert button.

== Description ==

OFG Code Editor provides a consistent way to publish code snippets in WordPress.

Features:

* Gutenberg block for formatted code output.
* Classic editor button for inserting the shortcode template.
* Shortcode support via `[ofgcodeeditor_code]`.
* Copy button and line numbers on the frontend.
* Responsive code box layout with light and dark friendly styling.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/ofg-code-editor` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the `Plugins` screen in WordPress.
3. Insert the `OFG Code Editor` block in Gutenberg or use the classic editor helper button.

== Frequently Asked Questions ==

= Which shortcode should I use? =

Use `[ofgcodeeditor_code language="html"]...[/ofgcodeeditor_code]`.

= Can I change the visible label? =

The plugin uses a fixed header label: OFG Code Editor Plugin.

== Screenshots ==

1. Frontend code block with line numbers and copy support.
2. Gutenberg editor preview and language selector.

== Changelog ==

= 1.0.3 =
* Updated plugin tested version metadata.

= 1.0.2 =
* Updated plugin metadata for WordPress.org review.
* Removed the legacy generic shortcode alias.
* Removed the unnecessary wrapper around the plugin class.

= 1.0.1 =
* Removed manual textdomain loading.

= 1.0.0 =
* Initial release.
