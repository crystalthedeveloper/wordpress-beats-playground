(() => {
  'use strict';

  const beatsDebugEnabled = () =>
    typeof window !== 'undefined' && window.BEATS_DEBUG === true;
  const debugLog = (...args) => {
    if (beatsDebugEnabled()) console.log(...args);
  };
  const debugWarn = (...args) => {
    if (beatsDebugEnabled()) console.warn(...args);
  };

  debugLog('[Beats Debug] beats-loader file loaded');

  const createSentinel = () => {
    const sentinel = document.createElement('div');
    sentinel.id = 'scroll-sentinel';
    sentinel.style.cssText = 'height:1px; width:100%;';
    return sentinel;
  };

  const createLoader = () => {
    const loader = document.createElement('div');
    loader.id = 'beats-loader';
    loader.textContent = 'Loading more beats…';
    loader.setAttribute('aria-live', 'polite');
    loader.style.cssText = 'text-align:center; padding:20px; display:none;';
    return loader;
  };

  function initBeatsLoader(wrapper) {
    if (!wrapper) {
      debugWarn('[Beats Debug] initBeatsLoader called without wrapper');
      return;
    }
    debugLog('[Beats Debug] wrapper found:', wrapper);
    debugLog('[Beats Debug] beats-loader found wrapper', {
      offset: wrapper.dataset.offset,
      hasMore: wrapper.dataset.hasMore,
    });

    const ajaxConfig = window.beats_ajax || {};
    const ajaxUrl =
      ajaxConfig.ajax_url ||
      (window.wp?.ajax?.settings?.url ||
        `${window.location.origin}/wp-admin/admin-ajax.php`);
    const ajaxNonce = ajaxConfig.nonce || '';

    let isLoading = false;
    let hasMore =
      wrapper.dataset.hasMore === undefined
        ? true
        : wrapper.dataset.hasMore !== '0';

    const loader = createLoader();
    const sentinel = createSentinel();
    wrapper.appendChild(sentinel);
    wrapper.after(loader);

    if (!wrapper.querySelector('.beats-section')) {
      hasMore = true;
    }

    const parseOffset = () => {
      const raw = parseInt(wrapper.dataset.offset || 0, 10);
      return Number.isNaN(raw) ? 0 : raw;
    };

    const setOffset = value => {
      wrapper.dataset.offset = String(value);
    };

    const setHasMore = value => {
      hasMore = !!value;
      wrapper.dataset.hasMore = hasMore ? '1' : '0';
    };

    const showMessage = text => {
      let msg = wrapper.querySelector('.beats-empty-message');
      if (!msg) {
        msg = document.createElement('p');
        msg.className = 'beats-empty-message';
        wrapper.appendChild(msg);
      }
      msg.textContent = text;
    };

    const clearMessageIfPopulated = () => {
      if (wrapper.querySelector('.beats-section')) {
        const msg = wrapper.querySelector('.beats-empty-message');
        if (msg) msg.remove();
      }
    };

    const sentinelIsClose = () => {
      const viewportHeight =
        window.innerHeight || document.documentElement.clientHeight || 0;
      const rect = sentinel.getBoundingClientRect();
      return rect.top <= viewportHeight + 200;
    };

    const requestPayload = () => {
      const formData = new FormData();
      formData.append('action', 'load_more_beats');
      formData.append('offset', parseOffset());
      if (ajaxNonce) {
        formData.append('nonce', ajaxNonce);
      }
      return formData;
    };

    const insertChunk = data => {
      wrapper.insertAdjacentHTML('beforeend', data.html);
      wrapper.appendChild(sentinel);
      setOffset(data.next_offset || 0);
      setHasMore(data.has_more);
      clearMessageIfPopulated();
      document.dispatchEvent(
        new CustomEvent('beats-loaded', { detail: data })
      );
    };

    const handleEmptyState = message => {
      setHasMore(false);
      if (!wrapper.querySelector('.beats-section')) {
        showMessage(message);
      }
    };

    const loadMore = async () => {
      if (isLoading || !hasMore) return;
      isLoading = true;
      loader.style.display = 'block';

      const offset = parseOffset();

      try {
        debugLog('[Beats Debug] sending AJAX request with offset:', offset);
        const response = await fetch(ajaxUrl, {
          method: 'POST',
          body: requestPayload(),
        });
        const res = await response.json();
        debugLog('[Beats Debug] AJAX success:', res);

        if (res.success && res.data?.html) {
          debugLog('[Beats Debug] beats-loader received HTML chunk', {
            nextOffset: res.data.next_offset,
            hasMore: res.data.has_more,
            htmlLength: res.data.html.length,
          });
          insertChunk(res.data);
        } else {
          debugWarn(
            '[Beats Debug] beats-loader response missing data',
            res
          );
          handleEmptyState('No beats available yet.');
        }
      } catch (err) {
        console.error('⚠️ Beats loader failed to fetch beats:', err);
        handleEmptyState('Unable to load beats right now. Please refresh.');
      } finally {
        isLoading = false;
        loader.style.display = 'none';
        debugLog('[Beats Debug] beats-loader loadMore finished', {
          isLoading,
          hasMore,
          nextOffset: wrapper.dataset.offset,
        });

        if (hasMore && sentinelIsClose()) {
          requestAnimationFrame(loadMore);
        }
      }
    };

    const observer = new IntersectionObserver(
      entries => {
        if (entries[0].isIntersecting) loadMore();
      },
      { rootMargin: '400px' }
    );

    observer.observe(sentinel);
    loadMore();
  }

  document.addEventListener('DOMContentLoaded', () => {
    debugLog('[Beats Debug] beats-loader DOM ready');
    debugLog('[Beats Debug] looking for #beats-wrapper');
    const wrapper = document.getElementById('beats-wrapper');
    if (!wrapper) {
      debugWarn(
        '[Beats Debug] wrapper NOT found — waiting via MutationObserver'
      );
      const mo = new MutationObserver(() => {
        const lateWrapper = document.getElementById('beats-wrapper');
        if (lateWrapper) {
          debugLog('[Beats Debug] wrapper now found (late):', lateWrapper);
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
