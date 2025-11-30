<?php
if (!defined('ABSPATH')) exit;

/**
 * Core utility helpers: file/path bootstrap, JSON persistence, demo seeding,
 * and grouping logic used by AJAX + shortcodes.
 */

/**
 * Paths, File Handling, JSON Helpers
 */
function beats_paths() {
  static $paths = null;
  if ($paths !== null) {
    return $paths;
  }

  $uploads = wp_upload_dir();
  $base  = trailingslashit($uploads['basedir']) . 'beats/';
  $url   = trailingslashit($uploads['baseurl']) . 'beats/';
  $audio = $base . 'audio/';
  $img   = $base . 'images/';

  $paths = [
    'base' => $base,
    'url' => $url,
    'audio_dir' => $audio,
    'img_dir' => $img,
    'json' => $base . 'beats.json'
  ];

  beats_ensure_storage_locations($paths);
  return $paths;
}

if (!function_exists('beats_ensure_storage_locations')) {
  function beats_write_protection_files( $dir ) {
    if ( ! is_dir( $dir ) ) {
      return;
    }

    $htaccess = trailingslashit( $dir ) . '.htaccess';
    $index    = trailingslashit( $dir ) . 'index.html';

    if ( ! file_exists( $htaccess ) && wp_is_writable( $dir ) ) {
      $rules = "Options -Indexes\n<Files *>\n  <IfModule mod_php.c>\n    php_flag engine off\n  </IfModule>\n</Files>\n";
      @file_put_contents( $htaccess, $rules );
    }

    if ( ! file_exists( $index ) ) {
      @file_put_contents( $index, '<!-- Silence is golden. -->' );
    }
  }

  function beats_ensure_storage_locations($paths) {
    $dirs = [$paths['base'], $paths['audio_dir'], $paths['img_dir']];
    foreach ($dirs as $dir) {
      if (!is_dir($dir)) {
        wp_mkdir_p($dir);
      }
      if (is_dir($dir) && !is_writable($dir)) {
        @chmod($dir, 0755);
      }
      beats_write_protection_files( $dir );
    }

    $json_dir = dirname($paths['json']);
    if (!is_dir($json_dir)) {
      wp_mkdir_p($json_dir);
    }
    if (!file_exists($paths['json'])) {
      @file_put_contents($paths['json'], json_encode([], JSON_PRETTY_PRINT));
    }
    if (file_exists($paths['json']) && !is_writable($paths['json'])) {
      @chmod($paths['json'], 0644);
    }
  }
}

if (!function_exists('beats_prepare_storage')) {
  function beats_prepare_storage() {
    beats_paths(); // ensures directories/files exist.
  }
}

if (!function_exists('beats_prime_data')) {
  function beats_prime_data() {
    beats_paths();
    beats_seed_playground_demo();
    beats_read_json();
    beats_get_categories();
  }
}

add_filter('the_content', 'do_shortcode', 11);
add_filter('render_block', function ($content, $block) {
  if (empty($block['blockName']) || $block['blockName'] !== 'core/shortcode') {
    return $content;
  }

  if (!empty($block['attrs']['text'])) {
    return shortcode_unautop(do_shortcode($block['attrs']['text']));
  }
  if (!empty($block['innerContent'])) {
    return shortcode_unautop(do_shortcode(implode('', $block['innerContent'])));
  }
  if (!empty($block['innerHTML'])) {
    return shortcode_unautop(do_shortcode($block['innerHTML']));
  }

  return shortcode_unautop(do_shortcode($content));
}, 10, 2);

function beats_is_playground() {
  static $flag = null;
  if ($flag !== null) {
    return $flag;
  }

  $flag = (
    (defined('IS_PLAYGROUND') && IS_PLAYGROUND) ||
    (defined('WP_PLAYGROUND') && WP_PLAYGROUND) ||
    (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'playground')
  );

  return $flag;
}

function beats_seed_playground_demo() {
  if (!beats_is_playground()) {
    return;
  }

  $paths = beats_paths();
  $json_file = $paths['json'];
  $existing = [];
  if (file_exists($json_file)) {
    $existing = json_decode(@file_get_contents($json_file), true);
    if (is_array($existing) && !empty($existing)) {
      return;
    }
  }

  $seed_file = plugin_dir_path(__FILE__) . '../resources/beats.json';
  if (!file_exists($seed_file)) {
    return;
  }

  $seed = json_decode(file_get_contents($seed_file), true);
  if (!is_array($seed) || empty($seed)) {
    return;
  }

  $demo_dir   = plugin_dir_path(__FILE__) . '../resources/';
  $public_dir = plugin_dir_path(__FILE__) . '../public/';

  $assets = [];
  foreach ($seed as $beat) {
    if (!empty($beat['file'])) {
      $assets[] = $beat['file'];
    }
    if (!empty($beat['image'])) {
      $assets[] = $beat['image'];
    }
  }

  $assets = array_unique(array_filter($assets));
  foreach ($assets as $asset) {
    $relative = ltrim($asset, '/');
    $target   = $paths['base'] . $relative;
    $target_dir = dirname($target);
    if (!is_dir($target_dir)) {
      wp_mkdir_p($target_dir);
    }
    if (file_exists($target)) {
      continue;
    }

    $candidate_sources = array(
      trailingslashit($demo_dir) . $relative,
      trailingslashit($demo_dir) . basename($relative),
      trailingslashit($public_dir) . $relative,
      trailingslashit($public_dir) . basename($relative)
    );

    foreach ($candidate_sources as $source) {
      if (file_exists($source)) {
        @copy($source, $target);
        break;
      }
    }
  }

  beats_write_json($seed);
}

