<?php
if (!defined('ABSPATH')) exit;

/**
 * Admin dashboard + CRUD logic for managing beats inside wp-admin.
 */

/**
 * Admin Dashboard: Beats Manager (AJAX CRUD + Upload Form)
 */

// Add to admin menu
function beats_admin_menu() {
  add_menu_page(
    'Beats Manager',
    'Beats Manager',
    'manage_options',
    'beats-manager',
    'beats_admin_page',
    'dashicons-format-audio',
    25
  );

  add_submenu_page(
    'beats-manager',
    __('Upload New Beat', 'beats-upload-player'),
    __('Upload New Beat', 'beats-upload-player'),
    'manage_options',
    'beats-manager-upload',
    'beats_admin_page_upload'
  );

  add_submenu_page(
    'beats-manager',
    __('Manage Library', 'beats-upload-player'),
    __('Manage Library', 'beats-upload-player'),
    'manage_options',
    'beats-manager-library',
    'beats_admin_page_library'
  );

  add_submenu_page(
    'beats-manager',
    __('Visualizer Settings', 'beats-upload-player'),
    __('Visualizer Settings', 'beats-upload-player'),
    'manage_options',
    'beats-manager-visualizer',
    'beats_admin_page_visualizer'
  );

  add_submenu_page(
    'beats-manager',
    __('Beats Global Player', 'beats-upload-player'),
    __('Beats Global Player', 'beats-upload-player'),
    'manage_options',
    'beats-manager-global-player',
    'beats_admin_page_global_player'
  );

  add_submenu_page(
    'beats-manager',
    __('Search Bar Settings', 'beats-upload-player'),
    __('Search Bar Settings', 'beats-upload-player'),
    'manage_options',
    'beats-manager-search-settings',
    'beats_admin_page_search_settings'
  );

  remove_submenu_page('beats-manager', 'beats-manager');
}
add_action('admin_menu', 'beats_admin_menu');

/**
 * Handle upload form submissions and return admin notice markup.
 */
function beats_admin_handle_upload_submission() {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['beats_admin_upload'])) {
    return '';
  }

  if (!current_user_can('manage_options')) {
    return '<div class="notice notice-error"><p>' . esc_html__('You do not have permission to upload beats.', 'beats-upload-player') . '</p></div>';
  }

  if (!isset($_POST['beats_admin_upload_nonce']) || !wp_verify_nonce($_POST['beats_admin_upload_nonce'], 'beats-admin-upload')) {
    return '<div class="notice notice-error"><p>' . esc_html__('Security check failed. Please try again.', 'beats-upload-player') . '</p></div>';
  }

  $paths     = beats_paths();
  $beat_name = sanitize_text_field($_POST['beat_name'] ?? '');
  $producer  = sanitize_text_field($_POST['beat_producer'] ?? '');
  $category  = sanitize_text_field($_POST['beat_category'] ?? '');
  $price_raw = sanitize_text_field($_POST['beat_price'] ?? '');
  $buy_link  = isset($_POST['beat_buy_url']) ? esc_url_raw(trim($_POST['beat_buy_url'])) : '';
  if ($price_raw !== '' && !is_numeric($price_raw)) {
    return '<div class="notice notice-error"><p>' . esc_html__('Please enter a valid numeric price.', 'beats-upload-player') . '</p></div>';
  }
  $price     = $price_raw !== '' ? floatval($price_raw) : '';
  $audio     = $_FILES['beat_file'] ?? null;
  $image     = $_FILES['beat_image'] ?? null;

  if (!$audio || empty($audio['name'])) {
    return '<div class="notice notice-error"><p>' . esc_html__('Please choose an audio file.', 'beats-upload-player') . '</p></div>';
  }
  if (!$image || empty($image['name'])) {
    return '<div class="notice notice-error"><p>' . esc_html__('Please choose a cover image.', 'beats-upload-player') . '</p></div>';
  }

  $allowed_audio = ['mp3', 'wav', 'm4a'];
  $allowed_img   = ['jpg', 'jpeg', 'png', 'webp'];

  $audio_ext = strtolower(pathinfo($audio['name'], PATHINFO_EXTENSION));
  if (!in_array($audio_ext, $allowed_audio, true)) {
    return '<div class="notice notice-error"><p>' . esc_html__('Only MP3, WAV, or M4A files are allowed.', 'beats-upload-player') . '</p></div>';
  }

  $img_ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
  if (!in_array($img_ext, $allowed_img, true)) {
    return '<div class="notice notice-error"><p>' . esc_html__('Cover image must be JPG, PNG, or WEBP.', 'beats-upload-player') . '</p></div>';
  }

  $audio_filename = time() . '-' . wp_unique_filename($paths['audio_dir'], sanitize_file_name($audio['name']));
  $audio_path     = $paths['audio_dir'] . $audio_filename;

  if (!move_uploaded_file($audio['tmp_name'], $audio_path)) {
    return '<div class="notice notice-error"><p>' . esc_html__('Audio upload failed. Please try again.', 'beats-upload-player') . '</p></div>';
  }

  $image_filename = time() . '-' . wp_unique_filename($paths['img_dir'], sanitize_file_name($image['name']));
  $image_path     = $paths['img_dir'] . $image_filename;

  if (!move_uploaded_file($image['tmp_name'], $image_path)) {
    @unlink($audio_path);
    return '<div class="notice notice-error"><p>' . esc_html__('Image upload failed. Please try again.', 'beats-upload-player') . '</p></div>';
  }

  $meta = [
    'name'     => $beat_name ?: pathinfo($audio_filename, PATHINFO_FILENAME),
    'producer' => $producer ?: 'Unknown Producer',
    'file'     => 'audio/' . $audio_filename,
    'category' => $category ?: 'Uncategorized',
    'image'    => 'images/' . $image_filename,
    'price'     => $price !== '' ? number_format((float)$price, 2, '.', '') : '',
    'buy_url'  => $buy_link,
    'uploaded' => current_time('mysql'),
  ];

  $data   = beats_read_json();
  $data[] = $meta;
  beats_write_json($data);

  return '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Beat uploaded successfully.', 'beats-upload-player') . '</p></div>';
}

