'use strict';
const feedbackStyles = document.createElement('link');
feedbackStyles.rel = 'stylesheet';
feedbackStyles.href = new URL('../css/feedback.css', document.currentScript.src).href;
document.head.append(feedbackStyles);
document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('shared-header-style')) return;
  const responsiveStyles = document.createElement('link');
  responsiveStyles.rel = 'stylesheet';
  responsiveStyles.href = 'assets/css/responsive.css?v=organized1';
  document.head.append(responsiveStyles);
  const headerStyles = document.createElement('link');
  headerStyles.rel = 'stylesheet';
  headerStyles.href = 'assets/css/header.css?v=audit6';
  document.head.append(headerStyles);
});
document.addEventListener('DOMContentLoaded', async () => {
  if (location.pathname.includes('/admin_')) return;
  try {
    const response = await fetch('check_session.php', {cache:'no-store'});
    if (!response.ok) return;
    const session = await response.json();
    const reviewer = document.getElementById('reviewer-name');
    if (reviewer && session.loggedIn) { reviewer.value = session.user.name; reviewer.readOnly = true; }
    const reviewNote = document.getElementById('review-account-note');
    if (reviewNote && session.loggedIn) {
      reviewNote.textContent = `Posting as ${session.user.name}. Your profile name will appear with your review.`;
    }
    document.querySelectorAll('a[href="logout.php"]').forEach(link => {
      link.hidden = !session.loggedIn;
      if (!session.loggedIn) link.style.setProperty('display', 'none', 'important');
      else link.style.removeProperty('display');
      link.setAttribute('aria-label', 'Sign out');
    });
    document.querySelectorAll('header a[href="profile.php"]').forEach(link => {
      link.href = !session.loggedIn ? 'login.html' : session.user.role === 'admin' ? 'admin_profile.php' : 'profile.php';
      link.setAttribute('aria-label', session.loggedIn ? 'My account' : 'Sign in');
      if (!session.loggedIn) {
        link.classList.add('sign-in-link');
        link.textContent = 'Sign in';
        link.title = 'Sign in to your account';
        link.style.cssText = 'width:auto;min-width:44px;padding:10px 16px;border-radius:8px;white-space:nowrap;font-size:14px';
      }
    });
  } catch { /* Keep normal navigation available during a temporary outage. */ }
});
window.GS = Object.freeze({
  notify(message) {
    let notice = document.getElementById('gs-notice');
    if (!notice) {
      notice = document.createElement('section');
      notice.id = 'gs-notice'; notice.className = 'gs-notice';
      const text = document.createElement('p');
      text.setAttribute('role', 'alert'); text.setAttribute('aria-atomic', 'true');
      const close = document.createElement('button');
      close.type = 'button'; close.textContent = 'Dismiss';
      close.setAttribute('aria-label', 'Dismiss notification');
      close.addEventListener('click', () => { notice.hidden = true; });
      notice.append(text, close); document.body.append(notice);
    }
    notice.hidden = false;
    notice.querySelector('p').textContent = String(message);
  },
  async fetch(url, options = {}) {
    const target = new URL(url || location.href, location.href);
    const headers = new Headers(options.headers);
    if (target.origin === location.origin && !['GET', 'HEAD'].includes((options.method || 'GET').toUpperCase())) {
      const tokenResponse = await fetch('csrf.php', {cache: 'no-store', signal: options.signal || AbortSignal.timeout(12000)});
      if (!tokenResponse.ok) throw new Error('Unable to verify your session. Please refresh.');
      const token = await tokenResponse.json();
      headers.set('X-CSRF-Token', token.csrfToken);
    }
    const readOnly = ['GET', 'HEAD'].includes((options.method || 'GET').toUpperCase());
    return fetch(url, {...options, headers, cache: 'no-store', signal: options.signal || (readOnly ? AbortSignal.timeout(12000) : undefined)});
  }
});
document.addEventListener('click', async event => {
  const link = event.target.closest('a[href="logout.php"]');
  if (!link) return;
  event.preventDefault();
  try {
    const result = await GS.fetch('logout.php', {method: 'POST'});
    if (!result.ok) throw new Error('Logout failed. Please try again.');
    try { sessionStorage.removeItem('greensprout-cart'); } catch { /* Optional draft. */ }
    location.assign('login.html');
  } catch (error) { GS.notify(error.message); }
});
