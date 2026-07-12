document.addEventListener('DOMContentLoaded', () => {
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
});