function beats_admin_handle_visualizer_settings() {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['beats_cltd_visualizer_settings'])) {
    return '';
  }

  if (!current_user_can('manage_options')) {
    return '<div class="notice notice-error"><p>' . esc_html__('You do not have permission to update settings.', 'beats-upload-player') . '</p></div>';
  }

  if (!isset($_POST['beats_cltd_visualizer_settings_nonce']) || !wp_verify_nonce($_POST['beats_cltd_visualizer_settings_nonce'], 'beats-visualizer-settings')) {
    return '<div class="notice notice-error"><p>' . esc_html__('Security check failed. Please try again.', 'beats-upload-player') . '</p></div>';
  }

  $enabled = isset($_POST['beats_cltd_visualizer_enabled']) ? '1' : '0';
  $fixed   = isset($_POST['beats_cltd_visualizer_fixed']) ? '1' : '0';
  update_option(beats_cltd_visualizer_option_key(), $enabled);
  update_option(beats_cltd_visualizer_fixed_option_key(), $fixed);

  if (function_exists('beats_cltd_visualizer_color_defaults')) {
    $defaults = beats_cltd_visualizer_color_defaults();
    $submitted = isset($_POST['beats_cltd_visualizer_colors']) && is_array($_POST['beats_cltd_visualizer_colors'])
      ? $_POST['beats_cltd_visualizer_colors']
      : [];
    foreach ($defaults as $slug => $default) {
      $raw = $submitted[$slug] ?? $default;
      $sanitized = beats_cltd_visualizer_sanitize_color($raw, $default);
      update_option(beats_cltd_visualizer_color_option_key($slug), $sanitized);
    }
  }

  return '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Visualizer settings updated.', 'beats-upload-player') . '</p></div>';
}

function beats_admin_handle_global_player_settings() {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['beats_cltd_global_player_settings'])) {
    return '';
  }

  if (!current_user_can('manage_options')) {
    return '<div class="notice notice-error"><p>' . esc_html__('You do not have permission to update settings.', 'beats-upload-player') . '</p></div>';
  }

  if (!isset($_POST['beats_cltd_global_player_settings_nonce']) || !wp_verify_nonce($_POST['beats_cltd_global_player_settings_nonce'], 'beats-global-player-settings')) {
    return '<div class="notice notice-error"><p>' . esc_html__('Security check failed. Please try again.', 'beats-upload-player') . '</p></div>';
  }

  $enabled = isset($_POST['beats_cltd_global_player_enabled']) ? '1' : '0';
  $logo    = isset($_POST['beats_cltd_global_logo']) ? esc_url_raw(trim($_POST['beats_cltd_global_logo'])) : '';

  update_option(beats_cltd_global_player_enabled_key(), $enabled);
  update_option(beats_cltd_global_player_logo_key(), $logo);

  return '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Global player settings updated.', 'beats-upload-player') . '</p></div>';
}

