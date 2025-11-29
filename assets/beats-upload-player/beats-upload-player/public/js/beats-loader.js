(() => {
const beatsDebugEnabled = () =>
  typeof window !== "undefined" && window.BEATS_DEBUG === true;
const debugLog = (...args) => {
  if (beatsDebugEnabled()) console.log(...args);
};
const debugWarn = (...args) => {
  if (beatsDebugEnabled()) console.warn(...args);
};

debugLog("[Beats Debug] beats-loader file loaded");

function initBeatsLoader(wrapper) {
  if (!wrapper) {
    debugWarn("[Beats Debug] initBeatsLoader called without wrapper");
    return;
  }
  debugLog("[Beats Debug] wrapper found:", wrapper);
  debugLog("[Beats Debug] beats-loader found wrapper", {
    offset: wrapper.dataset.offset,
    hasMore: wrapper.dataset.hasMore,
  });

  const ajaxConfig = window.beats_ajax || {};
  const ajaxUrl =
    ajaxConfig.ajax_url ||
    (window.wp?.ajax?.settings?.url || `${window.location.origin}/wp-admin/admin-ajax.php`);
  const ajaxNonce = ajaxConfig.nonce || "";

  let isLoading = false;
  let hasMore =
    wrapper.dataset.hasMore === undefined
      ? true
      : wrapper.dataset.hasMore !== "0";

  const loader = document.createElement("div");
  loader.id = "beats-loader";
  loader.textContent = "Loading more beats...";
  loader.style.cssText = "text-align:center; padding:20px; display:none;";

  const sentinel = document.createElement("div");
  sentinel.id = "scroll-sentinel";
  sentinel.style.cssText = "height:1px; width:100%;";

  wrapper.appendChild(sentinel);
  wrapper.after(loader);
  const initialHasContent = !!wrapper.querySelector(".beats-section");
  if (!initialHasContent) {
    setHasMore(true);
  }

  function parseOffset() {
    const raw = parseInt(wrapper.dataset.offset || 0, 10);
    return Number.isNaN(raw) ? 0 : raw;
  }

  function setOffset(value) {
    wrapper.dataset.offset = String(value);
  }

  function setHasMore(value) {
    hasMore = !!value;
    wrapper.dataset.hasMore = hasMore ? "1" : "0";
  }

  function showMessage(text) {
    let msg = wrapper.querySelector(".beats-empty-message");
    if (!msg) {
      msg = document.createElement("p");
      msg.className = "beats-empty-message";
      wrapper.appendChild(msg);
    }
    msg.textContent = text;
  }

  function clearMessageIfPopulated() {
    if (wrapper.querySelector(".beats-section")) {
      const msg = wrapper.querySelector(".beats-empty-message");
      if (msg) msg.remove();
    }
  }

  function sentinelIsClose() {
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
    const rect = sentinel.getBoundingClientRect();
    return rect.top <= viewportHeight + 200;
  }

  async function loadMore() {
    if (isLoading || !hasMore) return;
    isLoading = true;
    loader.style.display = "block";

    const offset = parseOffset();
    const formData = new FormData();
    formData.append("action", "load_more_beats");
    formData.append("offset", offset);
    if (ajaxNonce) {
      formData.append("nonce", ajaxNonce);
    }

    try {
      debugLog("[Beats Debug] sending AJAX request with offset:", offset);
      const response = await fetch(ajaxUrl, {
        method: "POST",
        body: formData,
      });
      const res = await response.json();
      debugLog("[Beats Debug] AJAX success:", res);

      if (res.success && res.data.html) {
        debugLog("[Beats Debug] beats-loader received HTML chunk", {
          nextOffset: res.data.next_offset,
          hasMore: res.data.has_more,
          htmlLength: res.data.html.length,
        });
        wrapper.insertAdjacentHTML("beforeend", res.data.html);
        wrapper.appendChild(sentinel);
        setOffset(res.data.next_offset || 0);
        setHasMore(res.data.has_more);
        clearMessageIfPopulated();

        // Notify other scripts (like beats-player.js)
        document.dispatchEvent(
          new CustomEvent("beats-loaded", { detail: res.data })
        );
      } else {
        console.warn("[Beats Debug] beats-loader response missing data", res);
        setHasMore(false);
        if (!wrapper.querySelector(".beats-section")) {
          showMessage("No beats available yet.");
        }
      }
    } catch (err) {
      console.error("⚠️ Beats load failed:", err);
      console.error("Beats loader failed to fetch beats:", err);
      if (!wrapper.querySelector(".beats-section")) {
        showMessage("Unable to load beats right now. Please refresh.");
      }
    } finally {
      isLoading = false;
      loader.style.display = "none";
      debugLog("[Beats Debug] beats-loader loadMore finished", {
        isLoading,
        hasMore,
        nextOffset: wrapper.dataset.offset,
      });

      // If the sentinel never left the viewport (e.g., short content),
      // immediately queue another fetch so we keep filling the page.
      if (hasMore && sentinelIsClose()) {
        requestAnimationFrame(loadMore);
      }
    }
  }

  const observer = new IntersectionObserver(
    entries => {
      if (entries[0].isIntersecting) loadMore();
    },
    { rootMargin: "400px" }
  );

  observer.observe(sentinel);
  loadMore(); // Prefetch next chunk after the initial server render
}

document.addEventListener("DOMContentLoaded", () => {
  debugLog("[Beats Debug] beats-loader DOM ready");
  debugLog("[Beats Debug] looking for #beats-wrapper");
  const wrapper = document.getElementById("beats-wrapper");
  if (!wrapper) {
    debugWarn("[Beats Debug] wrapper NOT found — waiting via MutationObserver");
    const mo = new MutationObserver(() => {
      const lateWrapper = document.getElementById("beats-wrapper");
      if (lateWrapper) {
        debugLog("[Beats Debug] wrapper now found (late):", lateWrapper);
        mo.disconnect();
        initBeatsLoader(lateWrapper);
      }
    });
    mo.observe(document.body, { childList: true, subtree: true });
    return;
  }
  initBeatsLoader(wrapper);
});

})();
