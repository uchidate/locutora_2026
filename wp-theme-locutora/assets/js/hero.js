document.addEventListener('DOMContentLoaded', () => {
  const menuButton = document.querySelector('.site-menu-toggle');
  const menu = document.querySelector('.site-nav');
  menuButton?.addEventListener('click', () => {
    const open = menuButton.getAttribute('aria-expanded') === 'true';
    menuButton.setAttribute('aria-expanded', String(!open));
    menu?.classList.toggle('is-open', !open);
  });

  const header = document.querySelector('.site-header');
  if (header) {
    const range = 80;
    const updateCompact = () => {
      const progress = Math.min(Math.max(window.scrollY / range, 0), 1);
      header.style.setProperty('--hdr', String(progress));
      header.classList.toggle('is-compact', progress > 0.03);
    };
    updateCompact();
    window.addEventListener('scroll', updateCompact, { passive: true });
  }

  const revealItems = Array.from(document.querySelectorAll('.reveal'));
  if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.documentElement.classList.add('reveal-ready');
    const revealObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-revealed');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.16, rootMargin: '0px 0px -8% 0px' });
    revealItems.forEach((item) => revealObserver.observe(item));
  } else {
    revealItems.forEach((item) => item.classList.add('is-revealed'));
  }

  const heroSection = document.querySelector('.hero');
  const firstVideo = document.querySelector('.hero__video');
  if (heroSection && firstVideo) {
    const markVideoReady = () => heroSection.classList.add('is-video-ready');
    if (firstVideo.readyState >= 2) {
      markVideoReady();
    } else {
      firstVideo.addEventListener('loadeddata', markVideoReady, { once: true });
    }
    window.setTimeout(markVideoReady, 1200);
  }

  const videos = Array.from(document.querySelectorAll('.hero__video'));
  if (videos.length < 2) return;

  let current = 0;
  const playNext = () => {
    videos[current].classList.remove('is-active');
    videos[current].pause();
    current = (current + 1) % videos.length;
    videos[current].currentTime = 0;
    videos[current].classList.add('is-active');
    videos[current].play().catch(() => {});
  };

  videos.forEach((video) => video.addEventListener('ended', playNext));
  videos[0].play().catch(() => {});

  const services = document.querySelector('.services-grid');
  const dots = Array.from(document.querySelectorAll('.services-dots__item'));
  if (services) {
    let serviceIndex = 0;
    const setActiveDot = (index) => {
      dots.forEach((dot, i) => dot.classList.toggle('is-active', i === index));
    };
    window.setInterval(() => {
      const cards = services.querySelectorAll('.service-item');
      if (cards.length < 2) return;
      serviceIndex = (serviceIndex + 1) % cards.length;
      const card = cards[serviceIndex];
      services.scrollTo({ left: card.offsetLeft - services.offsetLeft, behavior: 'smooth' });
      setActiveDot(serviceIndex);
    }, 4500);
    dots.forEach((dot, index) => {
      dot.addEventListener('click', () => {
        const cards = services.querySelectorAll('.service-item');
        const card = cards[index];
        if (!card) return;
        serviceIndex = index;
        services.scrollTo({ left: card.offsetLeft - services.offsetLeft, behavior: 'smooth' });
        setActiveDot(index);
      });
    });
  }
});