function beats_admin_handle_search_settings() {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['beats_cltd_search_settings'])) {
    return '';
  }

  if (!current_user_can('manage_options')) {
    return '<div class="notice notice-error"><p>' . esc_html__('You do not have permission to update settings.', 'beats-upload-player') . '</p></div>';
  }

  if (!isset($_POST['beats_cltd_search_settings_nonce']) || !wp_verify_nonce($_POST['beats_cltd_search_settings_nonce'], 'beats-search-settings')) {
    return '<div class="notice notice-error"><p>' . esc_html__('Security check failed. Please try again.', 'beats-upload-player') . '</p></div>';
  }

  $enabled = isset($_POST['beats_cltd_search_enabled']) ? '1' : '0';
  $sticky_disabled = isset($_POST['beats_cltd_search_disable_sticky']) ? '1' : '0';

  update_option(beats_cltd_search_enabled_option_key(), $enabled);
  update_option(beats_cltd_search_disable_sticky_option_key(), $sticky_disabled);

  return '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Search bar settings updated.', 'beats-upload-player') . '</p></div>';
}

// Admin Page UI
function beats_admin_page_render($visible_sections = null, $scroll_target = '') {
  if ($visible_sections === null) {
    $visible_sections = ['upload', 'library', 'visualizer', 'global_player', 'search'];
  }
  $show_upload = in_array('upload', $visible_sections, true);
  $show_library = in_array('library', $visible_sections, true);
  $show_visualizer = in_array('visualizer', $visible_sections, true);
  $show_global_player = in_array('global_player', $visible_sections, true);
  $show_search = in_array('search', $visible_sections, true);
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have permission to access this page.', 'beats-upload-player'));
  }

  $notices = [];

  $upload_notice = beats_admin_handle_upload_submission();
  if (!empty($upload_notice)) {
    $notices[] = $upload_notice;
  }

  $visualizer_notice = beats_admin_handle_visualizer_settings();
  if (!empty($visualizer_notice)) {
    $notices[] = $visualizer_notice;
  }

  $global_notice = beats_admin_handle_global_player_settings();
  if (!empty($global_notice)) {
    $notices[] = $global_notice;
  }

  $search_notice = beats_admin_handle_search_settings();
  if (!empty($search_notice)) {
    $notices[] = $search_notice;
  }

  echo '<div class="wrap beats-admin-wrap">';
  echo '<h1>🎵 Beats Manager</h1>';
  echo '<p class="description">' . esc_html__('Upload new beats, edit metadata, and manage artwork from one place.', 'beats-upload-player') . '</p>';

  $nav_sections = [];
  if ($show_upload) {
    $nav_sections['beats-admin-upload'] = esc_html__('Upload New Beat', 'beats-upload-player');
  }
  if ($show_library) {
    $nav_sections['beats-admin-library'] = esc_html__('Manage Library', 'beats-upload-player');
  }
  if ($show_visualizer) {
    $nav_sections['beats-admin-visualizer'] = esc_html__('Visualizer Settings', 'beats-upload-player');
  }
  if ($show_global_player) {
    $nav_sections['beats-admin-global-player'] = esc_html__('Beats Global Player', 'beats-upload-player');
  }
  if ($show_search) {
    $nav_sections['beats-admin-search'] = esc_html__('Search Bar Settings', 'beats-upload-player');
  }

  if (count($nav_sections) > 1) {
    echo '<div class="beats-admin-nav card">';
    echo '<label for="beats-section-select" class="screen-reader-text">' . esc_html__('Jump to section', 'beats-upload-player') . '</label>';
    echo '<select id="beats-section-select" class="beats-admin-nav__select">';
    foreach ($nav_sections as $target => $label) {
      echo '<option value="' . esc_attr($target) . '">' . $label . '</option>';
    }
    echo '</select>';
    echo '<button type="button" class="button" id="beats-section-go">' . esc_html__('Go', 'beats-upload-player') . '</button>';
    echo '</div>';
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
      var select = document.getElementById("beats-section-select");
      var btn = document.getElementById("beats-section-go");
      if (!select || !btn) return;
      function jump() {
        var targetId = select.value;
        var el = document.getElementById(targetId);
        if (el && typeof el.scrollIntoView === "function") {
          el.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      }
      btn.addEventListener("click", jump);
      select.addEventListener("change", jump);
    });
    </script>';
  }

  foreach ($notices as $notice) {
    echo $notice; // already escaped markup
  }

  $categories = beats_get_categories();

  if ($show_upload) {
    echo '<div id="beats-admin-upload" class="beats-admin-upload card">';
    echo '<h2>' . esc_html__('Upload New Beat', 'beats-upload-player') . '</h2>';
    echo '<form method="POST" enctype="multipart/form-data">';
    wp_nonce_field('beats-admin-upload', 'beats_admin_upload_nonce');
    echo '<input type="hidden" name="beats_admin_upload" value="1">';

    echo '<table class="form-table"><tbody>';

    echo '<tr><th scope="row"><label for="beat_name">' . esc_html__('Beat Name', 'beats-upload-player') . '</label></th>';
    echo '<td><input type="text" id="beat_name" name="beat_name" class="regular-text" required></td></tr>';

  echo '<tr><th scope="row"><label for="beat_producer">' . esc_html__('Producer', 'beats-upload-player') . '</label></th>';
  echo '<td><input type="text" id="beat_producer" name="beat_producer" class="regular-text" required></td></tr>';

  echo '<tr><th scope="row"><label for="beat_category">' . esc_html__('Category', 'beats-upload-player') . '</label></th>';
  echo '<td><select id="beat_category" name="beat_category" required>';
  foreach ($categories as $cat) {
    echo '<option value="' . esc_attr($cat) . '">' . esc_html($cat) . '</option>';
  }
  echo '</select></td></tr>';

  echo '<tr><th scope="row"><label for="beat_price">' . esc_html__('Price (CAD, optional)', 'beats-upload-player') . '</label></th>';
  echo '<td><input type="number" id="beat_price" name="beat_price" class="regular-text" min="0" step="0.01" placeholder="19.99"></td></tr>';

  echo '<tr><th scope="row"><label for="beat_buy_url">' . esc_html__('Stripe Buy Link (optional)', 'beats-upload-player') . '</label></th>';
  echo '<td><input type="url" id="beat_buy_url" name="beat_buy_url" class="regular-text" placeholder="https://checkout.stripe.com/..." pattern="https?://.+"></td></tr>';

  echo '<tr><th scope="row"><label for="beat_file">' . esc_html__('Beat File', 'beats-upload-player') . '</label></th>';
  echo '<td><input type="file" id="beat_file" name="beat_file" accept=".mp3,.wav,.m4a" required></td></tr>';

  echo '<tr><th scope="row"><label for="beat_image">' . esc_html__('Cover Image', 'beats-upload-player') . '</label></th>';
  echo '<td><input type="file" id="beat_image" name="beat_image" accept=".jpg,.jpeg,.png,.webp" required></td></tr>';

    echo '</tbody></table>';
    submit_button(__('Upload Beat', 'beats-upload-player'));
    echo '</form>';
    echo '</div>'; // upload card
  }

  if ($show_library) {
    echo '<div id="beats-admin-library" class="beats-admin-list card">';
    echo '<h2>' . esc_html__('Manage Library', 'beats-upload-player') . '</h2>';
    echo '<p class="description">' . esc_html__('Edit titles, producers, categories, or artwork. Changes save instantly.', 'beats-upload-player') . '</p>';
    echo '<div id="beats-admin-app" class="beats-admin-app"></div>';
    echo '</div>'; // list card
  }

  $visualizer_enabled = beats_cltd_visualizer_is_enabled();
  $visualizer_colors = function_exists('beats_cltd_visualizer_get_colors')
    ? beats_cltd_visualizer_get_colors()
    : (function_exists('beats_cltd_visualizer_color_defaults') ? beats_cltd_visualizer_color_defaults() : []);
  $global_player_enabled = function_exists('beats_cltd_global_player_is_enabled') ? beats_cltd_global_player_is_enabled() : true;
  $global_logo_raw = function_exists('beats_cltd_global_player_logo_raw') ? beats_cltd_global_player_logo_raw() : '';
  $global_logo_preview = function_exists('beats_cltd_global_player_logo_url') ? beats_cltd_global_player_logo_url() : plugin_dir_url(__FILE__) . '../public/images/logo-gold.webp';
  $global_logo_default = function_exists('beats_cltd_global_player_default_logo') ? beats_cltd_global_player_default_logo() : plugin_dir_url(__FILE__) . '../public/images/logo-gold.webp';
  $search_enabled = function_exists('beats_cltd_search_is_enabled') ? beats_cltd_search_is_enabled() : true;
  $search_sticky_disabled = function_exists('beats_cltd_search_sticky_is_disabled') ? beats_cltd_search_sticky_is_disabled() : false;

  if ($show_visualizer) {
    echo '<div id="beats-admin-visualizer" class="beats-admin-visualizer card">';
    echo '<h2>' . esc_html__('Visualizer Settings', 'beats-upload-player') . '</h2>';
    echo '<p class="description">' . esc_html__('Toggle the Beats Visualizer integration that can be embedded anywhere with a shortcode.', 'beats-upload-player') . '</p>';
    echo '<form method="POST">';
    wp_nonce_field('beats-visualizer-settings', 'beats_cltd_visualizer_settings_nonce');
    echo '<input type="hidden" name="beats_cltd_visualizer_settings" value="1">';

  $fixed_enabled = beats_cltd_visualizer_fixed_enabled();

  echo '<label for="beats_cltd_visualizer_enabled" style="display:flex;align-items:center;gap:8px;">';
  echo '<input type="checkbox" id="beats_cltd_visualizer_enabled" name="beats_cltd_visualizer_enabled" value="1" ' . checked($visualizer_enabled, true, false) . '>';
  echo ($visualizer_enabled ? esc_html__('Disable Beats Visualizer', 'beats-upload-player') : esc_html__('Enable Beats Visualizer', 'beats-upload-player'));
  echo '</label>';

  echo '<label for="beats_cltd_visualizer_fixed" style="display:flex;align-items:center;gap:8px;margin-top:12px;">';
  echo '<input type="checkbox" id="beats_cltd_visualizer_fixed" name="beats_cltd_visualizer_fixed" value="1" ' . checked($fixed_enabled, true, false) . '>';
  echo esc_html__('Stick visualizer to the page (position: fixed)', 'beats-upload-player');
  echo '</label>';

  if (!empty($visualizer_colors)) {
    echo '<div class="beats-visualizer-colors" style="display:flex;flex-wrap:wrap;gap:20px;margin:16px 0;">';
    $labels = [
      'band_1' => esc_html__('Top wave color', 'beats-upload-player'),
      'band_2' => esc_html__('Middle wave color', 'beats-upload-player'),
      'band_3' => esc_html__('Bottom wave color', 'beats-upload-player'),
    ];
    foreach ($visualizer_colors as $slug => $color) {
      $label = $labels[$slug] ?? sprintf(esc_html__('Wave %s color', 'beats-upload-player'), strtoupper($slug));
      $input_id = 'beats_cltd_visualizer_color_' . esc_attr($slug);
      echo '<div style="display:flex;flex-direction:column;gap:6px;min-width:160px;">';
      echo '<label for="' . $input_id . '">' . $label . '</label>';
      echo '<input type="color" id="' . $input_id . '" name="beats_cltd_visualizer_colors[' . esc_attr($slug) . ']" value="' . esc_attr($color) . '">';
      echo '</div>';
    }
    echo '</div>';
  }

  submit_button(__('Save Settings', 'beats-upload-player'));
  echo '</form>';

    echo '<p><strong>' . esc_html__('Shortcodes:', 'beats-upload-player') . '</strong> ';
    echo '<code>[beats_cltd_visualizer]</code> ';
    echo esc_html__('or', 'beats-upload-player') . ' <code>[beats_cltd_visualizer_demo]</code></p>';
    echo '</div>'; // visualizer card
  }

  if ($show_global_player) {
    if (function_exists('wp_enqueue_media')) {
      wp_enqueue_media();
    }
    echo '<div id="beats-admin-global-player" class="beats-admin-global card">';
    echo '<h2>' . esc_html__('Beats Global Player', 'beats-upload-player') . '</h2>';
    echo '<p class="description">' . esc_html__('Toggle the global player and customize its logo so it matches your brand.', 'beats-upload-player') . '</p>';
    echo '<form method="POST">';
    wp_nonce_field('beats-global-player-settings', 'beats_cltd_global_player_settings_nonce');
    echo '<input type="hidden" name="beats_cltd_global_player_settings" value="1">';

    echo '<label for="beats_cltd_global_player_enabled" style="display:flex;align-items:center;gap:8px;">';
    echo '<input type="checkbox" id="beats_cltd_global_player_enabled" name="beats_cltd_global_player_enabled" value="1" ' . checked($global_player_enabled, true, false) . '>';
    echo esc_html__('Enable Beats Global Player', 'beats-upload-player');
    echo '</label>';

    echo '<div class="beats-global-logo-field" style="display:flex;align-items:center;gap:16px;margin:16px 0;">';
    echo '<img id="beats-global-logo-preview" src="' . esc_url($global_logo_preview) . '" data-default-logo="' . esc_attr($global_logo_default) . '" alt="" style="width:80px;height:80px;border-radius:8px;object-fit:cover;background:#f3f4f6;">';
    echo '<div>'; 
    echo '<input type="hidden" name="beats_cltd_global_logo" id="beats_cltd_global_logo" value="' . esc_attr($global_logo_raw) . '">';
    echo '<button type="button" class="button" id="beats-global-logo-select">' . esc_html__('Select/Upload Logo', 'beats-upload-player') . '</button> ';
    echo '<button type="button" class="button button-link-delete" id="beats-global-logo-remove">' . esc_html__('Reset Logo', 'beats-upload-player') . '</button>';
    echo '</div></div>';

    submit_button(__('Save Settings', 'beats-upload-player'));
    echo '</form>';

    echo '<p><strong>' . esc_html__('Shortcode:', 'beats-upload-player') . '</strong> <code>[beats_cltd_global_player]</code></p>';
    $script_title  = esc_js(__('Select Global Player Logo', 'beats-upload-player'));
    $script_button = esc_js(__('Use this logo', 'beats-upload-player'));
    echo <<<JS
    <script>
    document.addEventListener("DOMContentLoaded", function() {
      var selectBtn = document.getElementById("beats-global-logo-select");
      var removeBtn = document.getElementById("beats-global-logo-remove");
      var preview = document.getElementById("beats-global-logo-preview");
      var input = document.getElementById("beats_cltd_global_logo");
      if (!selectBtn || !preview || !input) return;
      var defaultLogo = preview.getAttribute("data-default-logo");
      var frame;
      selectBtn.addEventListener("click", function(e){
        e.preventDefault();
        if (frame) {
          frame.open();
          return;
        }
        frame = wp.media({
          title: "{$script_title}",
          button: { text: "{$script_button}" },
          multiple: false
        });
        frame.on("select", function(){
          var attachment = frame.state().get("selection").first().toJSON();
          input.value = attachment.url;
          preview.src = attachment.url;
        });
        frame.open();
      });
      if (removeBtn) {
        removeBtn.addEventListener("click", function(e){
          e.preventDefault();
          input.value = "";
          preview.src = defaultLogo;
        });
      }
    });
    </script>
JS;
    echo '</div>';
  }

  if ($show_search) {
    echo '<div id="beats-admin-search" class="beats-admin-search card">';
    echo '<h2>' . esc_html__('Search Bar Settings', 'beats-upload-player') . '</h2>';
    echo '<p class="description">' . esc_html__('Control whether the Beats search bar displays on the front end and if it should stick to the top on scroll.', 'beats-upload-player') . '</p>';
    echo '<form method="POST">';
    wp_nonce_field('beats-search-settings', 'beats_cltd_search_settings_nonce');
    echo '<input type="hidden" name="beats_cltd_search_settings" value="1">';

    echo '<label for="beats_cltd_search_enabled" style="display:flex;align-items:center;gap:8px;">';
    echo '<input type="checkbox" id="beats_cltd_search_enabled" name="beats_cltd_search_enabled" value="1" ' . checked($search_enabled, true, false) . '>';
    echo esc_html__('Enable Search Bar on the Beats library', 'beats-upload-player');
    echo '</label>';

    echo '<label for="beats_cltd_search_disable_sticky" style="display:flex;align-items:center;gap:8px;margin-top:12px;">';
    echo '<input type="checkbox" id="beats_cltd_search_disable_sticky" name="beats_cltd_search_disable_sticky" value="1" ' . checked($search_sticky_disabled, true, false) . '>';
    echo esc_html__('Disable sticky behavior (keeps search bar inline)', 'beats-upload-player');
    echo '</label>';

    submit_button(__('Save Settings', 'beats-upload-player'));
    echo '</form>';
    echo '</div>';
  }

  if (!empty($scroll_target)) {
    echo '<script>document.addEventListener("DOMContentLoaded",function(){var el=document.getElementById(' . json_encode($scroll_target) . ');if(el&&el.scrollIntoView){el.scrollIntoView({behavior:"smooth",block:"start"});}});</script>';
  }

  echo '</div>'; // wrap
}