function beats_read_json() {
  $p = beats_paths();
  $f = $p['json'];
  if (!file_exists($f)) {
    beats_ensure_storage_locations($p);
  }
  $contents = @file_get_contents($f);
  if ($contents === false) {
    // Attempt to recreate the file if it was removed mid-request.
    beats_ensure_storage_locations($p);
    $contents = @file_get_contents($f);
    if ($contents === false) {
      return [];
    }
  }
  $data = json_decode($contents, true);
  if (!is_array($data)) {
    $data = [];
  }

  return $data;
}

function beats_write_json($data) {
  $p = beats_paths();
  beats_ensure_storage_locations($p);
  @file_put_contents($p['json'], json_encode(array_values($data), JSON_PRETTY_PRINT));
}

function beats_sanitize_beat_entry( $beat ) {
  $entry = array(
    'name'     => sanitize_text_field( $beat['name'] ?? '' ),
    'producer' => sanitize_text_field( $beat['producer'] ?? '' ),
    'category' => sanitize_text_field( $beat['category'] ?? '' ),
    'price'    => '',
    'buy_url'  => '',
    'file'     => '',
    'image'    => '',
  );

  $raw_price = isset( $beat['price'] ) ? (float) $beat['price'] : 0;
  if ( $raw_price > 0 ) {
    $entry['price'] = number_format( $raw_price, 2, '.', '' );
  }

  if ( ! empty( $beat['buy_url'] ) ) {
    $entry['buy_url'] = esc_url_raw( $beat['buy_url'] );
  }

  if ( ! empty( $beat['file'] ) ) {
    $entry['file'] = ltrim( (string) $beat['file'], '/' );
  }

  if ( ! empty( $beat['image'] ) ) {
    $entry['image'] = ltrim( (string) $beat['image'], '/' );
  }

  return $entry;
}

function beats_format_upload_error( $error ) {
  if ( ! is_wp_error( $error ) ) {
    return '';
  }

  $messages = array(
    'beats-upload-missing-file' => __( 'No file selected.', 'beats-upload-player' ),
    'beats-upload-error'        => __( 'There was an error uploading the file. Please try again.', 'beats-upload-player' ),
    'beats-upload-size'         => __( 'The uploaded file exceeds the size limit.', 'beats-upload-player' ),
    'beats-upload-mime'         => __( 'The uploaded file type is not allowed.', 'beats-upload-player' ),
    'beats-upload-failed'       => __( 'Upload failed. Please try again.', 'beats-upload-player' ),
  );

  $code = $error->get_error_code();
  if ( isset( $messages[ $code ] ) ) {
    return $messages[ $code ];
  }

  return $error->get_error_message();
}

function beats_throttle_key( $action, $identifier ) {
  $id = is_user_logged_in() ? get_current_user_id() : $identifier;
  return 'beats_throttle_' . sanitize_key( $action ) . '_' . sanitize_key( $id );
}

function beats_is_rate_limited( $action, $identifier, $window = 5, $max_attempts = 3 ) {
  $key = beats_throttle_key( $action, $identifier );
  $record = get_transient( $key );
  if ( ! is_array( $record ) ) {
    return false;
  }
  if ( $record['expires'] < time() ) {
    delete_transient( $key );
    return false;
  }
  return $record['attempts'] >= $max_attempts;
}

function beats_bump_rate_limit( $action, $identifier, $window = 5 ) {
  $key = beats_throttle_key( $action, $identifier );
  $record = get_transient( $key );
  if ( ! is_array( $record ) ) {
    $record = array(
      'attempts' => 1,
      'expires'  => time() + (int) $window,
    );
  } else {
    $record['attempts'] = (int) $record['attempts'] + 1;
  }
  set_transient( $key, $record, (int) $window );
}

if (!function_exists('beats_grouped_categories_or_default')) {
  function beats_grouped_categories_or_default($grouped, $data) {
    if (!empty($grouped)) {
      return $grouped;
    }
    if (!empty($data)) {
      $grouped[__('Uncategorized', 'beats-upload-player')] = $data;
    }
    return $grouped;
  }
}

/**
 * Group beats by category for rendering.
 */
