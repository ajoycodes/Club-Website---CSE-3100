/* ============================================================
   KMIND ML CLUB — script.js
   1. Navbar scroll behaviour
   2. Hamburger mobile menu
   3. Neural network canvas animation
   4. Join form validation + feedback
   ============================================================ */

/* ── 1. NAVBAR ── */
(function () {
  const navbar = document.getElementById('navbar');
  if (!navbar) return;

  function onScroll() {
    navbar.classList.toggle('navbar--scrolled', window.scrollY > 20);
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll(); // run once on load
})();


/* ── 2. HAMBURGER ── */
(function () {
  const hamburger = document.getElementById('hamburger');
  const navLinks  = document.getElementById('navLinks');
  if (!hamburger || !navLinks) return;

  hamburger.addEventListener('click', () => {
    const open = hamburger.classList.toggle('is-open');
    navLinks.classList.toggle('is-open', open);
    hamburger.setAttribute('aria-expanded', open);
  });

  // Close menu when a nav link is clicked
  navLinks.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      hamburger.classList.remove('is-open');
      navLinks.classList.remove('is-open');
      hamburger.setAttribute('aria-expanded', false);
    });
  });
})();


/* ── 3. NEURAL CANVAS ── */
(function () {
  const canvas = document.getElementById('neuralCanvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');

  const BLUE   = 'rgba(79, 142, 247,';
  const PURPLE = 'rgba(155, 89, 182,';
  const NODE_COUNT = 55;
  const CONNECT_DIST = 160;
  const PULSE_SPEED  = 0.012;

  let W, H, nodes;

  function resize() {
    W = canvas.width  = canvas.offsetWidth;
    H = canvas.height = canvas.offsetHeight;
  }

  function randomNode() {
    return {
      x:     Math.random() * W,
      y:     Math.random() * H,
      vx:    (Math.random() - 0.5) * 0.35,
      vy:    (Math.random() - 0.5) * 0.35,
      r:     Math.random() * 2 + 1.5,
      phase: Math.random() * Math.PI * 2,
      // assign color once on creation — not every frame
      color: Math.random() > 0.8 ? PURPLE : BLUE,
    };
  }

  function init() {
    resize();
    nodes = Array.from({ length: NODE_COUNT }, randomNode);
  }

  function draw(t) {
    ctx.clearRect(0, 0, W, H);

    // edges
    for (let i = 0; i < nodes.length; i++) {
      for (let j = i + 1; j < nodes.length; j++) {
        const a = nodes[i], b = nodes[j];
        const dx = a.x - b.x, dy = a.y - b.y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist > CONNECT_DIST) continue;

        const alpha = (1 - dist / CONNECT_DIST) * 0.45;
        ctx.beginPath();
        ctx.moveTo(a.x, a.y);
        ctx.lineTo(b.x, b.y);
        ctx.strokeStyle = `${BLUE} ${alpha})`;
        ctx.lineWidth = 0.8;
        ctx.stroke();
      }
    }

    // nodes
    nodes.forEach(n => {
      const pulse = 0.6 + 0.4 * Math.sin(t * PULSE_SPEED + n.phase);
      const color = n.color;

      ctx.beginPath();
      ctx.arc(n.x, n.y, n.r * pulse, 0, Math.PI * 2);
      ctx.fillStyle = `${color} ${0.7 * pulse})`;
      ctx.fill();

      // subtle glow
      ctx.beginPath();
      ctx.arc(n.x, n.y, n.r * pulse * 2.5, 0, Math.PI * 2);
      ctx.fillStyle = `${color} ${0.08 * pulse})`;
      ctx.fill();
    });
  }

  function update() {
    nodes.forEach(n => {
      n.x += n.vx;
      n.y += n.vy;
      if (n.x < 0 || n.x > W) n.vx *= -1;
      if (n.y < 0 || n.y > H) n.vy *= -1;
    });
  }

  let frame = 0;
  function loop() {
    update();
    draw(frame++);
    requestAnimationFrame(loop);
  }

  window.addEventListener('resize', () => {
    resize();
    // redistribute nodes that are now out of bounds
    nodes.forEach(n => {
      if (n.x > W) n.x = W - 10;
      if (n.y > H) n.y = H - 10;
    });
  }, { passive: true });

  init();
  loop();
})();


