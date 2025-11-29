<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('beats_cltd_global_player_enabled_key')) {
  function beats_cltd_global_player_enabled_key() {
    return 'beats_cltd_global_player_enabled';
  }
}

if (!function_exists('beats_cltd_global_player_logo_key')) {
  function beats_cltd_global_player_logo_key() {
    return 'beats_cltd_global_player_logo';
  }
}

if (!function_exists('beats_cltd_global_player_bootstrap_options')) {
  function beats_cltd_global_player_bootstrap_options() {
    if (get_option(beats_cltd_global_player_enabled_key(), '__missing__') === '__missing__') {
      add_option(beats_cltd_global_player_enabled_key(), '1');
    }
    if (get_option(beats_cltd_global_player_logo_key(), '__missing__') === '__missing__') {
      add_option(beats_cltd_global_player_logo_key(), '');
    }
  }
  beats_cltd_global_player_bootstrap_options();
}

if (!function_exists('beats_cltd_global_player_is_enabled')) {
  function beats_cltd_global_player_is_enabled() {
    return get_option(beats_cltd_global_player_enabled_key(), '1') === '1';
  }
}

if (!function_exists('beats_cltd_global_player_logo_raw')) {
  function beats_cltd_global_player_logo_raw() {
    return get_option(beats_cltd_global_player_logo_key(), '');
  }
}

if (!function_exists('beats_cltd_global_player_default_logo')) {
  function beats_cltd_global_player_default_logo() {
    return plugin_dir_url(__FILE__) . '../public/images/logo-gold.webp';
  }
}

if (!function_exists('beats_cltd_global_player_logo_url')) {
  function beats_cltd_global_player_logo_url() {
    $custom = beats_cltd_global_player_logo_raw();
    if (!empty($custom)) {
      return esc_url($custom);
    }
    return beats_cltd_global_player_default_logo();
  }
}
