<?php
if (!defined('ABSPATH')) exit;

/**
 * Front-end shortcodes: uploader, infinite-scroll listing, and player widgets.
 * These pieces render the entire storefront experience for visitors.
 */

/**
 * Front-end Shortcodes
 */

/**
 * Allowed MIME types for uploads.
 */
function beats_get_allowed_audio_mimes() {
  return apply_filters(
    'beats_allowed_audio_mimes',
    array(
      'mp3' => 'audio/mpeg',
      'm4a' => 'audio/mp4',
      'wav' => 'audio/wav',
    )
  );
}

function beats_get_allowed_image_mimes() {
  return apply_filters(
    'beats_allowed_image_mimes',
    array(
      'jpg'  => 'image/jpeg',
      'jpeg' => 'image/jpeg',
      'png'  => 'image/png',
      'webp' => 'image/webp',
    )
  );
}

/**
 * Upload directory override helpers so wp_handle_upload() stores files inside beats/.
 */
function beats_apply_upload_dir_override( $dirs ) {
  global $beats_current_upload_subdir;
  if ( empty( $beats_current_upload_subdir ) ) {
    return $dirs;
  }

  $paths   = beats_paths();
  $subdir  = trim( $beats_current_upload_subdir, '/' );
  $abs_dir = trailingslashit( $paths['base'] ) . $subdir;
  $url_dir = trailingslashit( $paths['url'] ) . $subdir . '/';

  if ( ! is_dir( $abs_dir ) ) {
    wp_mkdir_p( $abs_dir );
  }

  $dirs['path']   = $abs_dir;
  $dirs['url']    = $url_dir;
  $dirs['subdir'] = '/beats/' . $subdir;

  return $dirs;
}

function beats_set_upload_dir_override( $subdir ) {
  global $beats_current_upload_subdir;
  $beats_current_upload_subdir = trim( (string) $subdir, '/' );
  add_filter( 'upload_dir', 'beats_apply_upload_dir_override' );
}

function beats_remove_upload_dir_override() {
  global $beats_current_upload_subdir;
  $beats_current_upload_subdir = null;
  remove_filter( 'upload_dir', 'beats_apply_upload_dir_override' );
}

function beats_relative_beats_path( $absolute_path ) {
  $paths = beats_paths();
  if ( strpos( $absolute_path, $paths['base'] ) === 0 ) {
    $relative = substr( $absolute_path, strlen( $paths['base'] ) );
    return ltrim( $relative, '/' );
  }

  return basename( $absolute_path );
}

function beats_handle_frontend_upload( $file, $subdir, $allowed_mimes, $max_size ) {
  if ( empty( $file['name'] ) ) {
    return new WP_Error( 'beats-upload-missing-file', __( 'No file selected.', 'beats-upload-player' ) );
  }

  if ( ! empty( $file['error'] ) ) {
    return new WP_Error( 'beats-upload-error', __( 'There was an error uploading the file. Please try again.', 'beats-upload-player' ) );
  }

  if ( ! empty( $max_size ) && ( (int) $file['size'] > (int) $max_size ) ) {
    return new WP_Error( 'beats-upload-size', __( 'The uploaded file exceeds the size limit.', 'beats-upload-player' ) );
  }

  $type = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed_mimes );
  if ( empty( $type['ext'] ) || empty( $type['type'] ) ) {
    return new WP_Error( 'beats-upload-mime', __( 'The uploaded file type is not allowed.', 'beats-upload-player' ) );
  }

  beats_set_upload_dir_override( $subdir );
  $overrides = array(
    'test_form'                => false,
    'mimes'                    => $allowed_mimes,
    'unique_filename_callback' => function ( $dir, $name, $ext ) {
      return wp_unique_filename( $dir, sanitize_file_name( $name ) );
    },
  );

  $uploaded = wp_handle_upload( $file, $overrides );
  beats_remove_upload_dir_override();

  if ( isset( $uploaded['error'] ) ) {
    return new WP_Error( 'beats-upload-failed', $uploaded['error'] );
  }

  return array(
    'relative' => beats_relative_beats_path( $uploaded['file'] ),
    'url'      => $uploaded['url'],
  );
}

