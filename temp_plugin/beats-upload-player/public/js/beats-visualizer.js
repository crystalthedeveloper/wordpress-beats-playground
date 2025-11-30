//beats-visualizer.js
function BeatsVisualizerInit() {
  const canvas = document.getElementById("beats-canvas");
  if (!canvas) return;

  const config = window.BeatsVisualizerConfig || {};
  let bandColors = Array.isArray(config.colors) ? config.colors : [];

  if (!bandColors.length && canvas && canvas.parentElement) {
    const dataAttr = canvas.parentElement.getAttribute('data-band-colors');
    if (dataAttr) {
      try {
        const parsed = JSON.parse(dataAttr);
        if (Array.isArray(parsed)) {
          bandColors = parsed;
        }
      } catch (err) {
        console.warn('[Beats Visualizer] Failed to parse data-band-colors', err);
      }
    }
  }

  function hexToRgba(hex, alpha) {
    if (typeof hex !== "string" || !/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)) {
      return `rgba(0,0,0,${alpha})`;
    }
    let c = hex.substring(1).split("");
    if (c.length === 3) {
      c = [c[0], c[0], c[1], c[1], c[2], c[2]];
    }
    const r = parseInt(c[0] + c[1], 16);
    const g = parseInt(c[2] + c[3], 16);
    const b = parseInt(c[4] + c[5], 16);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
  }

  const ctx = canvas.getContext("2d");

  // resize for high DPI
  function resizeCanvas() {
    const rect = canvas.getBoundingClientRect();
    const ratio = window.devicePixelRatio || 1;
    canvas.width = rect.width * ratio;
    canvas.height = rect.height * ratio;
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
  }
  resizeCanvas();
  window.addEventListener("resize", resizeCanvas);

  // Web Audio connection setup
  const audioEl = document.getElementById("beats-player-audio");
  let audioCtx, analyser, dataArray, source;

  function setupAudio() {
    if (!audioEl) return;
    if (audioCtx) return; // already initialized

    audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    analyser = audioCtx.createAnalyser();
    analyser.fftSize = 256;

    const bufferLength = analyser.frequencyBinCount;
    dataArray = new Uint8Array(bufferLength);

    // connect player → analyser → speakers
    source = audioCtx.createMediaElementSource(audioEl);
    source.connect(analyser);
    analyser.connect(audioCtx.destination);
  }

  // Listen for playback
  document.addEventListener("click", () => {
    // Resume context on first interaction (for Chrome autoplay policy)
    if (audioCtx && audioCtx.state === "suspended") audioCtx.resume();
  });

  if (audioEl) {
    audioEl.addEventListener("play", () => {
      setupAudio();
      if (audioCtx && audioCtx.state === "suspended") audioCtx.resume();
    });
  }

  const points = 160;
  let tick = 0;

  function draw() {
    requestAnimationFrame(draw);

    const { width, height } = canvas;
    const w = width / (window.devicePixelRatio || 1);
    const h = height / (window.devicePixelRatio || 1);

    ctx.fillStyle = "#ffffff";
    ctx.fillRect(0, 0, w, h);

    const mid = h / 2;
    const step = w / points;

    const isPlaying = audioEl && !audioEl.paused && !audioEl.ended;
    let energy = 0;

    if (analyser && dataArray) {
      analyser.getByteFrequencyData(dataArray);
      const avg = dataArray.reduce((a, b) => a + b, 0) / dataArray.length;
      energy = avg / 255;
    }

    const baseSpeed = isPlaying ? 0.012 : 0.003;
    const speedBoost = isPlaying ? energy * 0.03 : energy * 0.008;
    tick += baseSpeed + speedBoost;

    const bands = [
      { offset: -140, speed: 0.035, amplitude: 80, lineWidth: 2.6, alpha: 0.85 },
      { offset: 0, speed: 0.022, amplitude: 48, lineWidth: 1.8, alpha: 0.6 },
      { offset: 140, speed: 0.012, amplitude: 28, lineWidth: 1.1, alpha: 0.45 },
    ];

    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';

    bands.forEach((band, idx) => {
      const amplitude = band.amplitude * (0.35 + energy * 0.9);
      const lineSpeed = band.speed + (isPlaying ? energy * 0.025 : energy * 0.008) * (idx + 1);
      const phase = tick * (1 + idx * 0.3);
      const verticalShift = band.offset * (1.2 + energy * 0.8);
      const wobble = Math.sin(phase * 0.25) * (isPlaying ? 14 : 6);
      const phaseOffset = phase * (0.7 + lineSpeed * 16);

      const customColor = bandColors[idx] || null;
      ctx.strokeStyle = customColor ? hexToRgba(customColor, band.alpha) : `rgba(0, 0, 0, ${band.alpha})`;
      ctx.lineWidth = band.lineWidth;
      ctx.beginPath();

      for (let i = 0; i <= points; i++) {
        const x = i * step;
        const y = mid + verticalShift + wobble
          + Math.sin(i * 0.14 + phaseOffset) * amplitude
          + Math.sin(i * 0.045 + phaseOffset * 0.55) * amplitude * 0.18;
        if (i === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
      }
      ctx.stroke();
    });
  }

  draw();
}
