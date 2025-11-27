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
      "step": "activateTheme",
      "themeFolderName": "twentytwentyfive"
    },
    {
      "step": "runPHP",
      "code": "require_once ABSPATH . 'wp-admin/includes/post.php';\nrequire_once ABSPATH . 'wp-admin/includes/theme.php';\n\n$template_path = get_theme_file_path( 'templates/front-page.html' );\n$template_html = file_exists( $template_path ) ? file_get_contents( $template_path ) : '';\n\n$category_html      = do_shortcode( '[beats_category_search]' );\n$visualizer_html    = do_shortcode( '[beats_visualizer]' );\n$display_home_html  = do_shortcode( '[beats_display_home]' );\n$global_player_html = do_shortcode( '[beats_global_player]' );\n\n$beats_section = <<<'HTML'\n<!-- wp:group {\"tagName\":\"main\",\"align\":\"full\",\"style\":{\"spacing\":{\"padding\":{\"top\":\"30px\",\"right\":\"20px\",\"bottom\":\"40px\",\"left\":\"20px\"}}}} -->\n<main class=\"wp-block-group alignfull\" style=\"padding-top:30px;padding-right:20px;padding-bottom:40px;padding-left:20px\">\n<!-- wp:group {\"align\":\"wide\",\"anchor\":\"beats-wrapper\",\"style\":{\"spacing\":{\"blockGap\":\"18px\",\"margin\":{\"top\":\"0\",\"bottom\":\"0\"},\"padding\":{\"top\":\"20px\",\"right\":\"20px\",\"bottom\":\"20px\",\"left\":\"20px\"}},\"border\":{\"radius\":\"16px\",\"color\":\"#f1f1f1\",\"width\":\"1px\"}},\"layout\":{\"type\":\"constrained\"}} -->\n<div class=\"wp-block-group alignwide\" id=\"beats-wrapper\" style=\"border-color:#f1f1f1;border-width:1px;border-radius:16px;margin-top:0;margin-bottom:0;padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px\">\n<!-- wp:heading {\"textAlign\":\"center\"} -->\n<h2 class=\"wp-block-heading has-text-align-center\">Beats</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph {\"align\":\"center\"} -->\n<p class=\"has-text-align-center\">Preview every Beats shortcode below.</p>\n<!-- /wp:paragraph -->\n<!-- wp:group {\"style\":{\"spacing\":{\"blockGap\":\"12px\"}},\"layout\":{\"type\":\"constrained\"}} -->\n<div class=\"wp-block-group\" style=\"gap:12px\">\n$category_html\n$visualizer_html\n$display_home_html\n$global_player_html\n</div>\n<!-- /wp:group -->\n</div>\n<!-- /wp:group -->\n</main>\n<!-- /wp:group -->\nHTML;\n\n$pattern = '/<!-- wp:group {\"tagName\":\"main\".*?<!-- \\/wp:group -->/s';\nif ( $template_html && preg_match( $pattern, $template_html ) ) {\n    $updated_template = preg_replace( $pattern, $beats_section, $template_html, 1 );\n} else {\n    $updated_template = $beats_section;\n}\n\n$theme       = wp_get_theme();\n$theme_slug  = $theme->get_stylesheet();\n$template_id = sprintf( '%s//front-page', $theme_slug );\n\n$template_args = array(\n    'post_title'   => 'Front Page',\n    'post_name'    => $template_id,\n    'post_status'  => 'publish',\n    'post_type'    => 'wp_template',\n    'post_content' => $updated_template,\n    'meta_input'   => array(\n        'origin' => 'custom'\n    )\n);\n\n$existing_template = get_page_by_path( $template_id, OBJECT, 'wp_template' );\nif ( $existing_template ) {\n    $template_args['ID'] = $existing_template->ID;\n    $saved_id            = wp_update_post( $template_args );\n} else {\n    $saved_id = wp_insert_post( $template_args );\n}\n\nif ( ! is_wp_error( $saved_id ) ) {\n    wp_set_post_terms( $saved_id, $theme_slug, 'wp_theme' );\n    echo 'Front-page template updated (post ID ' . $saved_id . ').' . PHP_EOL;\n} else {\n    echo 'Failed to update front-page template: ' . $saved_id->get_error_message() . PHP_EOL;\n}"
    },
    {
      "step": "runPHP",
      "code": "echo 'Beats Playground bootstrap complete.' . PHP_EOL;"
    }
  ]
}
```


- **Plugin bundle:** downloads the ZIP stored in `assets/`, unzips it locally, and logs progress to the console.  
- **Activation:** turns on Beats Upload Player before any content renders and then switches to the Twenty Twenty-Five block theme.  
- **Environment flag:** `WP_ENVIRONMENT_TYPE`, `WP_PLAYGROUND`, and `IS_PLAYGROUND` are defined in `wp-config.php` so the plugin knows it’s running inside Playground and seeds the demo `beats.json` library automatically.  
- **Template override:** the stock `front-page` template is fetched from the theme, its `<main>` block is replaced with the rendered shortcode HTML (generated via `do_shortcode()` during the bootstrap), and the modified template is saved as a `wp_template` post tied to Twenty Twenty-Five.  
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

---

## 🤝 Feedback

Questions, bugs, or feature ideas? Open an issue or reach out via https://www.crystalthedeveloper.ca. Thanks for testing Beats in Playground!
