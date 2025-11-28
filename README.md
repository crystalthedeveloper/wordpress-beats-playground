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

### VS Code Playground Server (local)

Prefer running the [WordPress Playground VS Code extension](https://marketplace.visualstudio.com/items?itemName=wordpress.playground)? Open this repo in VS Code, start the Playground server (the sidebar screenshot above), and visit `http://localhost:8881`. When the plugin activates it detects the `playground` environment (`WP_PLAYGROUND`, `IS_PLAYGROUND`, or `wp_get_environment_type()`), auto-creates the **Beats Demo** page, and sets it as the static home—no blueprint URL required.

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
      "code": "require_once ABSPATH . 'wp-admin/includes/post.php';
require_once ABSPATH . 'wp-admin/includes/theme.php';
require_once ABSPATH . 'wp-admin/includes/template.php';

error_log('[Beats Blueprint] runPHP start');
$theme = wp_get_theme();
$theme_slug = $theme->get_stylesheet();
error_log('[Beats Blueprint] theme stylesheet: ' . $theme_slug);
error_log('[Beats Blueprint] show_on_front before: ' . var_export(get_option('show_on_front', 'unset'), true));
error_log('[Beats Blueprint] page_on_front before: ' . var_export(get_option('page_on_front', 'unset'), true));

if ( function_exists( 'beats_prime_data' ) ) {
    beats_prime_data();
    error_log('[Beats Blueprint] beats_prime_data executed');
} elseif ( function_exists( 'beats_seed_playground_demo' ) ) {
    beats_seed_playground_demo();
    error_log('[Beats Blueprint] beats_seed_playground_demo executed');
} else {
    error_log('[Beats Blueprint] no seeding function available');
}

$beats_content = <<<HTML
<!-- wp:group {\"tagName\":\"main\",\"align\":\"full\",\"style\":{\"spacing\":{\"padding\":{\"top\":\"30px\",\"right\":\"20px\",\"bottom\":\"40px\",\"left\":\"20px\"}}}} -->
<main class=\"wp-block-group alignfull\" style=\"padding-top:30px;padding-right:20px;padding-bottom:40px;padding-left:20px\">
<!-- wp:group {\"align\":\"full\",\"anchor\":\"beats-wrapper\",\"style\":{\"spacing\":{\"blockGap\":\"18px\",\"margin\":{\"top\":\"0\",\"bottom\":\"0\"},\"padding\":{\"top\":\"24px\",\"right\":\"24px\",\"bottom\":\"24px\",\"left\":\"24px\"}},\"border\":{\"radius\":\"16px\"}},\"layout\":{\"type\":\"constrained\"}} -->
<div class=\"wp-block-group alignfull\" id=\"beats-wrapper\" style=\"border:none;border-radius:16px;margin-top:0;margin-bottom:0;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px\">
<!-- wp:paragraph {\"align\":\"center\"} -->
<p class=\"has-text-align-center\">Preview the Beats library and player below.</p>
<!-- /wp:paragraph -->
<!-- wp:shortcode -->[beats_cltd_category_search]<!-- /wp:shortcode -->
<!-- wp:group {\"align\":\"full\",\"style\":{\"spacing\":{\"blockGap\":\"16px\"}},\"layout\":{\"type\":\"flex\",\"orientation\":\"vertical\"}} -->
<div class=\"wp-block-group alignfull\" style=\"gap:16px\">
<!-- wp:shortcode -->[beats_cltd_visualizer]<!-- /wp:shortcode -->
<!-- wp:shortcode -->[beats_cltd_display_home]<!-- /wp:shortcode -->
<!-- wp:shortcode -->[beats_cltd_global_player]<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</main>
<!-- /wp:group -->
HTML;

$page_args = array(
    'post_title'   => 'Beats Demo',
    'post_name'    => 'beats-demo',
    'post_status'  => 'publish',
    'post_type'    => 'page',
    'post_content' => $beats_content,
    'post_author'  => get_current_user_id() ?: 1
);

$existing_page = get_page_by_path( 'beats-demo' );
if ( $existing_page ) {
    $page_args['ID'] = $existing_page->ID;
    $page_id         = wp_update_post( $page_args );
    error_log('[Beats Blueprint] updated existing Beats Demo page ID ' . $existing_page->ID);
} else {
    $page_id = wp_insert_post( $page_args );
    error_log('[Beats Blueprint] inserted Beats Demo page ID ' . $page_id);
}

if ( ! is_wp_error( $page_id ) ) {
    update_option( 'show_on_front', 'page' );
    update_option( 'page_on_front', $page_id );
    update_option( 'page_for_posts', 0 );
    error_log('[Beats Blueprint] show_on_front after: ' . var_export(get_option('show_on_front', 'unset'), true));
    error_log('[Beats Blueprint] page_on_front after: ' . var_export(get_option('page_on_front', 'unset'), true));
    error_log('[Beats Blueprint] page_for_posts after: ' . var_export(get_option('page_for_posts', 'unset'), true));
    error_log('[Beats Blueprint] Beats Demo permalink: ' . get_permalink( $page_id ));

    flush_rewrite_rules( true );
    error_log('[Beats Blueprint] rewrite rules flushed');

    $refresh_url = add_query_arg( 'refresh', time(), home_url( '/' ) );
    error_log('[Beats Blueprint] refresh url: ' . $refresh_url);
    $response = wp_remote_get( $refresh_url );
    if ( is_wp_error( $response ) ) {
        error_log('[Beats Blueprint] refresh request failed: ' . $response->get_error_message());
    } else {
        error_log('[Beats Blueprint] refresh response code: ' . wp_remote_retrieve_response_code( $response ));
    }

    echo 'Beats Demo front page ready (post ID ' . $page_id . ').' . PHP_EOL;
} else {
    error_log('[Beats Blueprint] failed to create Beats Demo page: ' . $page_id->get_error_message());
    echo 'Failed to create Beats Demo page: ' . $page_id->get_error_message() . PHP_EOL;
}

$upload_content = <<<HTML
<!-- wp:group {\"tagName\":\"main\",\"align\":\"full\",\"style\":{\"spacing\":{\"padding\":{\"top\":\"30px\",\"right\":\"20px\",\"bottom\":\"40px\",\"left\":\"20px\"}}}} -->
<main class=\"wp-block-group alignfull\" style=\"padding-top:30px;padding-right:20px;padding-bottom:40px;padding-left:20px\">
<!-- wp:group {\"align\":\"full\",\"style\":{\"spacing\":{\"blockGap\":\"18px\",\"margin\":{\"top\":\"0\",\"bottom\":\"0\"},\"padding\":{\"top\":\"24px\",\"right\":\"24px\",\"bottom\":\"24px\",\"left\":\"24px\"}},\"border\":{\"radius\":\"16px\"}},\"layout\":{\"type\":\"constrained\"}} -->
<div class=\"wp-block-group alignfull\" style=\"border:none;border-radius:16px;margin-top:0;margin-bottom:0;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px\">
<!-- wp:heading {\"textAlign\":\"center\"} -->
<h2 class=\"wp-block-heading has-text-align-center\">Upload</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {\"align\":\"center\"} -->
<p class=\"has-text-align-center\">Share a new beat with the Playground uploader.</p>
<!-- /wp:paragraph -->
<!-- wp:shortcode -->[beats_cltd_upload_form]<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
</main>
<!-- /wp:group -->
HTML;

$upload_args = array(
    'post_title'   => 'Upload',
    'post_name'    => 'upload',
    'post_status'  => 'publish',
    'post_type'    => 'page',
    'post_content' => $upload_content,
    'post_author'  => get_current_user_id() ?: 1
);

$upload_page = get_page_by_path( 'upload' );
if ( ! $upload_page ) {
    $sample_page = get_page_by_path( 'sample-page' );
    if ( $sample_page ) {
        $upload_args['ID'] = $sample_page->ID;
        $upload_id         = wp_update_post( $upload_args );
        error_log('[Beats Blueprint] repurposed Sample Page as Upload page ID ' . $sample_page->ID);
    } else {
        $upload_id = wp_insert_post( $upload_args );
        error_log('[Beats Blueprint] inserted Upload page ID ' . $upload_id);
    }
} else {
    $upload_args['ID'] = $upload_page->ID;
    $upload_id         = wp_update_post( $upload_args );
    error_log('[Beats Blueprint] updated existing Upload page ID ' . $upload_page->ID);
}

if ( is_wp_error( $upload_id ) ) {
    error_log('[Beats Blueprint] Failed to prepare Upload page: ' . $upload_id->get_error_message());
} else {
    error_log('[Beats Blueprint] Upload page ready (post ID ' . $upload_id . ').');
}

add_filter( 'pre_render_block', function( $pre_render, $parsed_block ) {
    if ( ( $parsed_block['blockName'] ?? '' ) === 'core/post-title' && is_front_page() ) {
        return '';
    }

    return $pre_render;
}, 10, 2 );

add_action( 'wp_head', function() {
    echo '<style>#beats-wrapper{border:none!important;box-shadow:none!important;}#beats-wrapper .wp-block-group{border:none!important;box-shadow:none!important;}.wp-block-post-title,h1,.wp-block-site-title,h1.wp-block-site-title{display:none!important;}</style>';
} );

error_log('[Beats Blueprint] runPHP complete');

update_option( 'blogname', 'Beats' );
"
    }
  ]
}
```



- **Plugin bundle:** downloads the ZIP stored in `assets/`, unzips it locally, and logs progress to the console.  
- **Activation:** turns on Beats Upload Player before any content renders and then switches to the Twenty Twenty-Five block theme.  
- **Environment flag:** `WP_ENVIRONMENT_TYPE`, `WP_PLAYGROUND`, and `IS_PLAYGROUND` are defined in `wp-config.php` so the plugin knows it’s running inside Playground and seeds the demo `beats.json` library automatically.  
- **Template override:** the blueprint now creates a physical “Beats Demo” page that contains the shortcode blocks (`[beats_category_search]`, `[beats_visualizer]`, `[beats_display_home]`, `[beats_global_player]`) and assigns it as the static front page via `page_on_front`, so the Beats UI executes immediately on load and is editable like any other page.  
- **Upload page:** the default Sample Page is converted into an “Upload” page that renders `[beats_upload_form]`, giving the top navigation an immediate link to the uploader workflow.
- **Landing page:** the blueprint creates/publishes the “Beats Demo” page and sets it as `page_on_front`, so when Playground hits `/` the shortcode blocks render immediately and Beats JS instantly finds `#beats-wrapper`.

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
