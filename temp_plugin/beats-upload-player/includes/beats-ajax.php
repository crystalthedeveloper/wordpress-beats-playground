<?php
if (!defined('ABSPATH')) exit;

/**
 * AJAX controller that powers the infinite-scroll feed by sending
 * pre-rendered category batches back to the frontend JavaScript loader.
 */

/**
 * AJAX: Load Beats Dynamically (Infinite Scroll)
 */
add_action('wp_ajax_load_more_beats', 'beats_ajax_load_more');
add_action('wp_ajax_nopriv_load_more_beats', 'beats_ajax_load_more');

function beats_ajax_load_more() {
  $nonce  = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
  $check  = function_exists('beats_validate_ajax_request')
    ? beats_validate_ajax_request($nonce, 'beats-load', 'load_more')
    : true;

  if (is_wp_error($check)) {
    $status = $check->get_error_code() === 'beats-ajax-rate-limited' ? 429 : 403;
    wp_send_json_error(['message' => $check->get_error_message()], $status);
  }

  $identifier = is_array($check) && isset($check['identifier']) ? $check['identifier'] : 'global';

  $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
  $requested_limit = isset($_POST['limit']) ? intval($_POST['limit']) : 0;
  $default_limit = apply_filters('beats_display_categories_per_batch', 4);
  $limit = $requested_limit > 0 ? $requested_limit : $default_limit;
  $limit = min(max(1, $limit), apply_filters('beats_ajax_max_limit', 10));

  if ($offset < 0) {
    $offset = 0;
  }

  $chunk = beats_render_category_batch($offset, $limit);

  if (function_exists('beats_bump_rate_limit')) {
    beats_bump_rate_limit('load_more', $identifier, apply_filters('beats_ajax_rate_limit_window', 5, 'load_more'));
  }

  if (empty($chunk['html'])) {
    wp_send_json_error(['message' => __('No beats found.', 'beats-upload-player')]);
    return;
  }

  wp_send_json_success([
    'html'        => $chunk['html'],
    'next_offset' => $chunk['next_offset'],
    'has_more'    => $chunk['has_more'],
  ]);
}
