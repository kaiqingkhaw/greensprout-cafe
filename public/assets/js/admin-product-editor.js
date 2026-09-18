'use strict';
(() => {
    const form = document.getElementById('productForm');
    const error = document.createElement('p');
    error.hidden = true; error.setAttribute('role', 'alert'); error.tabIndex = -1;
    error.style.cssText = 'padding:14px 18px;border:1px solid #e8b6af;background:#fff4f2;color:#873a30;border-radius:10px;';
    form.prepend(error);
    Object.entries({name:255,description:5000,image:500}).forEach(([key,max]) => {form.elements[key].maxLength = max;});
    for (const field of form.querySelectorAll('input:not([type=hidden]),textarea,select')) {
        field.id ||= 'product-' + field.name;
        const label = field.closest('.form-group,.nutrition-item')?.querySelector('label');
        if (label && !['checkbox'].includes(field.type)) label.htmlFor = field.id;
        if (['calories','protein','carbs','fats','fiber'].includes(field.name)) field.max = '65535';
    }
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const button = form.querySelector('button[type=submit]');
        if (button.disabled) return;
        error.hidden = true;
        const image = form.elements.image.value.trim();
        if (image && !/^https:\/\//i.test(image)) {error.textContent = 'Use an HTTPS image URL, or leave it blank.';error.hidden = false;form.elements.image.focus();return;}
        const data = new FormData(form);
        button.disabled = true; form.setAttribute('aria-busy','true');
        try {
            const response = await GS.fetch(location.href, {method:'POST',headers:{Accept:'application/json'},body:data});
            if (!response.ok) {
                const result = await response.json(); throw new Error(result.error || 'Unable to save this product.');
            }
            const result = await response.json();
            if (!result.success || result.redirect !== 'admin_manage_menu.php') throw new Error('Your session may have expired or a required field is missing. Your entries are still here.');
            location.assign('admin_manage_menu.php');
        } catch (problem) {
            error.textContent = problem instanceof TypeError ? 'Unable to reach the server. Your entries have been kept; please retry.' : problem.message;
            error.hidden = false; error.focus();
        } finally {button.disabled = false;form.removeAttribute('aria-busy');}
    });
})();
