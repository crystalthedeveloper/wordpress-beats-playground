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
    "wp": "6.8"
  },
  "steps": [
    {
      "step": "unzip",
      "zipFile": {
        "resource": "url",
        "url": "https://raw.githubusercontent.com/crystalthedeveloper/wordpress-beats-playground/main/assets/beats-upload-player.zip",
        "caption": "Bundling Beats Upload Player"
      },
      "extractToPath": "/wordpress/wp-content/plugins/"
    },
    {
      "step": "defineWpConfigConsts",
      "consts": {
        "WP_ENVIRONMENT_TYPE": "playground",
        "WP_PLAYGROUND": true,
        "IS_PLAYGROUND": true
      }
    },
    {
      "step": "activatePlugin",
      "pluginPath": "beats-upload-player/beats-upload-player.php"
    },
    {
      "step": "activateTheme",
      "themeFolderName": "twentytwentyfive"
    },
    {
      "step": "runPHP",
      "code": "require_once ABSPATH . 'wp-admin/includes/post.php';\nrequire_once ABSPATH . 'wp-admin/includes/theme.php';\nrequire_once ABSPATH . 'wp-admin/includes/template.php';\n\nif ( function_exists( 'beats_prime_data' ) ) {\n    beats_prime_data();\n} elseif ( function_exists( 'beats_seed_playground_demo' ) ) {\n    beats_seed_playground_demo();\n}\n\n$beats_section = <<<HTML\n<!-- wp:group {\"tagName\":\"main\",\"align\":\"full\",\"style\":{\"spacing\":{\"padding\":{\"top\":\"30px\",\"right\":\"20px\",\"bottom\":\"40px\",\"left\":\"20px\"}}}} -->\n<main class=\"wp-block-group alignfull\" style=\"padding-top:30px;padding-right:20px;padding-bottom:40px;padding-left:20px\">\n<!-- wp:group {\"align\":\"wide\",\"style\":{\"spacing\":{\"blockGap\":\"18px\",\"margin\":{\"top\":\"0\",\"bottom\":\"0\"},\"padding\":{\"top\":\"20px\",\"right\":\"20px\",\"bottom\":\"20px\",\"left\":\"20px\"}},\"border\":{\"radius\":\"16px\",\"color\":\"#f1f1f1\",\"width\":\"1px\"}},\"layout\":{\"type\":\"constrained\"}} -->\n<div class=\"wp-block-group alignwide\" style=\"border-color:#f1f1f1;border-width:1px;border-radius:16px;margin-top:0;margin-bottom:0;padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px\">\n<!-- wp:heading {\"textAlign\":\"center\"} -->\n<h2 class=\"wp-block-heading has-text-align-center\">Beats</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph {\"align\":\"center\"} -->\n<p class=\"has-text-align-center\">Preview every Beats shortcode below.</p>\n<!-- /wp:paragraph -->\n<!-- wp:group {\"style\":{\"spacing\":{\"blockGap\":\"12px\"}},\"layout\":{\"type\":\"constrained\"}} -->\n<div class=\"wp-block-group\" style=\"gap:12px\">\n<!-- wp:shortcode -->[beats_category_search]<!-- /wp:shortcode -->\n<!-- wp:shortcode -->[beats_visualizer]<!-- /wp:shortcode -->\n<!-- wp:shortcode -->[beats_display_home]<!-- /wp:shortcode -->\n<!-- wp:shortcode -->[beats_global_player]<!-- /wp:shortcode -->\n</div>\n<!-- /wp:group -->\n</div>\n<!-- /wp:group -->\n</main>\n<!-- /wp:group -->\nHTML;\n\n$theme      = wp_get_theme();\n$theme_slug = $theme->get_stylesheet();\n$templates  = array(\n    'home'       => 'Home',\n    'front-page' => 'Front Page',\n    'index'      => 'Index'\n);\n\n$template_wrapper = sprintf(\n    '<!-- wp:template-part {\"slug\":\"header\",\"theme\":\"%1$s\",\"area\":\"header\"} /-->%2$s<!-- wp:template-part {\"slug\":\"footer\",\"theme\":\"%1$s\",\"area\":\"footer\"} /-->',\n    esc_attr( $theme_slug ),\n    $beats_section\n);\n\nforeach ( $templates as $template_slug => $template_label ) {\n    $template_id = sprintf( '%s//%s', $theme_slug, $template_slug );\n\n    $template_args = array(\n        'post_title'   => $template_label,\n        'post_name'    => $template_id,\n        'post_status'  => 'publish',\n        'post_type'    => 'wp_template',\n        'post_content' => $template_wrapper,\n        'meta_input'   => array(\n            'origin' => 'custom'\n        )\n    );\n\n    $existing_template = get_page_by_path( $template_id, OBJECT, 'wp_template' );\n    if ( $existing_template ) {\n        $template_args['ID'] = $existing_template->ID;\n        $saved_id            = wp_update_post( $template_args );\n    } else {\n        $saved_id = wp_insert_post( $template_args );\n    }\n\n    if ( ! is_wp_error( $saved_id ) ) {\n        wp_set_post_terms( $saved_id, $theme_slug, 'wp_theme' );\n        echo $template_label . ' template updated (post ID ' . $saved_id . ').' . PHP_EOL;\n    } else {\n        echo 'Failed to update ' . $template_slug . ' template: ' . $saved_id->get_error_message() . PHP_EOL;\n    }\n}"
    }
  ]
}
```



- **Plugin bundle:** downloads the ZIP stored in `assets/`, unzips it locally, and logs progress to the console.  
- **Activation:** turns on Beats Upload Player before any content renders and then switches to the Twenty Twenty-Five block theme.  
- **Environment flag:** `WP_ENVIRONMENT_TYPE`, `WP_PLAYGROUND`, and `IS_PLAYGROUND` are defined in `wp-config.php` so the plugin knows it’s running inside Playground and seeds the demo `beats.json` library automatically.  
- **Template override:** the blueprint overwrites the Twenty Twenty-Five `home`, `front-page`, and `index` templates with a lightweight block that pulls in the stock header/footer and drops four shortcode blocks (`[beats_category_search]`, `[beats_visualizer]`, `[beats_display_home]`, `[beats_global_player]`) in between, so the Beats UI renders instantly on the homepage and is editable inside the Site Editor.  
- **Landing page:** no pages are created—when Playground hits `/`, the updated front-page template already contains the Beats markup, so the header/nav/footer stay intact and the Beats JS instantly finds `#beats-wrapper`.

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

## 🪲 Debug Logging (optional)

The public JS files no longer spam the console by default. If you need verbose tracing, set `window.BEATS_DEBUG = true;` in the browser console (run it before the page reloads so the scripts read the flag) and refresh—both `beats-loader.js` and `beats-player.js` will start emitting the `[Beats Debug]` messages again. Leave the flag unset/false for a clean, production-friendly console.

---

## 🤝 Feedback

Questions, bugs, or feature ideas? Open an issue or reach out via https://www.crystalthedeveloper.ca. Thanks for testing Beats in Playground!
