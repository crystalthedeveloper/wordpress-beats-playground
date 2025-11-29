=== Beats Upload Player ===
Contributors: crystallewis
Donate link: https://www.crystalthedeveloper.ca
Tags: beats, audio, uploader, playlist, ecommerce
Requires at least: 6.3
Tested up to: 6.6
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Upload beats, manage your catalog, and embed a visual player with infinite scroll and category search in WordPress.

== Description ==

Beats Upload Player is a full toolkit for producers and beat marketplaces. It provides:

* Front-end beat uploader with cover art, metadata, and pricing.
* Infinite-scroll storefront powered by AJAX and `<div id="beats-wrapper">`.
* Global sticky audio player that reacts to clicks anywhere on the grid.
* Category search bar and curated genre list boilerplate.
* Admin “Beats Manager” page for editing, replacing art, and deleting beats.
* Optional visualizer demo, plus a “Beats Player Demo” shortcode that works offline or inside WordPress Playground.

All storage is handled in `wp-content/uploads/beats/` and is safe for multi-site or Playground environments. No tracking, no remote licensing, 100% GPL‑compatible.

== Installation ==

1. Upload the `beats-upload-player` folder to the `/wp-content/plugins/` directory or install via Plugins → Add New.
2. Activate the plugin through the “Plugins” menu in WordPress.
3. Visit “Beats Manager” in wp-admin to upload your first beats or use the `[beats_cltd_upload_form]` shortcode on the front end.
4. Add `[beats_cltd_display_home]`, `[beats_cltd_global_player]`, or `[beats_player_demo]` to any page/Block editor Shortcode block.

== Frequently Asked Questions ==

= Does it work with block themes like Twenty Twenty-Five? =

Yes. The plugin hooks `render_block` and buffers the final HTML so the `<div id="beats-wrapper">` markup always loads, even if the theme skips `the_content()`. A MutationObserver in `beats-loader.js` watches for late renders so infinite scroll still initializes.

= Where are the beats stored? =

Audio, art, and `beats.json` live inside `wp-content/uploads/beats/`. This path is generated via `wp_upload_dir()` so it works in WordPress Playground or custom setups.

= Are there demo beats? =

When the plugin detects WordPress Playground it seeds `resources/beats.json` along with bundled demo audio/art so users can try the storefront instantly.

== Screenshots ==

1. AJAX infinite scroll storefront with beat cards and overlays.
2. Global player pinned to the bottom of the page.
3. Beats Manager grid inside wp-admin for editing metadata.

== Changelog ==

= 1.0.0 =
* Initial release: uploader, Beats Manager, infinite scroll grid, global player, Playground support, and visualization demo.