/* ── 4. ACTIVE NAV STATE ── */
(function () {
  const navLinks = document.querySelectorAll('.navbar__links a[href^="#"]');
  const sections = document.querySelectorAll('main section[id]');
  if (!navLinks.length || !sections.length) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        navLinks.forEach(l => l.classList.remove('is-active'));
        const link = document.querySelector(`.navbar__links a[href="#${entry.target.id}"]`);
        if (link) link.classList.add('is-active');
      }
    });
  }, { threshold: 0.35, rootMargin: '0px 0px -10% 0px' });

  sections.forEach(s => observer.observe(s));
})();


/* ── 5. COUNT-UP STATS ── */
(function () {
  const statNumbers = document.querySelectorAll('.stat-card__number');
  if (!statNumbers.length) return;

  function animateCount(el) {
    // Read the target from the text node (excludes the .stat-card__plus child)
    const plusEl = el.querySelector('.stat-card__plus');
    const rawText = el.childNodes[0] ? el.childNodes[0].textContent.trim() : '';
    const target = parseInt(rawText, 10);
    if (isNaN(target)) return;

    const duration = 1400;
    const start = performance.now();

    function tick(now) {
      const elapsed = now - start;
      const progress = Math.min(elapsed / duration, 1);
      // ease-out cubic
      const eased = 1 - Math.pow(1 - progress, 3);
      const current = Math.floor(eased * target);

      // Update only the text node, preserving the + child
      el.childNodes[0].textContent = current;

      if (progress < 1) {
        requestAnimationFrame(tick);
      } else {
        el.childNodes[0].textContent = target;
      }
    }

    requestAnimationFrame(tick);
  }

  if (!('IntersectionObserver' in window)) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        animateCount(entry.target);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.6 });

  statNumbers.forEach(el => observer.observe(el));
})();


/* ── 6. SCROLL REVEAL ── */
(function () {
  const els = document.querySelectorAll('[data-reveal]');
  if (!els.length) return;

  // Apply stagger delay from data attribute
  els.forEach(el => {
    const delay = el.getAttribute('data-reveal-delay');
    if (delay) el.style.setProperty('--reveal-delay', delay + 'ms');
  });

  // Graceful fallback if IntersectionObserver unavailable
  if (!('IntersectionObserver' in window)) {
    els.forEach(el => {
      el.style.opacity = '1';
      el.style.transform = 'none';
    });
    return;
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

  els.forEach(el => observer.observe(el));
})();


/* ── 7. JOIN FORM ── */
(function () {
  const form = document.getElementById('joinForm');
  if (!form) return;

  function showError(input, msg) {
    input.style.borderColor = '#fc8181';
    input.style.boxShadow   = '0 0 0 3px rgba(252, 129, 129, 0.15)';
    let err = input.parentElement.querySelector('.form-error');
    if (!err) {
      err = document.createElement('span');
      err.className = 'form-error';
      err.style.cssText = 'font-size:0.78rem;color:#fc8181;margin-top:2px;';
      input.parentElement.appendChild(err);
    }
    err.textContent = msg;
  }

  function clearError(input) {
    input.style.borderColor = '';
    input.style.boxShadow   = '';
    const err = input.parentElement.querySelector('.form-error');
    if (err) err.remove();
  }

  // Live clear-on-fix
  form.querySelectorAll('.form-input').forEach(input => {
    input.addEventListener('input', () => clearError(input));
  });

  form.addEventListener('submit', e => {
    e.preventDefault();
    let valid = true;

    const firstName  = form.querySelector('#firstName');
    const lastName   = form.querySelector('#lastName');
    const email      = form.querySelector('#email');
    const background = form.querySelector('#background');

    [firstName, lastName, email, background].forEach(f => clearError(f));

    if (!firstName.value.trim()) {
      showError(firstName, 'First name is required.');
      valid = false;
    }
    if (!lastName.value.trim()) {
      showError(lastName, 'Last name is required.');
      valid = false;
    }
    if (!email.value.trim()) {
      showError(email, 'Email address is required.');
      valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
      showError(email, 'Please enter a valid email address.');
      valid = false;
    }
    if (!background.value) {
      showError(background, 'Please select your background level.');
      valid = false;
    }

    if (!valid) return;

    // Redirect to signup.php with pre-filled values
    const btn = form.querySelector('.join__submit');
    btn.disabled = true;
    btn.textContent = 'Redirecting…';

    const params = new URLSearchParams({
      first_name: firstName.value.trim(),
      last_name:  lastName.value.trim(),
      email:      email.value.trim(),
      background: background.value,
      interest:   (form.querySelector('#interest')?.value) || '',
    });
    window.location.href = '/signup.php?' + params.toString();
  });
})();