/* ===============================
   Upload Form
=============================== */
function beats_process_frontend_upload_submission() {
  if (!beats_user_can_frontend_upload(get_current_user_id())) {
    return new WP_Error('beats-upload-permission', __('You do not have permission to upload files.', 'beats-upload-player'));
  }

  if (!isset($_POST['beats_upload_nonce']) || !wp_verify_nonce($_POST['beats_upload_nonce'], 'beats-frontend-upload')) {
    return new WP_Error('beats-upload-nonce', __('Security check failed. Please reload and try again.', 'beats-upload-player'));
  }

  if (empty($_FILES['beat_file']['name'])) {
    return new WP_Error('beats-upload-missing-file', __('Please upload a beat file.', 'beats-upload-player'));
  }

  if (!beats_user_can_frontend_upload(get_current_user_id())) {
    return new WP_Error('beats-upload-permission', __('You do not have permission to upload files.', 'beats-upload-player'));
  }

  $beat_name = sanitize_text_field(wp_unslash($_POST['beat_name'] ?? ''));
  $producer  = sanitize_text_field(wp_unslash($_POST['beat_producer'] ?? ''));
  $category  = sanitize_text_field(wp_unslash($_POST['beat_category'] ?? ''));
  $price_raw = sanitize_text_field(wp_unslash($_POST['beat_price'] ?? ''));
  $buy_link  = isset($_POST['beat_buy_url']) ? esc_url_raw(trim(wp_unslash($_POST['beat_buy_url']))) : '';

  if ($price_raw !== '' && !is_numeric($price_raw)) {
    return new WP_Error('beats-upload-price', __('Please enter a valid numeric price.', 'beats-upload-player'));
  }

  $price     = $price_raw !== '' ? floatval($price_raw) : '';
  $file      = $_FILES['beat_file'];
  $image     = $_FILES['beat_image'] ?? null;

  if (empty($image['name'])) {
    return new WP_Error('beats-upload-image', __('Please upload a cover image.', 'beats-upload-player'));
  }

  $audio_upload = beats_handle_frontend_upload(
    $file,
    'audio',
    beats_get_allowed_audio_mimes(),
    apply_filters('beats_audio_max_upload_size', 20 * MB_IN_BYTES)
  );

  if (is_wp_error($audio_upload)) {
    return $audio_upload;
  }

  $image_upload = beats_handle_frontend_upload(
    $image,
    'images',
    beats_get_allowed_image_mimes(),
    apply_filters('beats_image_max_upload_size', 5 * MB_IN_BYTES)
  );

  if (is_wp_error($image_upload)) {
    return $image_upload;
  }

  $meta = [
    'name'     => $beat_name ?: pathinfo($audio_upload['relative'], PATHINFO_FILENAME),
    'producer' => $producer ?: 'Unknown Producer',
    'file'     => $audio_upload['relative'],
    'category' => $category ?: 'Uncategorized',
    'image'    => $image_upload['relative'],
    'price'    => $price !== '' ? number_format((float)$price, 2, '.', '') : '',
    'buy_url'  => $buy_link,
    'uploaded' => current_time('mysql')
  ];

  $data = beats_read_json();
  $data[] = $meta;
  beats_write_json($data);

  return [
    'message' => __('✅ Beat uploaded successfully.', 'beats-upload-player'),
  ];
}

function beats_ajax_frontend_upload() {
  $result = beats_process_frontend_upload_submission();
  if (is_wp_error($result)) {
    $message = function_exists('beats_format_upload_error') ? beats_format_upload_error($result) : $result->get_error_message();
    wp_send_json_error(['message' => $message], 400);
  }

  wp_send_json_success($result);
}
add_action('wp_ajax_beats_frontend_upload', 'beats_ajax_frontend_upload');
add_action('wp_ajax_nopriv_beats_frontend_upload', 'beats_ajax_frontend_upload');

function beats_cltd_upload_form_shortcode() {
  wp_enqueue_style('beats-upload-style');
  wp_enqueue_script('beats-upload-form');

  ob_start();

  if (!is_user_logged_in()) {
    $redirect = home_url();
    if (function_exists('get_permalink')) {
      $permalink = get_permalink();
      if ($permalink) {
        $redirect = $permalink;
      }
    }
    $login_url = esc_url(wp_login_url($redirect));
    echo '<p class="beats-upload-login-required">' . esc_html__('Please log in to upload your beats.', 'beats-upload-player') . ' ';
    echo '<a href="' . $login_url . '">' . esc_html__('Log in', 'beats-upload-player') . '</a></p>';
    return ob_get_clean();
  }

  if (!beats_user_can_frontend_upload(get_current_user_id())) {
    echo '<p class="beats-upload-error">' . esc_html__('You do not have permission to upload files.', 'beats-upload-player') . '</p>';
    return ob_get_clean();
  }

  $ajax_url = admin_url('admin-ajax.php');
  echo '<form method="POST" enctype="multipart/form-data" class="beats-upload-form" action="' . esc_url($ajax_url) . '">';
  wp_nonce_field('beats-frontend-upload', 'beats_upload_nonce');
  echo '<input type="hidden" name="action" value="beats_frontend_upload">';
  echo '<label>Beat Name:</label><br><input type="text" name="beat_name" required><br><br>';
  echo '<label>Producer Name:</label><br><input type="text" name="beat_producer" required><br><br>';
  echo '<label>Price (CAD, optional):</label><br><input type="number" name="beat_price" min="0" step="0.01" placeholder="19.99"><br><br>';
  echo '<label>Stripe Buy Link (optional):</label><br><input type="url" name="beat_buy_url" placeholder="https://checkout.stripe.com/..." pattern="https?://.+"><br><br>';
  echo '<label>🎵 Upload Beat File:</label><br><input type="file" name="beat_file" accept=".mp3,.wav,.m4a" required><br><br>';
  echo '<label>*Upload Cover Image:</label><br><input type="file" name="beat_image" accept=".jpg,.jpeg,.png,.webp" required><br><br>';
  echo '<label>Genre:</label><br><select name="beat_category" required>';
  foreach (beats_get_categories() as $cat) echo '<option value="'.esc_attr($cat).'">'.esc_html($cat).'</option>';
  echo '</select><br><br><button type="submit">' . esc_html__('Upload Beat', 'beats-upload-player') . '</button>';
  echo '<div class="beats-upload-response" role="status" aria-live="polite"></div>';
  echo '</form>';

  return ob_get_clean();
}
add_shortcode('beats_cltd_upload_form', 'beats_cltd_upload_form_shortcode');

