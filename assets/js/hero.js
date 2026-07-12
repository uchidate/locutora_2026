document.addEventListener('DOMContentLoaded', () => {
  const menuButton = document.querySelector('.site-menu-toggle');
  const menu = document.querySelector('.site-nav');
  menuButton?.addEventListener('click', () => {
    const open = menuButton.getAttribute('aria-expanded') === 'true';
    menuButton.setAttribute('aria-expanded', String(!open));
    menu?.classList.toggle('is-open', !open);
  });

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
  if (services) {
    let serviceIndex = 0;
    window.setInterval(() => {
      const cards = services.querySelectorAll('.service-item');
      if (cards.length < 2) return;
      serviceIndex = (serviceIndex + 1) % cards.length;
      const card = cards[serviceIndex];
      services.scrollTo({ left: card.offsetLeft - services.offsetLeft, behavior: 'smooth' });
    }, 4500);
  }
});
