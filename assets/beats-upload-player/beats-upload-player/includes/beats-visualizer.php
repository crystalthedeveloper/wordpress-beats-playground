<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('beats_cltd_visualizer_option_key')) {
  function beats_cltd_visualizer_option_key() {
    return 'beats_cltd_visualizer_enabled';
  }
}

if (!function_exists('beats_cltd_visualizer_fixed_option_key')) {
  function beats_cltd_visualizer_fixed_option_key() {
    return 'beats_cltd_visualizer_fixed';
  }
}

if (!function_exists('beats_cltd_visualizer_color_defaults')) {
  function beats_cltd_visualizer_color_defaults() {
    return [
      'band_1' => '#0b1221',
      'band_2' => '#1f2937',
      'band_3' => '#475569',
    ];
  }
}

if (!function_exists('beats_cltd_visualizer_color_option_key')) {
  function beats_cltd_visualizer_color_option_key($slug) {
    return 'beats_cltd_visualizer_color_' . sanitize_key($slug);
  }
}

if (!function_exists('beats_cltd_visualizer_sanitize_color')) {
  function beats_cltd_visualizer_sanitize_color($value, $fallback) {
    $value = trim((string) $value);
    if (preg_match('/^#([A-Fa-f0-9]{3}){1,2}$/', $value)) {
      return $value;
    }
    return $fallback;
  }
}

if (!function_exists('beats_cltd_visualizer_get_colors')) {
  function beats_cltd_visualizer_get_colors() {
    $defaults = beats_cltd_visualizer_color_defaults();
    $colors = [];
    foreach ($defaults as $slug => $default) {
      $stored = get_option(beats_cltd_visualizer_color_option_key($slug), $default);
      $colors[$slug] = beats_cltd_visualizer_sanitize_color($stored, $default);
    }
    return $colors;
  }
}

if (!function_exists('beats_cltd_visualizer_bootstrap_option')) {
  function beats_cltd_visualizer_bootstrap_option() {
    $key = beats_cltd_visualizer_option_key();
    if (get_option($key, '__missing__') === '__missing__') {
      add_option($key, '1');
    }

    $fixed_key = beats_cltd_visualizer_fixed_option_key();
    if (get_option($fixed_key, '__missing__') === '__missing__') {
      add_option($fixed_key, '1');
    }

    $defaults = beats_cltd_visualizer_color_defaults();
    foreach ($defaults as $slug => $color) {
      $option_key = beats_cltd_visualizer_color_option_key($slug);
      if (get_option($option_key, '__missing__') === '__missing__') {
        add_option($option_key, $color);
      }
    }
  }
  beats_cltd_visualizer_bootstrap_option();
}

if (!function_exists('beats_cltd_visualizer_is_enabled')) {
  function beats_cltd_visualizer_is_enabled() {
    return get_option(beats_cltd_visualizer_option_key(), '0') === '1';
  }
}

if (!function_exists('beats_cltd_visualizer_fixed_enabled')) {
  function beats_cltd_visualizer_fixed_enabled() {
    return get_option(beats_cltd_visualizer_fixed_option_key(), '0') === '1';
  }
}

if (!function_exists('beats_cltd_visualizer_enqueue_assets')) {
  function beats_cltd_visualizer_enqueue_assets() {
    if (!beats_cltd_visualizer_is_enabled()) {
      return;
    }
    wp_enqueue_style('beats-visualizer-style');
    wp_enqueue_script('beats-visualizer');
    wp_localize_script('beats-visualizer', 'BeatsVisualizerConfig', [
      'colors' => array_values(beats_cltd_visualizer_get_colors()),
    ]);
  }
}

if (!function_exists('beats_cltd_visualizer_shortcode')) {
  function beats_cltd_visualizer_shortcode() {
    if (!beats_cltd_visualizer_is_enabled()) {
      return '<p class="beats-visualizer-disabled">' . esc_html__('Visualizer is disabled in Beats Manager.', 'beats-upload-player') . '</p>';
    }

    beats_cltd_visualizer_enqueue_assets();

    $classes = ['beats-visualizer-container'];
    if (!beats_cltd_visualizer_fixed_enabled()) {
      $classes[] = 'beats-visualizer-container--relative';
    }
    $classes = implode(' ', array_map('sanitize_html_class', $classes));

    $band_colors = beats_cltd_visualizer_get_colors();
    $style_parts = [];
    foreach ($band_colors as $index => $color) {
      $style_parts[] = sprintf('--beats-band-%d:%s', $index + 1, $color);
    }
    $style_attr = '';
    if (!empty($style_parts)) {
      $style_attr = ' style="' . esc_attr(implode(';', $style_parts) . ';') . '"';
    }

    $color_payload = '';
    if (!empty($band_colors)) {
      $color_payload = ' data-band-colors="' . esc_attr(wp_json_encode(array_values($band_colors))) . '"';
    }

    return sprintf(
      '<div id="beats-visualizer-container" class="%s"%s%s><canvas id="beats-canvas"></canvas></div>',
      esc_attr(trim($classes)),
      $style_attr,
      $color_payload
    );
  }
  add_shortcode('beats_cltd_visualizer', 'beats_cltd_visualizer_shortcode');
}

if (!function_exists('beats_cltd_visualizer_demo_shortcode')) {
  function beats_cltd_visualizer_demo_shortcode() {
    if (!beats_cltd_visualizer_is_enabled()) {
      return '<p class="beats-visualizer-disabled">' . esc_html__('Visualizer demo is disabled. Enable it from Beats Manager.', 'beats-upload-player') . '</p>';
    }

    beats_cltd_visualizer_enqueue_assets();

    $heading = '<h3 class="beats-visualizer-demo__heading">' . esc_html__('Beats Visualizer Demo', 'beats-upload-player') . '</h3>';

    return '
      <div class="beats-visualizer-demo">
        ' . $heading . '
        ' . beats_cltd_visualizer_shortcode() . '
      </div>
    ';
  }
  add_shortcode('beats_cltd_visualizer_demo', 'beats_cltd_visualizer_demo_shortcode');
}
