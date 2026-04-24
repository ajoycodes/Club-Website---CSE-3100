/* ============================================================
   ROADMAP PAGE — roadmap.js
   Track tab switching + specialisation card shortcuts
   ============================================================ */
(function () {
  const tabs     = document.querySelectorAll('.track-tab');
  const panels   = document.querySelectorAll('.track-content');
  if (!tabs.length) return;

  function switchTrack(trackId) {
    tabs.forEach(t => {
      const active = t.dataset.track === trackId;
      t.classList.toggle('track-tab--active', active);
      t.setAttribute('aria-selected', active);
    });
    panels.forEach(p => {
      p.classList.toggle('track-content--active', p.dataset.track === trackId);
    });
    // Scroll track tabs into view on mobile
    const activeTab = document.querySelector(`.track-tab[data-track="${trackId}"]`);
    if (activeTab) activeTab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
  }

  // Tab clicks
  tabs.forEach(tab => {
    tab.addEventListener('click', () => switchTrack(tab.dataset.track));
  });

  // Specialisation shortcut cards (in Foundations phase 04)
  document.querySelectorAll('.spec-card[data-goto]').forEach(card => {
    card.addEventListener('click', () => {
      const target = card.dataset.goto;
      switchTrack(target);
      // Scroll to the top of the roadmap section
      document.querySelector('.roadmap-section')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });
})();
