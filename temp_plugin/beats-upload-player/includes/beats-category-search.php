<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('beats_cltd_search_enable_option_key')) {
  function beats_cltd_search_enable_option_key() {
    return 'beats_enable_search_bar';
  }
}

if (!function_exists('beats_cltd_search_sticky_option_key')) {
  function beats_cltd_search_sticky_option_key() {
    return 'beats_disable_sticky_search_bar';
  }
}

if (!function_exists('beats_cltd_search_bootstrap_options')) {
  function beats_cltd_search_bootstrap_options() {
    if (get_option(beats_cltd_search_enable_option_key(), '__missing__') === '__missing__') {
      add_option(beats_cltd_search_enable_option_key(), '1');
    }

    if (get_option(beats_cltd_search_sticky_option_key(), '__missing__') === '__missing__') {
      add_option(beats_cltd_search_sticky_option_key(), '0');
    }
  }
  beats_cltd_search_bootstrap_options();
}

if (!function_exists('beats_cltd_search_is_enabled')) {
  function beats_cltd_search_is_enabled() {
    return get_option(beats_cltd_search_enable_option_key(), '1') === '1';
  }
}

if (!function_exists('beats_cltd_search_sticky_is_disabled')) {
  function beats_cltd_search_sticky_is_disabled() {
    return get_option(beats_cltd_search_sticky_option_key(), '0') === '1';
  }
}

/**
 * Shortcode: [beats_cltd_category_search]
 * Adds a smart search bar that scrolls to a matched category section.
 */
function beats_cltd_category_search_assets() {
  if (!wp_style_is('beats-category-search-style', 'registered')) {
    $dir = plugin_dir_url(__FILE__);
    wp_register_style(
      'beats-category-search-style',
      $dir . '../public/css/beats-category-search.css',
      [],
      defined('BEATS_UPLOAD_PLAYER_VERSION') ? BEATS_UPLOAD_PLAYER_VERSION : '1.0'
    );
  }
}
add_action('wp_enqueue_scripts', 'beats_cltd_category_search_assets');