function beats_admin_page() {
  beats_admin_page_render();
}

function beats_admin_page_upload() {
  beats_admin_page_render(['upload'], 'beats-admin-upload');
}

function beats_admin_page_library() {
  beats_admin_page_render(['library'], 'beats-admin-library');
}

function beats_admin_page_visualizer() {
  beats_admin_page_render(['visualizer'], 'beats-admin-visualizer');
}

function beats_admin_page_global_player() {
  beats_admin_page_render(['global_player'], 'beats-admin-global-player');
}

function beats_admin_page_search_settings() {
  beats_admin_page_render(['search'], 'beats-admin-search');
}

/**
 * Shared helper to validate AJAX permissions.
 */
function beats_admin_verify_ajax() {
  if (!current_user_can('manage_options')) {
    wp_send_json_error(['message' => __('Permission denied.', 'beats-upload-player')], 403);
  }

  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'beats-admin')) {
    wp_send_json_error(['message' => __('Invalid security token.', 'beats-upload-player')], 403);
  }
}

function beats_admin_ajax_list() {
  beats_admin_verify_ajax();

  $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
  $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 12;
  $per_page = max(1, min(50, $per_page));
  $term = isset($_POST['term']) ? sanitize_text_field($_POST['term']) : '';
  $term_lower = strtolower($term);

  $items = beats_read_json();

  if ($term_lower !== '') {
    $items = array_filter($items, function ($beat) use ($term_lower) {
      $haystack = strtolower(($beat['name'] ?? '') . ' ' . ($beat['producer'] ?? '') . ' ' . ($beat['category'] ?? '') . ' ' . ($beat['price'] ?? ''));
      return strpos($haystack, $term_lower) !== false;
    });
  }

  $items = array_values($items);

  usort($items, function ($a, $b) {
    $timeA = isset($a['uploaded']) ? strtotime($a['uploaded']) : 0;
    $timeB = isset($b['uploaded']) ? strtotime($b['uploaded']) : 0;
    return $timeB <=> $timeA;
  });

  $total = count($items);
  $total_pages = $total > 0 ? (int) ceil($total / $per_page) : 1;
  if ($page > $total_pages) {
    $page = $total_pages;
  }
  if ($page < 1) {
    $page = 1;
  }
  $offset = ($page - 1) * $per_page;
  $paged_items = array_slice($items, $offset, $per_page);

  wp_send_json_success([
    'items' => $paged_items,
    'total' => $total,
    'page' => $page,
    'per_page' => $per_page,
    'total_pages' => $total_pages,
  ]);
}
add_action('wp_ajax_beats_list', 'beats_admin_ajax_list');

