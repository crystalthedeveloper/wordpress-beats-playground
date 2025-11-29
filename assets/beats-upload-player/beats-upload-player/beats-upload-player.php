<?php
/*
Plugin Name: Beats Upload Player
Plugin URI: https://www.crystalthedeveloper.ca
Description: Upload and manage beats, with a visual player.
Version: 1.0.0
Author: Crystal The Developer Inc.
Author URI: https://www.crystalthedeveloper.ca
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: beats-upload-player
*/

/**
 * Bootstrap file that registers every component (shortcodes, AJAX handlers,
 * admin pages) and wires shared helpers so beats can be uploaded, listed,
 * and played from the front-end experience.
 */

if (!defined('ABSPATH')) exit;

if (!defined('BEATS_UPLOAD_PLAYER_VERSION')) {
  define('BEATS_UPLOAD_PLAYER_VERSION', '1.5.0');
}

if (!function_exists('beats_normalize_block_markup')) {
  function beats_normalize_block_markup($markup) {
    if (function_exists('parse_blocks') && function_exists('serialize_blocks')) {
      $blocks = parse_blocks($markup);
      if (!empty($blocks)) {
        return serialize_blocks($blocks);
      }
    }

    return $markup;
  }
}

// === Includes ===
require_once plugin_dir_path(__FILE__) . 'includes/beats-categories.php';
require_once plugin_dir_path(__FILE__) . 'includes/beats-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/beats-search-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/beats-ajax.php';
require_once plugin_dir_path(__FILE__) . 'includes/beats-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/beats-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/beats-category-search.php';
require_once plugin_dir_path(__FILE__) . 'includes/beats-visualizer.php';
require_once plugin_dir_path(__FILE__) . 'includes/beats-global-player.php';

// Ensure storage directories/files exist early.
add_action('plugins_loaded', 'beats_prepare_storage', 5);
add_action('init', 'beats_prepare_storage', 1);
register_activation_hook(__FILE__, 'beats_prepare_storage');

add_action('plugins_loaded', 'beats_prime_data', 20);

// Guard against legacy fallback injections that duplicated beats_cltd_display_home output.
add_action('plugins_loaded', function () {
  if (function_exists('beats_maybe_buffer_wrapper_output') && has_action('template_redirect', 'beats_maybe_buffer_wrapper_output')) {
    remove_action('template_redirect', 'beats_maybe_buffer_wrapper_output', 0);
  }
}, 25);

function beats_is_playground_env() {
  if (defined('WP_PLAYGROUND') && WP_PLAYGROUND) {
    return true;
  }
  if (defined('IS_PLAYGROUND') && IS_PLAYGROUND) {
    return true;
  }
  if (function_exists('wp_get_environment_type') && wp_get_environment_type() === 'playground') {
    return true;
  }
  return false;
}

