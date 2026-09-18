'use strict';
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('contactForm');
  const message = document.getElementById('successMessage');
  Object.entries({name:100,email:190,phone:30,message:2000}).forEach(([key, limit]) => form.elements[key].maxLength = limit);
  const showErrors = errors => {
    for (const [name, text] of Object.entries(errors)) {
      const field = form.elements.namedItem(name);
      if (!field) continue;
      const group = field.closest('.form-group');
      let error = group.querySelector('.error-message');
      if (!error) { error = document.createElement('div'); error.className = 'error-message'; group.append(error); }
      error.id = 'contact-' + name + '-error'; error.textContent = text;
      group.classList.add('error'); field.setAttribute('aria-invalid', 'true'); field.setAttribute('aria-errormessage', error.id);
    }
    form.querySelector('[aria-invalid="true"]')?.focus();
  };
  form.addEventListener('input', event => {
    event.target.closest('.form-group')?.classList.remove('error');
    event.target.removeAttribute('aria-invalid'); event.target.removeAttribute('aria-errormessage');
    message.hidden = true;
  });
  form.addEventListener('submit', async event => {
    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    if (button.disabled) return;
    form.querySelectorAll('.form-group').forEach(group => group.classList.remove('error'));
    form.querySelectorAll('[aria-invalid]').forEach(field => { field.removeAttribute('aria-invalid'); field.removeAttribute('aria-errormessage'); });
    message.hidden = true;
    const data = new FormData(form), errors = {};
    if (!data.get('name').trim()) errors.name = 'Enter your full name.';
    if (!data.get('email').trim() || !form.elements.email.validity.valid) errors.email = 'Enter a valid email address.';
    if (!data.get('subject')) errors.subject = 'Choose a subject.';
    if (data.get('message').trim().length < 10) errors.message = 'Write at least 10 characters so we can understand your request.';
    if (Object.keys(errors).length) { showErrors(errors); return; }
    const label = button.textContent;
    button.disabled = true; button.textContent = 'Saving message…'; form.setAttribute('aria-busy', 'true');
    try {
      const response = await GS.fetch('contact.php', {method:'POST', body:data});
      const result = await response.json();
      if (!response.ok) {
        if (result.errors) { showErrors(result.errors); return; }
        throw new Error(result.error || 'Your message could not be saved. Please try again.');
      }
      form.reset(); message.dataset.state = 'success'; message.setAttribute('role', 'status');
      message.textContent = 'Your message has been saved. Thank you.'; message.hidden = false;
    } catch (error) {
      message.dataset.state = 'error'; message.setAttribute('role', 'alert');
      message.textContent = error instanceof TypeError ? 'Unable to reach the café. Your entries are still here—please try again.' : error.message;
      message.hidden = false;
    } finally { button.disabled = false; button.textContent = label; form.removeAttribute('aria-busy'); }
  });
});
