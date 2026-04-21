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
      x:   Math.random() * W,
      y:   Math.random() * H,
      vx:  (Math.random() - 0.5) * 0.35,
      vy:  (Math.random() - 0.5) * 0.35,
      r:   Math.random() * 2 + 1.5,
      // pulse phase offset
      phase: Math.random() * Math.PI * 2,
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
      const color = Math.random() > 0.8 ? PURPLE : BLUE;

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


/* ── 4. JOIN FORM ── */
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

    // Success state
    const btn = form.querySelector('.join__submit');
    btn.disabled = true;
    btn.textContent = 'Sending…';

    // Simulate async submit (replace with real fetch when backend is ready)
    setTimeout(() => {
      form.innerHTML = `
        <div style="
          text-align:center;
          padding: 3rem 1rem;
          display:flex;
          flex-direction:column;
          align-items:center;
          gap:1rem;
        ">
          <div style="
            width:64px; height:64px; border-radius:50%;
            background: rgba(79,142,247,0.12);
            border: 2px solid rgba(79,142,247,0.4);
            display:flex; align-items:center; justify-content:center;
            font-size:1.8rem;
          ">&#10003;</div>
          <h3 style="font-family:'Space Grotesk',sans-serif;color:#f0f4ff;font-size:1.3rem;">
            Application received!
          </h3>
          <p style="color:#8892a4;max-width:380px;font-size:0.95rem;line-height:1.65;">
            Thanks for applying to Kmind. We review applications weekly and
            will reach out within 5–7 days.
          </p>
        </div>
      `;
    }, 800);
  });
})();
