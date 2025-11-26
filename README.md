# WordPress Beats Playground

This repository houses a **WordPress Playground configuration** that spins up a disposable site focused on the **Beats Upload Player** plugin. The plugin is bundled directly in this project, so Playground unzips it locally, activates it, and creates a “Beats Playground” page that renders the `[beats_player_demo]` shortcode in seconds—no hosting, no local setup.

---

## 🚀 Quick Start

1. Open the link below in any modern browser.  
2. Playground provisions a brand-new WordPress instance (powered by WebAssembly).  
3. The Beats Upload Player plugin is already active, and the homepage shows the demo shortcode.

👉 **https://playground.wordpress.net/?config=https://raw.githubusercontent.com/crystalthedeveloper/wordpress-beats-playground/main/playground-beats.json**

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
  "landingPage": "/beats-playground/",
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
      "step": "runPHP",
      "code": "require_once ABSPATH . 'wp-admin/includes/post.php';\n\n$page_args = array(\n    'post_title'   => 'Beats Playground',\n    'post_name'    => 'beats-playground',\n    'post_status'  => 'publish',\n    'post_type'    => 'page',\n    'post_content' => '<!-- wp:shortcode -->[beats_player_demo]<!-- /wp:shortcode -->'\n);\n\n$existing = get_page_by_path( $page_args['post_name'], OBJECT, 'page' );\nif ( $existing ) {\n    $page_args['ID'] = $existing->ID;\n    $page_id = wp_update_post( $page_args );\n} else {\n    $page_id = wp_insert_post( $page_args );\n}\n\nupdate_option( 'show_on_front', 'page' );\nupdate_option( 'page_on_front', $page_id );"
    }
  ]
}
```

- **Plugin bundle:** downloads `assets/beats-upload-player.zip` from this repo and unzips it into `/wp-content/plugins`.  
- **Activation:** ensures the plugin is ready the moment Playground boots and renders the shortcode immediately.  
- **Demo page + homepage:** a `runPHP` step inserts (or updates) the Beats Playground page and assigns it as the front page so the shortcode loads immediately.

---

## ✏️ Customize the Demo

- **Modify the page content:** edit the `content` field to include copy, headings, or additional shortcodes.  
- **Add more plugins:** drop another plugin ZIP into `assets/`, add a matching `unzip` step (pointing at the raw file), and then activate it.  
- **Chain steps:** Playground also accepts steps such as `importFile`, `setOption`, or running `wp-cli` commands. See the [official docs](https://wordpress.github.io/wordpress-playground/) for the full schema.

### Adding the Beats Visualizer (optional)

If you want the page to showcase the Beats Visualizer plugin too, bundle its ZIP (for example `assets/beats-visualizer.zip`), add another `unzip` step that extracts to `/wordpress/wp-content/plugins/`, and then add an `activatePlugin` step targeting `beats-visualizer/beats-visualizer.php` before the `runPHP` page-creation block.

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