function beats_admin_ajax_update() {
  beats_admin_verify_ajax();

  $file      = sanitize_text_field($_POST['file'] ?? '');
  $name      = sanitize_text_field($_POST['name'] ?? '');
  $producer  = sanitize_text_field($_POST['producer'] ?? '');
  $category  = sanitize_text_field($_POST['category'] ?? '');
  $price_raw = sanitize_text_field($_POST['price'] ?? '');
  $buy_url   = isset($_POST['buy_url']) ? esc_url_raw(trim($_POST['buy_url'])) : '';
  if ($price_raw !== '' && !is_numeric($price_raw)) {
    wp_send_json_error(['message' => __('Please enter a valid numeric price.', 'beats-upload-player')], 400);
  }
  $price = ($price_raw !== '' && is_numeric($price_raw))
    ? number_format((float)$price_raw, 2, '.', '')
    : '';

  if (!$file) {
    wp_send_json_error(['message' => __('Missing beat identifier.', 'beats-upload-player')], 400);
  }

  $data    = beats_read_json();
  $updated = false;

  foreach ($data as &$beat) {
    if ($beat['file'] === $file) {
      if (!empty($name)) {
        $beat['name'] = $name;
      }
      if (!empty($producer)) {
        $beat['producer'] = $producer;
      }
      if (!empty($category)) {
        // Allow custom categories but trim whitespace.
        $beat['category'] = trim($category);
      }
      $beat['price'] = $price;
      $beat['buy_url'] = $buy_url;
      $updated = true;
      break;
    }
  }
  unset($beat);

  if (!$updated) {
    wp_send_json_error(['message' => __('Beat not found.', 'beats-upload-player')], 404);
  }

  beats_write_json($data);
  wp_send_json_success();
}
add_action('wp_ajax_beats_update', 'beats_admin_ajax_update');

