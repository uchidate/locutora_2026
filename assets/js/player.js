/**
 * Locutora — WaveSurfer player
 * Depende de: wavesurfer.js (carregado via CDN no functions.php)
 */
document.addEventListener('DOMContentLoaded', () => {

  const WAVE_OPTS = {
    waveColor:     'rgba(201,163,91,.35)',
    progressColor: '#C9A35B',
    cursorColor:   'transparent',
    barWidth:      2,
    barGap:        2,
    barRadius:     2,
    height:        'auto',
    normalize:     true,
    interact:      true,
    backend:       'WebAudio',
  };

  let activeWs = null;

  /* ─── Hero player ─── */
  const heroContainer = document.getElementById('hero-wave');
  const heroBtn       = document.getElementById('hero-play');
  const heroAudio     = heroBtn ? heroBtn.dataset.src : null;

  if (heroContainer && heroAudio) {
    const heroWs = WaveSurfer.create({ container: heroContainer, ...WAVE_OPTS, height: 110 });
    heroWs.load(heroAudio);

    heroBtn.addEventListener('click', () => togglePlay(heroWs, heroBtn));

    heroWs.on('finish', () => resetBtn(heroBtn));
    heroWs.on('audioprocess', () => {
      const el = document.getElementById('hero-time');
      if (el) el.textContent = formatTime(heroWs.getCurrentTime());
    });
  }

  /* ─── Demo cards ─── */
  document.querySelectorAll('.demo-card').forEach(card => {
    const btn       = card.querySelector('.demo-card__play');
    const container = card.querySelector('.demo-card__wave');
    const src       = btn ? btn.dataset.src : null;

    if (!btn || !container || !src) return;

    const ws = WaveSurfer.create({ container, ...WAVE_OPTS, height: 34 });
    ws.load(src);

    btn.addEventListener('click', () => {
      if (activeWs && activeWs !== ws) {
        activeWs.pause();
        resetBtn(activeWs._btn);
      }
      togglePlay(ws, btn);
      activeWs = ws;
      ws._btn  = btn;
    });

    ws.on('finish', () => resetBtn(btn));
  });

  /* ─── Helpers ─── */
  function togglePlay(ws, btn) {
    ws.playPause();
    const icon = btn.querySelector('svg');
    if (ws.isPlaying()) {
      icon.innerHTML = '<rect x="2" y="0" width="4" height="13" fill="currentColor"/><rect x="9" y="0" width="4" height="13" fill="currentColor"/>';
    } else {
      resetBtn(btn);
    }
  }

  function resetBtn(btn) {
    if (!btn) return;
    const icon = btn.querySelector('svg');
    if (icon) icon.innerHTML = '<path d="M0 0v13l11-6.5z"/>';
  }

  function formatTime(s) {
    const m = Math.floor(s / 60);
    const sec = String(Math.floor(s % 60)).padStart(2, '0');
    return `${m}:${sec}`;
  }
});
