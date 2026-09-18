'use strict';
document.addEventListener('DOMContentLoaded', () => {
  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
  let pausedByChoice = false;
  try { pausedByChoice = sessionStorage.getItem('gs-video-paused') === 'true'; } catch {}
  const media = [...document.querySelectorAll('video[autoplay]')].map(video => {
    let explicitlyPlaying = false;
    const control = document.createElement('button');
    control.type = 'button';
    control.className = 'video-toggle';
    const label = () => {
      control.textContent = video.paused ? 'Play video' : 'Pause video';
      control.setAttribute('aria-label', (video.paused ? 'Play' : 'Pause') + ' café video');
    };
    const shouldPause = () => pausedByChoice || (reducedMotion.matches && !explicitlyPlaying);
    video.addEventListener('play', () => { if (shouldPause()) video.pause(); label(); });
    video.addEventListener('pause', label);
    control.addEventListener('click', () => {
      pausedByChoice = !video.paused;
      try { sessionStorage.setItem('gs-video-paused', String(pausedByChoice)); } catch {}
      if (video.paused) { explicitlyPlaying = true; video.play().catch(label); }
      else media.forEach(item => item.video.pause());
    });
    video.parentElement.append(control);
    if (shouldPause()) { video.removeAttribute('autoplay'); video.pause(); }
    label();
    return {video, resetMotion: () => { explicitlyPlaying = false; video.pause(); }};
  });
  reducedMotion.addEventListener('change', () => {
    if (reducedMotion.matches) media.forEach(item => item.resetMotion());
  });
});