function beats_admin_ajax_replace_image() {
  beats_admin_verify_ajax();

  $file  = sanitize_text_field($_POST['file'] ?? '');
  $image = $_FILES['image'] ?? null;

  if (!$file || !$image || empty($image['name'])) {
    wp_send_json_error(['message' => __('Invalid request.', 'beats-upload-player')], 400);
  }

  $allowed_img = ['jpg', 'jpeg', 'png', 'webp'];
  $img_ext     = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
  if (!in_array($img_ext, $allowed_img, true)) {
    wp_send_json_error(['message' => __('Unsupported image type.', 'beats-upload-player')], 400);
  }

  $paths    = beats_paths();
  $data     = beats_read_json();
  $replaced = false;
  $new_rel  = '';

  foreach ($data as &$beat) {
    if ($beat['file'] !== $file) {
      continue;
    }

    $image_filename = time() . '-' . wp_unique_filename($paths['img_dir'], sanitize_file_name($image['name']));
    $image_path     = $paths['img_dir'] . $image_filename;

    if (!move_uploaded_file($image['tmp_name'], $image_path)) {
      wp_send_json_error(['message' => __('Failed to store image.', 'beats-upload-player')], 500);
    }

    // Remove previous image if no other beats are using it.
    if (!empty($beat['image'])) {
      $old_rel  = $beat['image'];
      $old_path = $paths['base'] . ltrim($old_rel, '/');
      $shared   = false;
      foreach ($data as $maybe) {
        if (($maybe['file'] ?? '') === $beat['file']) {
          continue;
        }
        if (!empty($maybe['image']) && $maybe['image'] === $old_rel) {
          $shared = true;
          break;
        }
      }
      if (!$shared && file_exists($old_path)) {
        @unlink($old_path);
      }
    }

    $new_rel       = 'images/' . $image_filename;
    $beat['image'] = $new_rel;
    $replaced      = true;
    break;
  }
  unset($beat);

  if (!$replaced) {
    wp_send_json_error(['message' => __('Beat not found.', 'beats-upload-player')], 404);
  }

  beats_write_json($data);
  wp_send_json_success(['imageUrl' => $paths['url'] . $new_rel]);
}
add_action('wp_ajax_beats_replace_image', 'beats_admin_ajax_replace_image');

