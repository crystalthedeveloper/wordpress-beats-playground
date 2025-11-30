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
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'beats-load')) {
    wp_send_json_error(['message' => __('Invalid request.', 'beats-upload-player')], 403);
  }

  $identifier = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : 'unknown';
  if (function_exists('beats_is_rate_limited') && beats_is_rate_limited('load_more', $identifier, 5, 5)) {
    wp_send_json_error(['message' => __('Too many requests. Please wait a moment.', 'beats-upload-player')], 429);
  }

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
    beats_bump_rate_limit('load_more', $identifier, 5);
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