/* ===============================
   Infinite Scroll Display
=============================== */
function beats_cltd_display_home_shortcode() {
  if (beats_has_display_home_rendered()) {
    return '';
  }
  beats_prime_data();
  wp_enqueue_style('beats-upload-style');
  wp_enqueue_script('beats-player');
  beats_mark_display_home_rendered();

  $initial_limit = apply_filters('beats_cltd_display_home_initial_limit', 0);
  $chunk = beats_render_category_batch(0, $initial_limit);
  $offset = isset($chunk['next_offset']) ? intval($chunk['next_offset']) : 0;
  $has_more = !empty($chunk['has_more']);
  $html = $chunk['html'] ?? '';

  if ($html === '') {
    $html = '<p class="beats-empty-message">' . esc_html__('No beats available yet.', 'beats-upload-player') . '</p>';
    $offset = 0;
    $has_more = false;
  }

  if ($has_more) {
    wp_enqueue_script('beats-loader');
  } else {
    wp_dequeue_script('beats-loader');
  }

  ob_start(); ?>
  <div id="beats-wrapper" data-offset="<?php echo esc_attr($offset); ?>" data-has-more="<?php echo $has_more ? '1' : '0'; ?>">
    <?php echo wp_kses_post($html); ?>
  </div>
  <?php

  return ob_get_clean();
}
add_shortcode('beats_cltd_display_home', 'beats_cltd_display_home_shortcode');

/* ===============================
   Global Player
=============================== */
function beats_cltd_global_player_shortcode() {
  if (!function_exists('beats_cltd_global_player_is_enabled') || !beats_cltd_global_player_is_enabled()) {
    return '<p class="beats-global-player-disabled">' . esc_html__('Global player is disabled in Beats Manager.', 'beats-upload-player') . '</p>';
  }
  wp_enqueue_style('beats-upload-style');
  wp_enqueue_script('beats-player');

  $logo_url = function_exists('beats_cltd_global_player_logo_url')
    ? beats_cltd_global_player_logo_url()
    : plugin_dir_url(__FILE__) . '../public/images/logo-gold.webp';

  ob_start(); ?>
  <div id="beats-global-player" class="beats-global-player glassy-player">
    <div class="player-left">
      <img id="beats-player-cover" src="<?php echo esc_url($logo_url); ?>" alt="Cover">
      <div class="player-info">
        <p id="beats-player-name">Select a beat to play</p>
        <small id="beats-player-category"></small>
        <small id="beats-player-producer"></small>
      </div>
    </div>
    <div class="player-controls">
      <audio id="beats-player-audio" controls></audio>
    </div>
  </div>
  <?php return wp_kses_post(ob_get_clean());
}
add_shortcode('beats_cltd_global_player', 'beats_cltd_global_player_shortcode');

/**
 * Playground demo shim that renders the main player with a heading.
 */
function beats_player_demo_shortcode() {
  wp_enqueue_style('beats-upload-style');
  wp_enqueue_style('beats-category-search-style');
  wp_enqueue_script('beats-loader');
  wp_enqueue_script('beats-player');

  $heading = '<h3 class="beats-player-demo__heading">' . esc_html__('Beats Upload Player Demo', 'beats-upload-player') . '</h3>';

  $player_markup = '';
  if (function_exists('beats_render_shortcode_block')) {
    $player_markup = beats_render_shortcode_block('beats_upload_player', array(), false);
    if ($player_markup === '') {
      $player_markup  = beats_render_shortcode_block('beats_cltd_category_search', array(), false);
      $player_markup .= beats_render_shortcode_block('beats_cltd_display_home', array(), false);
      $player_markup .= beats_render_shortcode_block('beats_cltd_global_player', array(), false);
    }
  } else {
    if (shortcode_exists('beats_upload_player')) {
      $player_markup = do_shortcode('[beats_upload_player]');
    } else {
      $player_markup  = do_shortcode('[beats_cltd_category_search]');
      $player_markup .= do_shortcode('[beats_cltd_display_home]');
      $player_markup .= do_shortcode('[beats_cltd_global_player]');
    }
  }

  $output = '<div class="beats-player-demo">' . $heading . $player_markup . '</div>';
  return wp_kses_post($output);
}
add_shortcode('beats_player_demo', 'beats_player_demo_shortcode');

/**
 * Track shortcode rendering and provide fallbacks when templates omit the content block.
 */
function beats_mark_display_home_rendered() {
  $GLOBALS['beats_cltd_display_home_rendered'] = true;
}

function beats_has_display_home_rendered() {
  return !empty($GLOBALS['beats_cltd_display_home_rendered']);
}
