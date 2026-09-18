'use strict';
document.addEventListener('DOMContentLoaded', () => {
  const signup = Boolean(document.getElementById('signupForm'));
  const form = document.getElementById(signup ? 'signupForm' : 'loginForm');
  const errorBox = document.getElementById(signup ? 'signupError' : 'loginError');
  const successBox = document.getElementById('loginSuccess');
  const remember = document.getElementById('rememberMe');
  let pending = false;
  form.addEventListener('input', event => {
    event.target.removeAttribute('aria-invalid');
    event.target.removeAttribute('aria-errormessage');
    errorBox.style.display = 'none';
  });
  const showError = message => {
    errorBox.textContent = message;
    errorBox.style.display = 'block';
    errorBox.focus();
  };
  document.querySelectorAll('.password-toggle').forEach(button => {
    const field = document.getElementById(button.dataset.passwordTarget);
    const label = field.name === 'confirm_password' ? 'confirmed password' : 'password';
    button.addEventListener('click', () => {
      const visible = field.type === 'password';
      field.type = visible ? 'text' : 'password';
      button.setAttribute('aria-label', (visible ? 'Hide ' : 'Show ') + label);
      button.setAttribute('aria-pressed', String(visible));
      button.querySelector('i').className = visible ? 'fas fa-eye-slash' : 'fas fa-eye';
    });
  });
  const params = new URLSearchParams(location.search);
  const returnToCheckout = params.get('next') === 'checkout';
  if (returnToCheckout) {
    document.querySelectorAll('a[href="signup.html"], a[href="login.html"]').forEach(link => {
      link.href += '?next=checkout';
    });
  }
  if (params.get('error')) showError(params.get('error'));
  if (successBox && params.get('signup') === 'success') {
    successBox.textContent = 'Account created. Log in with your username and password.';
    successBox.style.display = 'block';
  }
  if (remember) {
    try {
      const saved = localStorage.getItem('savedUsername');
      if (saved) { form.elements.username.value = saved; remember.checked = true; }
    } catch { /* Storage is optional; authentication must still work. */ }
    const help = document.getElementById('forgotContainer');
    const link = document.getElementById('forgotLink');
    const close = document.getElementById('closeForgot');
    help.hidden = true;
    const dismiss = () => { help.hidden = true; help.classList.remove('active'); form.inert = false; link.focus(); };
    link.addEventListener('click', event => {
      event.preventDefault(); help.hidden = false; help.classList.add('active'); form.inert = true; close.focus();
    });
    close.addEventListener('click', dismiss);
    help.addEventListener('keydown', event => {
      if (event.key === 'Escape') dismiss();
      if (event.key === 'Tab') {
        const last = help.querySelector('a');
        if (event.shiftKey && document.activeElement === close) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); close.focus(); }
      }
    });
  }
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (pending) return;
    const invalid = Array.from(form.elements).find(field => field.willValidate && !field.validity.valid);
    if (invalid) {
      const label = invalid.getAttribute('aria-label') || invalid.name.replaceAll('_', ' ');
      showError(invalid.validity.valueMissing ? 'Please enter your ' + label.toLowerCase() + '.' :
        invalid.validity.typeMismatch ? 'Please enter a valid ' + label.toLowerCase() + '.' : invalid.validationMessage);
      invalid.setAttribute('aria-invalid', 'true');
      invalid.setAttribute('aria-errormessage', errorBox.id);
      invalid.focus();
      return;
    }
    errorBox.style.display = 'none';
    if (successBox) successBox.style.display = 'none';
    const data = Object.fromEntries(new FormData(form));
    if (signup && data.password !== data.confirm_password) {
      showError('Passwords do not match.');
      const confirmation = form.elements.confirm_password;
      confirmation.setAttribute('aria-invalid', 'true');
      confirmation.setAttribute('aria-errormessage', errorBox.id);
      confirmation.focus();
      return;
    }
    if (new TextEncoder().encode(data.password).length > 72) { showError('Password is too long. Use up to 72 bytes (fewer characters for emoji).'); return; }
    const button = form.querySelector('button[type="submit"]');
    const label = button.textContent;
    pending = true; button.disabled = true; form.setAttribute('aria-busy', 'true');
    button.textContent = signup ? 'Creating account…' : 'Logging in…';
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 15000);
    try {
      const response = await GS.fetch(signup ? 'signup.php' : 'login.php', {
        method: 'POST', signal: controller.signal,
        headers: { 'Content-Type': signup ? 'application/json' : 'application/x-www-form-urlencoded' },
        body: signup ? JSON.stringify(data) : new URLSearchParams(data)
      });
      let result;
      try { result = await response.json(); } catch { throw new Error('The server returned an unexpected response. Please try again.'); }
      if (!response.ok || !result.success) throw new Error(result.error || 'Unable to continue. Please try again.');
      if (remember) {
        try {
          if (remember.checked) localStorage.setItem('savedUsername', data.username.trim());
          else localStorage.removeItem('savedUsername');
        } catch { /* Never store passwords, and do not require browser storage. */ }
      }
      const allowed = signup ? ['login.html?signup=success'] : ['MainMenu.html', 'admin_home.php'];
      if (!allowed.includes(result.redirect)) throw new Error('Login response was unexpected. Please refresh and try again.');
      const destination = returnToCheckout
        ? signup ? 'login.html?signup=success&next=checkout'
          : result.redirect === 'MainMenu.html' ? 'menu.html?view=checkout' : result.redirect
        : result.redirect;
      location.assign(destination);
    } catch (error) {
      showError(error.name === 'AbortError' ? 'The request timed out. Check your connection; if signup completed, try logging in.' :
        error instanceof TypeError ? 'Cannot reach the café server. Check that Apache and MySQL are running.' : error.message);
    } finally {
      clearTimeout(timeout); pending = false; button.disabled = false; button.textContent = label; form.removeAttribute('aria-busy');
    }
  });
});