function beats_admin_ajax_delete_image() {
  beats_admin_verify_ajax();

  $file = sanitize_text_field($_POST['file'] ?? '');
  if (!$file) {
    wp_send_json_error(['message' => __('Missing beat identifier.', 'beats-upload-player')], 400);
  }

  $paths = beats_paths();
  $data  = beats_read_json();
  $found = false;

  foreach ($data as &$beat) {
    if ($beat['file'] !== $file) {
      continue;
    }

    if (!empty($beat['image'])) {
      $old_rel  = $beat['image'];
      $old_path = $paths['base'] . ltrim($old_rel, '/');

      $shared = false;
      foreach ($data as $maybe_index => $maybe) {
        if ($maybe_index === $index) {
          continue;
        }
        if (!empty($maybe['image']) && $maybe['image'] === $old_rel) {
          $shared = true;
          break;
        }
      }
      if (!$shared && file_exists($old_path)) {
        @unlink($old_path);
      }

      $beat['image'] = '';
    }

    $found = true;
    break;
  }
  unset($beat);

  if (!$found) {
    wp_send_json_error(['message' => __('Beat not found.', 'beats-upload-player')], 404);
  }

  beats_write_json($data);
  wp_send_json_success();
}
add_action('wp_ajax_beats_delete_image', 'beats_admin_ajax_delete_image');

