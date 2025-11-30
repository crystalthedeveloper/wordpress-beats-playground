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

  document.addEventListener('DOMContentLoaded', () => {
    debugLog('[Beats Debug] beats-player DOMContentLoaded fired');
    const player = document.getElementById('beats-player-audio');
    const cover = document.getElementById('beats-player-cover');
    const name = document.getElementById('beats-player-name');
    const cat = document.getElementById('beats-player-category');
    const prod = document.getElementById('beats-player-producer');

    if (!player) {
      debugWarn(
        '[Beats Debug] beats-player could not find #beats-player-audio'
      );
      return;
    }
    debugLog('[Beats Debug] beats-player initialized');

    let currentSrc = '';
    const overlayStates = ['show-info', 'show-cart'];

    const clearAllOverlays = () => {
      document.querySelectorAll('.beat-overlay').forEach(overlay => {
        overlay.classList.remove(...overlayStates);
      });
    };

    const setOverlayState = (overlay, state) => {
      overlay.classList.remove(...overlayStates);
      if (state) {
        overlay.classList.add(state);
      }
    };

    const toggleOverlayState = (overlay, state) => {
      const isActive = overlay.classList.contains(state);
      clearAllOverlays();
      if (!isActive) {
        overlay.classList.add(state);
      }
    };

    const resetAllButtons = () => {
      document
        .querySelectorAll('.beat-play-btn')
        .forEach(btn => (btn.textContent = '▶'));
      clearAllOverlays();
    };

    const bindCard = card => {
      if (card.dataset.playerBound === '1') return;
      const playBtn = card.querySelector('.beat-play-btn');
      const imgEl = card.querySelector('.beat-thumb img');
      const overlay = card.querySelector('.beat-overlay');
      const infoBtn = card.querySelector('.beat-info-btn');
      const cartBtn = card.querySelector('.beat-cart-btn');
      if (!playBtn || !imgEl) return;
      if (overlay) setOverlayState(overlay, null);

      const src = card.dataset.src;
      const img = card.dataset.img;
      const title = card.dataset.name || '';
      const category = card.dataset.cat || '';
      const producer = card.dataset.producer || 'Unknown Producer';
      const price = card.dataset.price || '';

      const togglePlay = () => {
        if (currentSrc === src) {
          if (!player.paused) {
            player.pause();
            playBtn.textContent = '▶';
          } else {
            player
              .play()
              .then(() => (playBtn.textContent = '⏸'))
              .catch(() => {
                playBtn.textContent = '▶';
              });
          }
          return;
        }

        resetAllButtons();
        if (overlay) {
          setOverlayState(overlay, null);
        }
        player.src = src;
        player
          .play()
          .then(() => {
            playBtn.textContent = '⏸';
          })
          .catch(() => {
            playBtn.textContent = '▶';
          });

        currentSrc = src;
        cover.src = img;
        name.textContent = title || 'Unknown Beat';
        cat.textContent = price ? `${category} • ${price}` : category;
        if (prod) prod.textContent = producer;
      };

      playBtn.addEventListener('click', togglePlay);
      imgEl.addEventListener('click', togglePlay);

      if (infoBtn && overlay) {
        const showInfo = () => {
          clearAllOverlays();
          setOverlayState(overlay, 'show-info');
        };
        const toggleInfo = () => toggleOverlayState(overlay, 'show-info');
        infoBtn.addEventListener('mouseenter', showInfo);
        infoBtn.addEventListener('focus', showInfo);
        infoBtn.addEventListener('click', event => {
          event.stopPropagation();
          toggleInfo();
        });
      }

      if (cartBtn && overlay) {
        const showCart = () => {
          clearAllOverlays();
          setOverlayState(overlay, 'show-cart');
        };
        const toggleCart = () => toggleOverlayState(overlay, 'show-cart');
        cartBtn.addEventListener('mouseenter', showCart);
        cartBtn.addEventListener('focus', showCart);
        cartBtn.addEventListener('click', event => {
          event.stopPropagation();
          toggleCart();
        });
      }

      if (overlay) {
        overlay.addEventListener('mouseleave', () =>
          setOverlayState(overlay, null)
        );
      }
      card.addEventListener('mouseleave', () => {
        if (overlay) setOverlayState(overlay, null);
      });

      card.dataset.playerBound = '1';
      debugLog('[Beats Debug] beats-player bound card', {
        name: title,
        category,
      });
    };

    const attachHandlers = () => {
      document.querySelectorAll('.beat-card').forEach(bindCard);
    };

    player.addEventListener('ended', () => {
      resetAllButtons();
      currentSrc = '';
    });

    attachHandlers();
    debugLog('[Beats Debug] beats-player initial binding complete');

    document.addEventListener('beats-loaded', () => {
      debugLog('[Beats Debug] beats-player heard beats-loaded event');
      clearAllOverlays();
      attachHandlers();
    });

    const wrapper = document.getElementById('beats-wrapper');
    if (wrapper) {
      const observer = new MutationObserver(() => attachHandlers());
      observer.observe(wrapper, { childList: true, subtree: true });
    }
  });
})();
