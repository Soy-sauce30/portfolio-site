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

  let delay = isDeleting ? 40 : 80;

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

setTimeout(type, 1000);

/* =========================================
   Nav Scroll Effect
   ========================================= */
const nav = document.getElementById('nav');

window.addEventListener('scroll', () => {
  nav.classList.toggle('scrolled', window.scrollY > 50);
}, { passive: true });

/* =========================================
   Scroll-triggered Fade In
   ========================================= */
const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      entry.target.classList.add('animate-in');
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });

document.querySelectorAll(
  '.skill-card, .project-card, .about-grid > *, .contact-card'
).forEach(el => {
  el.style.opacity = '0';
  observer.observe(el);
});