function beats_get_demo_page_content() {
  $content = <<<'HTML'
<!-- wp:group {"tagName":"main","align":"full"} -->
<main class="wp-block-group alignfull">
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Preview the Beats library and player below.</p>
<!-- /wp:paragraph -->
<!-- wp:beats/category-search /-->
<!-- wp:beats/visualizer /-->
<!-- wp:html -->
<div id="beats-wrapper" class="beats-wrapper" style="padding-left:20px;padding-right:0">
<!-- wp:group {"align":"full","style":{"spacing":{"blockGap":"16px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group alignfull" style="gap:16px">
<!-- wp:beats/display-home /-->
<!-- wp:beats/global-player /-->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:html -->
</main>
<!-- /wp:group -->
HTML;

  return beats_normalize_block_markup($content);
}

function beats_get_upload_page_content() {
  $content = <<<'HTML'
<!-- wp:group {"tagName":"main","align":"full"} -->
<main class="wp-block-group alignfull">
<!-- wp:html -->
<div class="beats-upload-wrapper">
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Share a new beat with the Playground uploader.</p>
<!-- /wp:paragraph -->
<!-- wp:beats/upload-form /-->
</div>
<!-- /wp:html -->
</main>
<!-- /wp:group -->
HTML;

  return beats_normalize_block_markup($content);
}

function beats_seed_demo_front_page() {
  static $seeded = false;
  if ($seeded || !beats_is_playground_env()) {
    return;
  }
  $seeded = true;

  $page_args = [
    'post_title'   => 'Beats Demo',
    'post_name'    => 'beats-demo',
    'post_status'  => 'publish',
    'post_type'    => 'page',
    'post_content' => beats_get_demo_page_content(),
    'post_author'  => get_current_user_id() ?: 1,
  ];

  $existing_page = get_page_by_path('beats-demo');
  if ($existing_page) {
    $page_args['ID'] = $existing_page->ID;
    $page_id = wp_update_post($page_args);
  } else {
    $page_id = wp_insert_post($page_args);
  }

  if (!is_wp_error($page_id)) {
    update_option('show_on_front', 'page');
    update_option('page_on_front', $page_id);
    update_option('page_for_posts', 0);
  }

  beats_seed_upload_page();
}
register_activation_hook(__FILE__, 'beats_seed_demo_front_page');
add_action('init', 'beats_seed_demo_front_page', 25);

function beats_seed_upload_page() {
  $upload_args = [
    'post_title'   => 'Upload',
    'post_name'    => 'upload',
    'post_status'  => 'publish',
    'post_type'    => 'page',
    'post_content' => beats_get_upload_page_content(),
    'post_author'  => get_current_user_id() ?: 1,
  ];

  $upload_page = get_page_by_path('upload');
  if (!$upload_page) {
    $sample = get_page_by_path('sample-page');
    if ($sample) {
      $upload_args['ID'] = $sample->ID;
      wp_update_post($upload_args);
      return $sample->ID;
    }
    return wp_insert_post($upload_args);
  }

  $upload_args['ID'] = $upload_page->ID;
  return wp_update_post($upload_args);
}

function beats_filter_show_on_front_option($value) {
  if (beats_is_playground_env()) {
    return 'page';
  }
  return $value;
}

function beats_filter_page_on_front_option($value) {
  if (!beats_is_playground_env()) {
    return $value;
  }
  $page = get_page_by_path('beats-demo');
  if ($page) {
    return $page->ID;
  }
  return $value;
}

function beats_filter_page_for_posts_option($value) {
  if (beats_is_playground_env()) {
    return 0;
  }
  return $value;
}

add_filter('option_show_on_front', 'beats_filter_show_on_front_option');
add_filter('option_page_on_front', 'beats_filter_page_on_front_option');
add_filter('option_page_for_posts', 'beats_filter_page_for_posts_option');

function beats_hide_front_page_title_block( $pre_render, $parsed_block ) {
  if ( is_admin() ) {
    return $pre_render;
  }

  if ( ( $parsed_block['blockName'] ?? '' ) === 'core/post-title' && ( is_front_page() || is_page( 'upload' ) ) ) {
    return '';
  }

  return $pre_render;
}
add_filter( 'pre_render_block', 'beats_hide_front_page_title_block', 10, 2 );

function beats_register_core_blocks() {
  if ( ! function_exists( 'register_block_type_from_metadata' ) ) {
    return;
  }

  $map = array(
    'category-search' => 'beats_cltd_category_search',
    'visualizer'     => 'beats_cltd_visualizer',
    'display-home'   => 'beats_cltd_display_home',
    'global-player'  => 'beats_cltd_global_player',
    'upload-form'    => 'beats_cltd_upload_form',
  );

  foreach ( $map as $slug => $shortcode ) {
    register_block_type_from_metadata(
      __DIR__ . '/blocks/' . $slug,
      array(
        'render_callback' => function( $attributes, $content, $block ) use ( $shortcode ) {
          return do_shortcode( '[' . $shortcode . ']' );
        },
      )
    );
  }
}
add_action( 'init', 'beats_register_core_blocks', 20 );

function beats_enqueue_block_editor_assets() {
  $dir = plugin_dir_url( __FILE__ );
  $previews = array(
    'beats/category-search' => $dir . 'public/images/previews/category-search-preview.jpeg',
    'beats/visualizer'      => $dir . 'public/images/previews/visualizer-preview.jpeg',
    'beats/display-home'    => $dir . 'public/images/previews/display-home-preview.jpeg',
    'beats/global-player'   => $dir . 'public/images/previews/global-player-preview.jpeg',
    'beats/upload-form'     => $dir . 'public/images/previews/upload-form-preview.jpeg',
  );

  wp_add_inline_script(
    'wp-blocks',
    'window.BeatsBlockPreviews = ' . wp_json_encode( $previews ) . ';',
    'after'
  );

  $css = '.beats-block-preview-wrapper{display:flex;align-items:center;justify-content:center;min-height:140px;background:#fbfbfb;border:1px dashed #d0d0d0;border-radius:14px;padding:12px;text-align:center;font-size:14px;color:#555;}.beats-block-preview-wrapper img{max-width:100%;height:auto;display:block;}';
  wp_add_inline_style( 'wp-edit-blocks', $css );
}
add_action( 'enqueue_block_editor_assets', 'beats_enqueue_block_editor_assets' );

function beats_register_block_category( $categories, $editor_context = null ) {
  $exists = wp_list_pluck( $categories, 'slug' );
  if ( in_array( 'cltd', $exists, true ) ) {
    return $categories;
  }

  $icon = '<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M4 10a6 6 0 0 1 12 0v5a2 2 0 0 1-2 2h-1a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1h1V9a4 4 0 0 0-8 0v2h1a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1H6a2 2 0 0 1-2-2z"/></svg>';

  $new = array(
    array(
      'slug'  => 'cltd',
      'title' => __( 'CLTD Blocks', 'beats-upload-player' ),
      'icon'  => $icon,
    ),
  );

  return array_merge( $new, $categories );
}
add_filter( 'block_categories_all', 'beats_register_block_category', 10, 2 );

// === Public Assets ===
function beats_register_public_assets() {
  $dir = plugin_dir_url(__FILE__);
  $version = BEATS_UPLOAD_PLAYER_VERSION;

  wp_register_style('beats-upload-style', $dir . 'public/css/beats-upload.css', [], $version);
  wp_register_style('beats-category-search-style', $dir . 'public/css/beats-category-search.css', [], $version);
  wp_register_style('beats-visualizer-style', $dir . 'public/css/beats-visualizer.css', [], $version);

  wp_register_script('beats-loader', $dir . 'public/js/beats-loader.js', [], $version, true);
  wp_register_script('beats-player', $dir . 'public/js/beats-player.js', [], $version, true);
  wp_register_script('beats-visualizer', $dir . 'public/js/beats-visualizer.js', [], $version, true);
  wp_add_inline_script('beats-visualizer', "
    (function() {
      function startVisualizer() {
        if (typeof BeatsVisualizerInit === 'function') {
          BeatsVisualizerInit();
        }
      }
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startVisualizer);
      } else {
        startVisualizer();
      }
    })();
  ", 'after');

  wp_localize_script('beats-loader', 'beats_ajax', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce'    => wp_create_nonce('beats-load'),
  ]);
}
add_action('wp_enqueue_scripts', 'beats_register_public_assets');

// === Admin Assets ===
function beats_enqueue_admin_assets($hook) {
  $valid_hooks = [
    'toplevel_page_beats-manager',
    'beats-manager_page_beats-manager-upload',
    'beats-manager_page_beats-manager-library',
    'beats-manager_page_beats-manager-visualizer',
  ];

  if (!in_array($hook, $valid_hooks, true)) {
    return;
  }

  $dir = plugin_dir_url(__FILE__);
  $paths = beats_paths();

  wp_enqueue_style('beats-admin-style', $dir . 'admin/css/admin.css', [], BEATS_UPLOAD_PLAYER_VERSION);
  wp_enqueue_script('beats-admin-script', $dir . 'admin/js/admin.js', ['jquery'], BEATS_UPLOAD_PLAYER_VERSION, true);

  wp_localize_script('beats-admin-script', 'BeatsAdmin', [
    'ajax'       => admin_url('admin-ajax.php'),
    'nonce'      => wp_create_nonce('beats-admin'),
    'baseUrl'    => $paths['url'],
    'categories' => beats_get_categories(),
    'defaultArt' => $dir . 'public/images/default-art.webp',
    'uploadLink' => admin_url('admin.php?page=beats-manager#beats-admin-upload'),
  ]);
}
add_action('admin_enqueue_scripts', 'beats_enqueue_admin_assets');
