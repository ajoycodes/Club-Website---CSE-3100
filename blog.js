/* ============================================================
   BLOG PAGE — blog.js
   Category filter for post grid
   ============================================================ */
(function () {
  const filters  = document.querySelectorAll('.filter-btn');
  const cards    = document.querySelectorAll('.post-card[data-category]');
  const emptyMsg = document.getElementById('blogEmpty');
  if (!filters.length) return;

  filters.forEach(btn => {
    btn.addEventListener('click', () => {
      // update active button
      filters.forEach(b => b.classList.remove('filter-btn--active'));
      btn.classList.add('filter-btn--active');

      const filter = btn.dataset.filter;
      let visible = 0;

      cards.forEach(card => {
        const match = filter === 'all' || card.dataset.category === filter;
        card.classList.toggle('post-card--hidden', !match);
        if (match) visible++;
      });

      if (emptyMsg) emptyMsg.style.display = visible === 0 ? 'block' : 'none';
    });
  });
})();
