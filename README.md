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
    "wp": "latest"
  },
  "steps": [
    {
      "step": "runPHP",
      "code": "echo 'Starting Beats Playground bootstrap...' . PHP_EOL;"
    },
    {
      "step": "unzip",
      "zipFile": {
        "resource": "url",
        "url": "https://raw.githubusercontent.com/crystalthedeveloper/wordpress-beats-playground/main/assets/beats-upload-player.zip"
      },
      "extractToPath": "/wordpress/wp-content/plugins/"
    },
    {
      "step": "runPHP",
      "code": "echo 'Plugin files extracted to wp-content/plugins.' . PHP_EOL;"
    },
    {
      "step": "activatePlugin",
      "pluginPath": "beats-upload-player/beats-upload-player.php"
    },
    {
      "step": "runPHP",
      "code": "echo 'Beats Upload Player activated.' . PHP_EOL;"
    },
    {
      "step": "runPHP",
      "code": "require_once ABSPATH . 'wp-admin/includes/post.php';\n\n$template_contents = <<<'HTML'\n<!-- wp:template-part {\"slug\":\"header\",\"tagName\":\"header\"} /-->\n<!-- wp:group {\"tagName\":\"main\",\"align\":\"full\",\"style\":{\"spacing\":{\"padding\":{\"top\":\"0\",\"bottom\":\"0\"}}}} -->\n<main class=\"wp-block-group alignfull\" style=\"padding-top:0;padding-bottom:0\">\n<!-- wp:post-content {\"layout\":{\"type\":\"constrained\"}} /-->\n</main>\n<!-- /wp:group -->\n<!-- wp:template-part {\"slug\":\"footer\",\"tagName\":\"footer\"} /-->\nHTML;\n\n$theme       = wp_get_theme();\n$theme_slug  = $theme->get_stylesheet();\n$template_id = sprintf( '%s//front-page', $theme_slug );\n\n$template_args = array(\n    'post_title'   => 'Custom Front Page',\n    'post_name'    => $template_id,\n    'post_status'  => 'publish',\n    'post_type'    => 'wp_template',\n    'post_content' => $template_contents,\n    'meta_input'   => array(\n        'origin' => 'custom'\n    )\n);\n\n$existing_template = get_page_by_path( $template_id, OBJECT, 'wp_template' );\nif ( $existing_template ) {\n    $template_args['ID'] = $existing_template->ID;\n    $saved_id            = wp_update_post( $template_args );\n} else {\n    $saved_id = wp_insert_post( $template_args );\n}\n\nif ( ! is_wp_error( $saved_id ) ) {\n    wp_set_post_terms( $saved_id, $theme_slug, 'wp_theme' );\n    echo 'Stored custom front-page template in the database (post ID ' . $saved_id . ').' . PHP_EOL;\n} else {\n    echo 'Failed to store custom front-page template: ' . $saved_id->get_error_message() . PHP_EOL;\n}"
    },
    {
      "step": "runPHP",
      "code": "require_once ABSPATH . 'wp-admin/includes/post.php';\nrequire_once ABSPATH . 'wp-admin/includes/misc.php';\nrequire_once ABSPATH . 'wp-admin/includes/rewrite.php';\n\n$content = <<<'HTML'\n<!-- wp:group {\"tagName\":\"main\",\"align\":\"full\",\"style\":{\"spacing\":{\"padding\":{\"top\":\"30px\",\"right\":\"20px\",\"bottom\":\"40px\",\"left\":\"20px\"}}}} -->\n<main class=\"wp-block-group alignfull\" style=\"padding-top:30px;padding-right:20px;padding-bottom:40px;padding-left:20px\">\n<!-- wp:group {\"align\":\"wide\",\"anchor\":\"beats-wrapper\",\"style\":{\"spacing\":{\"blockGap\":\"18px\",\"margin\":{\"top\":\"0\",\"bottom\":\"0\"},\"padding\":{\"top\":\"20px\",\"right\":\"20px\",\"bottom\":\"20px\",\"left\":\"20px\"}},\"border\":{\"radius\":\"16px\",\"color\":\"#f1f1f1\",\"width\":\"1px\"}},\"layout\":{\"type\":\"constrained\"}} -->\n<div class=\"wp-block-group alignwide\" id=\"beats-wrapper\" style=\"border-color:#f1f1f1;border-width:1px;border-radius:16px;margin-top:0;margin-bottom:0;padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px\">\n<!-- wp:heading {\"textAlign\":\"center\"} -->\n<h2 class=\"wp-block-heading has-text-align-center\">Beats</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph {\"align\":\"center\"} -->\n<p class=\"has-text-align-center\">Preview every Beats shortcode below.</p>\n<!-- /wp:paragraph -->\n<!-- wp:group {\"style\":{\"spacing\":{\"blockGap\":\"12px\"}},\"layout\":{\"type\":\"constrained\"}} -->\n<div class=\"wp-block-group\" style=\"gap:12px\">\n<!-- wp:shortcode -->[beats_category_search]<!-- /wp:shortcode -->\n<!-- wp:shortcode -->[beats_visualizer]<!-- /wp:shortcode -->\n<!-- wp:shortcode -->[beats_display_home]<!-- /wp:shortcode -->\n<!-- wp:shortcode -->[beats_global_player]<!-- /wp:shortcode -->\n</div>\n<!-- /wp:group -->\n</div>\n<!-- /wp:group -->\n</main>\n<!-- /wp:group -->\nHTML;\n\n$page_args = array(\n    'post_title'   => 'Beats',\n    'post_name'    => 'beats-playground',\n    'post_status'  => 'publish',\n    'post_type'    => 'page',\n    'post_content' => $content\n);\n\n$existing = get_page_by_path( $page_args['post_name'], OBJECT, 'page' );\nif ( $existing ) {\n    $page_args['ID'] = $existing->ID;\n    $page_id = wp_update_post( $page_args );\n} else {\n    $page_id = wp_insert_post( $page_args );\n}\n\nif ( is_wp_error( $page_id ) ) {\n    echo 'Failed to create Beats homepage: ' . $page_id->get_error_message() . PHP_EOL;\n} else {\n    update_option( 'show_on_front', 'page' );\n    update_option( 'page_on_front', $page_id );\n    update_option( 'page_for_posts', 0 );\n    flush_rewrite_rules( true );\n    $prime_urls = array(\n        home_url( '/?t=' . time() ),\n        home_url( '/beats-playground/?t=' . ( time() + 1 ) )\n    );\n    foreach ( $prime_urls as $url ) {\n        $response = wp_remote_get( $url );\n        if ( is_wp_error( $response ) ) {\n            echo 'Priming request failed for ' . $url . ': ' . $response->get_error_message() . PHP_EOL;\n        } else {\n            echo 'Primed ' . $url . PHP_EOL;\n        }\n    }\n    echo 'Beats homepage ready at /beats-playground/ (ID ' . $page_id . ').' . PHP_EOL;\n}"
    },
    {
      "step": "runPHP",
      "code": "echo 'Beats Playground bootstrap complete.' . PHP_EOL;"
    }
  ]
}
```

- **Plugin bundle:** downloads the ZIP stored in `assets/`, unzips it locally, and logs progress to the console.  
- **Activation:** turns on Beats Upload Player before any content renders and logs confirmation.  
- **Template override:** the default Twenty Twenty-Four `front-page` template is replaced with a slim header/main/footer wrapper so the Beats shortcodes sit directly in the page body.  
- **Landing page:** a `runPHP` step creates/updates the Beats homepage (complete with the `#beats-wrapper` anchor), sets it as the homepage, primes both `/` and `/beats-playground/`, and logs the results so the Playground shell hits the live page instead of a cached 404.

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