function beats_cltd_category_search_shortcode() {
  if (function_exists('beats_cltd_search_is_enabled') && !beats_cltd_search_is_enabled()) {
    return '';
  }

  wp_enqueue_style('beats-category-search-style');

  $sticky_disabled = function_exists('beats_cltd_search_sticky_is_disabled') ? beats_cltd_search_sticky_is_disabled() : false;
  $sticky_attr = $sticky_disabled ? ' data-sticky-disabled="1"' : ' data-sticky-disabled="0"';
  $container_classes = 'beats-search-container' . ($sticky_disabled ? ' beats-search-container--static' : '');

  ob_start(); ?>

  <div class="<?php echo esc_attr($container_classes); ?>" data-sticky-top="32"<?php echo $sticky_attr; ?>>
    <input
      type="text"
      id="beats-search-input"
      placeholder="🔍 Search genre (e.g. Hip hop, Trap, Reggae)..."
    />
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const input = document.getElementById("beats-search-input");
      if (!input) {
        return;
      }
      const container = input.closest(".beats-search-container");
      const defaultPlaceholder = input.getAttribute("placeholder") || "";

      function normalize(str) {
        return str.toLowerCase().trim().replace(/[\s_]+/g, "-"); // normalize spacing
      }

      function findMatchingSection(query) {
        const normalizedQuery = normalize(query);
        const sections = document.querySelectorAll(".beats-section");
        let exactMatch = null;
        let partialMatch = null;

        sections.forEach(sec => {
          const id = normalize(sec.id || "");
          const title = normalize(sec.querySelector("h4")?.textContent || "");

          if (!exactMatch && (id === normalizedQuery || title === normalizedQuery)) {
            exactMatch = sec;
            return;
          }

          if (!partialMatch && (id.startsWith(normalizedQuery) || title.startsWith(normalizedQuery) || id.includes(`-${normalizedQuery}`) || title.includes(`-${normalizedQuery}`))) {
            partialMatch = sec;
          }
        });

        return exactMatch || partialMatch;
      }

      function markNotFound(message) {
        input.classList.add("not-found");
        input.value = "";
        input.setAttribute("placeholder", message || defaultPlaceholder);
      }

      function clearNotFound() {
        if (!input.classList.contains("not-found")) return;
        input.classList.remove("not-found");
        input.setAttribute("placeholder", defaultPlaceholder);
      }

      input.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
          const query = input.value;
          if (!query) return;

          const target = findMatchingSection(query);
          if (target) {
            target.scrollIntoView({ behavior: "smooth", block: "start", inline: "nearest" });
            target.classList.add("highlight");
            setTimeout(() => target.classList.remove("highlight"), 1500);
            clearNotFound();
          } else {
            markNotFound("No matching category found");
          }
        }
      });

      // Optional live search scroll
      input.addEventListener("input", function () {
        clearNotFound();
        const query = this.value;
        if (!query) return;
        const target = findMatchingSection(query);
        if (target) {
          target.scrollIntoView({ behavior: "smooth", block: "start", inline: "nearest" });
          clearNotFound();
        }
      });

      input.addEventListener("focus", () => {
        clearNotFound();
      });

      if (container) {
        const stickyDisabled = container.dataset.stickyDisabled === "1";
        if (!stickyDisabled) {
          const adminOffset = document.body.classList.contains("admin-bar") ? 32 : 0;
          const baseOffset = parseFloat(container.dataset.stickyTop || "32");
          const topOffset = baseOffset + adminOffset;
          const originalTop = container.getBoundingClientRect().top + window.scrollY;
          const placeholder = document.createElement("div");
          placeholder.className = "beats-search-placeholder";

          const syncPlaceholderDimensions = () => {
            const computed = window.getComputedStyle(container);
            placeholder.style.width = `${container.offsetWidth}px`;
            placeholder.style.marginLeft = 'auto';
            placeholder.style.marginRight = 'auto';
            placeholder.style.marginTop = computed.marginTop;
            placeholder.style.marginBottom = computed.marginBottom;
          };

          const applyFixedPosition = () => {
            const rect = placeholder.getBoundingClientRect();
            container.style.width = `${rect.width}px`;
            container.style.left = `${rect.left}px`;
            container.style.right = 'auto';
            container.style.transform = 'none';
            container.style.top = `${topOffset}px`;
          };

          const resetFixedPosition = () => {
            container.style.width = '';
            container.style.left = '';
            container.style.right = '';
            container.style.transform = '';
            container.style.top = '';
          };

          const updateStickyState = () => {
            const shouldFix = window.scrollY > originalTop - topOffset;
            const isFixed = container.classList.contains("is-fixed");

            if (shouldFix && !isFixed) {
              syncPlaceholderDimensions();
              placeholder.style.height = `${container.offsetHeight}px`;
              container.parentNode.insertBefore(placeholder, container);
              container.classList.add("is-fixed");
              applyFixedPosition();
            } else if (shouldFix && isFixed) {
              syncPlaceholderDimensions();
              placeholder.style.height = `${container.offsetHeight}px`;
              applyFixedPosition();
            } else if (!shouldFix && isFixed) {
              container.classList.remove("is-fixed");
              if (placeholder.parentNode) {
                placeholder.parentNode.removeChild(placeholder);
              }
              resetFixedPosition();
            }
          };

          window.addEventListener("scroll", updateStickyState, { passive: true });
          window.addEventListener("resize", () => {
            if (container.classList.contains("is-fixed")) {
              placeholder.style.height = `${container.offsetHeight}px`;
              syncPlaceholderDimensions();
              applyFixedPosition();
            }
            updateStickyState();
          });
          updateStickyState();
        }
      }
    });
  </script>

  <?php return ob_get_clean();
}
add_shortcode('beats_cltd_category_search', 'beats_cltd_category_search_shortcode');