if (!function_exists('beats_group_beats_by_category')) {
  function beats_group_beats_by_category() {
    $data = beats_read_json();
    if (empty($data) || !is_array($data)) {
      return [];
    }

    $grouped = [];
    foreach ($data as $beat) {
      $category = isset($beat['category']) && $beat['category'] !== ''
        ? (string) $beat['category']
        : __('Uncategorized', 'beats-upload-player');
      $grouped[$category][] = $beat;
    }
    return beats_grouped_categories_or_default($grouped, $data);
  }
}

/**
 * Render a batch of categories into HTML shared by the shortcode + AJAX.
 */
if (!function_exists('beats_render_category_batch')) {
  function beats_render_category_batch($offset = 0, $limit = 4) {
    $offset = max(0, intval($offset));
    $limit  = intval($limit);

    $grouped = beats_group_beats_by_category();
    $categories = array_keys($grouped);
    $total_categories = count($categories);

    if ($limit <= 0) {
      $limit = $total_categories;
    }

    $limit = max(1, $limit);

    if ($total_categories === 0) {
      return [
        'html' => '',
        'next_offset' => $offset,
        'has_more' => false,
        'total_categories' => 0,
      ];
    }

    $batch = array_slice($categories, $offset, $limit);
    if (empty($batch)) {
      return [
        'html' => '',
        'next_offset' => $offset,
        'has_more' => false,
        'total_categories' => $total_categories,
      ];
    }

    $paths = beats_paths();
    $uploads_url = trailingslashit($paths['url']);
    $uploads_base = trailingslashit($paths['base']);
    $plugin_public_url = trailingslashit(plugin_dir_url(__FILE__) . '../public');

    $beats_build_url = function ($value) use ($uploads_url, $uploads_base, $plugin_public_url) {
      $value = ltrim((string) $value, '/');
      if ($value === '') {
        return '';
      }
      if (preg_match('#^https?://#i', $value)) {
        return $value;
      }

      if (file_exists($uploads_base . $value)) {
        return $uploads_url . $value;
      }

      if (strpos($value, 'public/') === 0) {
        return $plugin_public_url . substr($value, strlen('public/'));
      }

      if (strpos($value, 'audio/') === 0 || strpos($value, 'images/') === 0) {
        return $uploads_url . $value;
      }

      return $uploads_url . $value;
    };

    ob_start();
    foreach ($batch as $cat) {
      echo '<div class="beats-section" id="' . sanitize_title($cat) . '">';
      echo '<h4>' . esc_html($cat) . '</h4><div class="beats-row">';

      foreach ($grouped[$cat] as $beat_raw) {
        $b = beats_sanitize_beat_entry($beat_raw);

        $url = esc_url($beats_build_url($b['file']));
        $img = $b['image'] !== ''
          ? esc_url($beats_build_url($b['image']))
          : esc_url(plugin_dir_url(__FILE__) . '../public/images/default-art.webp');
        $producer = $b['producer'] !== '' ? $b['producer'] : __('Unknown Producer', 'beats-upload-player');
        $price_display = $b['price'] !== '' ? 'CAD $' . $b['price'] : '';
        $buy_link = $b['buy_url'] !== '' ? esc_url($b['buy_url']) : '';

        echo '<div class="beat-card"
                data-src="' . $url . '"
                data-name="' . esc_attr($b['name']) . '"
                data-producer="' . esc_attr($producer) . '"
                data-cat="' . esc_attr($b['category']) . '"
                data-price="' . esc_attr($price_display !== '' ? $price_display : 'Free') . '"
                data-img="' . $img . '"
                data-buy="' . esc_attr($buy_link) . '">';
        echo '<div class="beat-thumb">';
        echo '<img src="' . $img . '" alt="Beat Cover" loading="lazy">';
        echo '<div class="beat-title-ribbon">' . esc_html($b['name']) . '</div>';
        echo '<div class="beat-overlay">';
        echo '<div class="beat-overlay-actions">';
        echo '<button type="button" class="beat-info-btn" aria-label="Show beat info">&#9432;</button>';
        echo '<button type="button" class="beat-cart-btn" aria-label="Show price">&#128722;</button>';
        echo '<button type="button" class="beat-play-btn" aria-label="Play beat">▶</button>';
        echo '</div>';
        echo '<div class="beat-overlay-panel">';
        echo '<div class="beat-panel beat-panel-info"><small class="beat-producer">By ' . esc_html($producer) . '</small></div>';
        $cart_classes = 'beat-panel beat-panel-cart';
        if (!$price_display) {
          $cart_classes .= ' beat-panel-cart--empty';
        }
        echo '<div class="' . esc_attr($cart_classes) . '">';
        echo '<span class="beat-price">' . ($price_display ? esc_html($price_display) : 'Free') . '</span>';
        if ($buy_link) {
          echo '<a class="beat-store-btn" href="' . $buy_link . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Buy Now', 'beats-upload-player') . '</a>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
      }

      echo '</div></div>';
    }

    $html = ob_get_clean();
    $next_offset = $offset + count($batch);
    $has_more = $next_offset < $total_categories;

    return [
      'html' => $html,
      'next_offset' => $next_offset,
      'has_more' => $has_more,
      'total_categories' => $total_categories,
    ];
  }
}
