# WordPress Beats Playground

This repository houses a **WordPress Playground configuration** that spins up a disposable site focused on the **Beats Upload Player** plugin. The plugin is bundled directly in this project, so Playground unzips it locally, activates it, and creates a “Beats Playground” page that renders the `[beats_player_demo]` shortcode in seconds—no hosting, no local setup.

---

## 🚀 Quick Start

1. Open the link below in any modern browser.  
2. Playground provisions a brand-new WordPress instance (powered by WebAssembly).  
3. The Beats Upload Player plugin is already active, and the homepage shows the demo shortcode.

👉 **https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/crystalthedeveloper/wordpress-beats-playground/main/playground-beats.json**

> Forking the repo? Replace the `raw.githubusercontent.com` URL above with your fork’s raw `playground-beats.json`.

---

## 📁 Repository Contents

- `README.md` — Documentation for launching and customizing the Playground experience.  
- `playground-beats.json` — The configuration consumed by WordPress Playground.
- `assets/beats-upload-player.zip` — Bundled Beats Upload Player plugin that the blueprint unzips into `/wp-content/plugins`.

---

## 🔧 How the Playground Is Provisioned

The `playground-beats.json` file defines every automated step:

```json
{
  "$schema": "https://playground.wordpress.net/blueprint-schema.json",
  "landingPage": "/",
  "preferredVersions": {
    "php": "8.2",
    "wp": "6.5"
  },
  "steps": [
    {
      "step": "unzip",
      "zipFile": {
        "resource": "url",
        "url": "https://raw.githubusercontent.com/crystalthedeveloper/wordpress-beats-playground/main/assets/beats-upload-player.zip"
      },
      "extractToPath": "/wordpress/wp-content/plugins/"
    },
    {
      "step": "activatePlugin",
      "pluginPath": "beats-upload-player/beats-upload-player.php"
    },
    {
      "step": "writeFile",
      "path": "/wordpress/wp-content/themes/twentytwentyfour/page-beats-template.php",
      "data": "<?php\n/**\n * Template Name: Beats Playground Template\n */\nget_header();\n?>\n<main id=\"primary\" class=\"site-main\">\n    <div id=\"beats-wrapper\" class=\"beats-wrapper\" style=\"max-width:900px;margin:0 auto;padding:40px 20px;text-align:center;display:flex;flex-direction:column;gap:18px;\">\n        <h1 style=\"font-size:54px;font-weight:700;margin:0;\">Beats Playground</h1>\n        <p style=\"font-size:16px;\">Preview the Beats shortcodes below.</p>\n        <div class=\"beats-shortcodes\" style=\"display:flex;flex-direction:column;gap:18px;\">\n            <?php echo do_shortcode( '[beats_category_search]' ); ?>\n            <?php echo do_shortcode( '[beats_visualizer]' ); ?>\n            <?php echo do_shortcode( '[beats_display_home]' ); ?>\n            <?php echo do_shortcode( '[beats_global_player]' ); ?>\n        </div>\n        <p style=\"font-size:14px;margin-top:20px;\">&copy; Beats Playground</p>\n    </div>\n</main>\n<?php\nget_footer();"
    },
    {
      "step": "runPHP",
      "code": "require_once ABSPATH . 'wp-admin/includes/post.php';\nrequire_once ABSPATH . 'wp-admin/includes/misc.php';\n\n$page_args = array(\n    'post_title'   => 'Beats Playground',\n    'post_name'    => 'beats-playground',\n    'post_status'  => 'publish',\n    'post_type'    => 'page',\n    'post_content' => '<!-- wp:shortcode -->[beats_global_player]<!-- /wp:shortcode -->',\n    'meta_input'   => array(\n        '_wp_page_template' => 'page-beats-template.php'\n    )\n);\n\n$existing = get_page_by_path( $page_args['post_name'], OBJECT, 'page' );\nif ( $existing ) {\n    $page_args['ID'] = $existing->ID;\n    $page_id = wp_update_post( $page_args );\n} else {\n    $page_id = wp_insert_post( $page_args );\n}\n\nupdate_option( 'show_on_front', 'page' );\nupdate_option( 'page_on_front', $page_id );\nflush_rewrite_rules();\nwp_safe_redirect( home_url( '/beats-playground/' ) );"
    }
  ]
}
```

- **Plugin bundle:** downloads `assets/beats-upload-player.zip` from this repo and unzips it into `/wp-content/plugins`.  
- **Activation:** ensures the plugin is ready the moment Playground boots.  
- **Landing page:** a `runPHP` step creates/updates the Beats Playground page, sets it as the homepage, flushes rewrite rules, and redirects to `/beats-playground/` so the service worker doesn’t cache a 404 before the shortcodes render.

---

## ✏️ Customize the Demo

- **Modify the page content:** edit the HTML/shortcodes inside the `runPHP` block to change the layout or add/remove shortcodes.  
- **Add more plugins:** drop another plugin ZIP into `assets/`, add a matching `unzip` step (pointing at the raw file), and then activate it.  
- **Chain steps:** Playground also accepts steps such as `importFile`, `setOption`, or running `wp-cli` commands. See the [official docs](https://wordpress.github.io/wordpress-playground/) for the full schema.

### Adding the Beats Visualizer (optional)

If you want the page to showcase the Beats Visualizer plugin too, bundle its ZIP (for example `assets/beats-visualizer.zip`), add another `unzip` step that extracts to `/wordpress/wp-content/plugins/`, add an `activatePlugin` step targeting `beats-visualizer/beats-visualizer.php`, and extend the `runPHP` markup to drop in the extra shortcode.

Then reference its shortcode inside the page content, e.g.:

```html
<!-- wp:heading --><h2>Visualizer Preview</h2><!-- /wp:heading -->
<!-- wp:shortcode -->[beats_visualizer_demo]<!-- /wp:shortcode -->
```

---

## 🧩 Shortcode Helpers

The demo expects each plugin to register its own “demo wrapper” shortcode so the preview has context. Example:

```php
add_shortcode( 'beats_player_demo', function () {
    return '<h3>Beats Upload Player Demo</h3>' . do_shortcode( '[beats_upload_player]' );
} );
```

Ship an equivalent snippet in each plugin to keep the Playground content clean.

---

## 🤝 Feedback

Questions, bugs, or feature ideas? Open an issue or reach out via https://www.crystalthedeveloper.ca. Thanks for testing Beats in Playground!
