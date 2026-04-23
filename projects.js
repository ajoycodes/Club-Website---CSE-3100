/* ============================================================
   PROJECTS PAGE — projects.js
   Category filter for project grid
   ============================================================ */
(function () {
  const filters  = document.querySelectorAll('#projFilters .filter-btn');
  const cards    = document.querySelectorAll('.proj-card[data-category]');
  const emptyMsg = document.getElementById('projEmpty');
  if (!filters.length) return;

  filters.forEach(btn => {
    btn.addEventListener('click', () => {
      filters.forEach(b => b.classList.remove('filter-btn--active'));
      btn.classList.add('filter-btn--active');

      const filter = btn.dataset.filter;
      let visible = 0;

      cards.forEach(card => {
        const match = filter === 'all' || card.dataset.category === filter;
        card.classList.toggle('proj-card--hidden', !match);
        if (match) visible++;
      });

      if (emptyMsg) emptyMsg.style.display = visible === 0 ? 'block' : 'none';
    });
  });
})();
