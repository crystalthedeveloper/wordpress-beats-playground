<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('beats_cltd_search_enabled_option_key')) {
  function beats_cltd_search_enabled_option_key() {
    return 'beats_enable_search_bar';
  }
}

if (!function_exists('beats_cltd_search_disable_sticky_option_key')) {
  function beats_cltd_search_disable_sticky_option_key() {
    return 'beats_disable_sticky_search_bar';
  }
}

if (!function_exists('beats_cltd_search_bootstrap_options')) {
  function beats_cltd_search_bootstrap_options() {
    if (get_option(beats_cltd_search_enabled_option_key(), '__missing__') === '__missing__') {
      add_option(beats_cltd_search_enabled_option_key(), '1');
    }
    if (get_option(beats_cltd_search_disable_sticky_option_key(), '__missing__') === '__missing__') {
      add_option(beats_cltd_search_disable_sticky_option_key(), '0');
    }
  }
  beats_cltd_search_bootstrap_options();
}

if (!function_exists('beats_cltd_search_is_enabled')) {
  function beats_cltd_search_is_enabled() {
    return get_option(beats_cltd_search_enabled_option_key(), '1') === '1';
  }
}

if (!function_exists('beats_cltd_search_sticky_is_disabled')) {
  function beats_cltd_search_sticky_is_disabled() {
    return get_option(beats_cltd_search_disable_sticky_option_key(), '0') === '1';
  }
}
