/* =========================================
   Particle Canvas Background
   ========================================= */
const canvas = document.getElementById('heroCanvas');
const ctx = canvas.getContext('2d');
let particles = [];

function resizeCanvas() {
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;
}

const COLORS = ['#6366f1', '#a855f7', '#ec4899', '#3b82f6'];

class Particle {
  constructor() {
    this.reset(true);
  }

  reset(initial) {
    this.x = initial ? Math.random() * canvas.width : (Math.random() < 0.5 ? -5 : canvas.width + 5);
    this.y = Math.random() * canvas.height;
    this.size = Math.random() * 1.5 + 0.4;
    this.speedX = (Math.random() - 0.5) * 0.5;
    this.speedY = (Math.random() - 0.5) * 0.5;
    this.opacity = Math.random() * 0.55 + 0.1;
    this.color = COLORS[Math.floor(Math.random() * COLORS.length)];
  }

  update() {
    this.x += this.speedX;
    this.y += this.speedY;
    if (this.x < -20 || this.x > canvas.width + 20 ||
        this.y < -20 || this.y > canvas.height + 20) {
      this.reset(false);
    }
  }

  draw() {
    ctx.save();
    ctx.globalAlpha = this.opacity;
    ctx.fillStyle = this.color;
    ctx.shadowColor = this.color;
    ctx.shadowBlur = 8;
    ctx.beginPath();
    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
    ctx.fill();
    ctx.restore();
  }
}

function initParticles() {
  particles = [];
  const density = (canvas.width * canvas.height) / 14000;
  const count = Math.min(Math.floor(density), 100);
  for (let i = 0; i < count; i++) {
    particles.push(new Particle());
  }
}

function connectParticles() {
  const maxDist = 110;
  for (let i = 0; i < particles.length; i++) {
    for (let j = i + 1; j < particles.length; j++) {
      const dx = particles[i].x - particles[j].x;
      const dy = particles[i].y - particles[j].y;
      const dist = Math.sqrt(dx * dx + dy * dy);
      if (dist < maxDist) {
        ctx.save();
        ctx.globalAlpha = (1 - dist / maxDist) * 0.1;
        ctx.strokeStyle = '#6366f1';
        ctx.lineWidth = 0.5;
        ctx.beginPath();
        ctx.moveTo(particles[i].x, particles[i].y);
        ctx.lineTo(particles[j].x, particles[j].y);
        ctx.stroke();
        ctx.restore();
      }
    }
  }
}

function animate() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  particles.forEach(p => { p.update(); p.draw(); });
  connectParticles();
  requestAnimationFrame(animate);
}

resizeCanvas();
initParticles();
animate();

window.addEventListener('resize', () => {
  resizeCanvas();
  initParticles();
});

/* =========================================
   Typing Effect
   ========================================= */
const phrases = [
  'beautiful interfaces.',
  'fast web apps.',
  'clean code.',
  'digital experiences.',
  'things that matter.',
];

let phraseIndex = 0;
let charIndex = 0;
let isDeleting = false;
const typedEl = document.getElementById('typedText');

function type() {
  const current = phrases[phraseIndex];

  if (isDeleting) {
    charIndex--;
    typedEl.textContent = current.slice(0, charIndex);
  } else {
    charIndex++;
    typedEl.textContent = current.slice(0, charIndex);
  }

  let delay = isDeleting ? 45 : 85;

  if (!isDeleting && charIndex === current.length) {
    delay = 2200;
    isDeleting = true;
  } else if (isDeleting && charIndex === 0) {
    isDeleting = false;
    phraseIndex = (phraseIndex + 1) % phrases.length;
    delay = 350;
  }

  setTimeout(type, delay);
}

setTimeout(type, 1400);

/* =========================================
   Nav Scroll Effect
   ========================================= */
const nav = document.getElementById('nav');

window.addEventListener('scroll', () => {
  nav.classList.toggle('scrolled', window.scrollY > 50);
}, { passive: true });

/* =========================================
   Scroll-triggered Animations
   ========================================= */
const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry, i) => {
    if (entry.isIntersecting) {
      entry.target.style.animationDelay = `${i * 0.08}s`;
      entry.target.classList.add('animate-in');
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

document.querySelectorAll(
  '.skill-card, .project-card, .about-grid > *, .contact-card'
).forEach(el => {
  el.style.opacity = '0';
  observer.observe(el);
});