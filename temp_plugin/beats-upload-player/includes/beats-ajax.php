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
  if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'beats-load')) {
    wp_send_json_error(['message' => 'Invalid request.'], 403);
  }

  $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
  $limit  = apply_filters('beats_display_categories_per_batch', 4);

  $chunk = beats_render_category_batch($offset, $limit);

  if (empty($chunk['html'])) {
    wp_send_json_error(['message' => 'No beats found.']);
    return;
  }

  wp_send_json_success([
    'html'        => $chunk['html'],
    'next_offset' => $chunk['next_offset'],
    'has_more'    => $chunk['has_more'],
  ]);
}