function beats_admin_ajax_delete() {
  beats_admin_verify_ajax();

  $file = sanitize_text_field($_POST['file'] ?? '');
  if (!$file) {
    wp_send_json_error(['message' => __('Missing beat identifier.', 'beats-upload-player')], 400);
  }

  $paths = beats_paths();
  $data  = beats_read_json();
  $found = false;

  foreach ($data as $index => $beat) {
    if ($beat['file'] !== $file) {
      continue;
    }

    $audio_rel = $beat['file'];
    $audio_path = $paths['base'] . ltrim($audio_rel, '/');

    // Only remove audio if no other beat references it.
    $shared_audio = false;
    foreach ($data as $maybe_index => $maybe) {
      if ($maybe_index === $index) {
        continue;
      }
      if (!empty($maybe['file']) && $maybe['file'] === $audio_rel) {
        $shared_audio = true;
        break;
      }
    }
    if (!$shared_audio && file_exists($audio_path)) {
      @unlink($audio_path);
    }

    if (!empty($beat['image'])) {
      $image_rel  = $beat['image'];
      $image_path = $paths['base'] . ltrim($image_rel, '/');
      $shared_img = false;
      foreach ($data as $maybe_index => $maybe) {
        if ($maybe_index === $index) {
          continue;
        }
        if (!empty($maybe['image']) && $maybe['image'] === $image_rel) {
          $shared_img = true;
          break;
        }
      }
      if (!$shared_img && file_exists($image_path)) {
        @unlink($image_path);
      }
    }

    unset($data[$index]);
    $found = true;
    break;
  }

  if (!$found) {
    wp_send_json_error(['message' => __('Beat not found.', 'beats-upload-player')], 404);
  }

  beats_write_json(array_values($data));
  wp_send_json_success();
}
add_action('wp_ajax_beats_delete', 'beats_admin_ajax_delete');
