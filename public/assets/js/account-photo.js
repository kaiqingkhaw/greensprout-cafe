(() => {
    const input = document.getElementById('profile_image');
    const remove = document.getElementById('remove_photo');
    const button = document.getElementById('remove-photo-button');
    const avatar = document.querySelector('.profile-avatar');
    const feedback = document.getElementById('photo-feedback');
    let previewUrl;
    document.getElementById('choose-photo').addEventListener('click', () => input.click());
    button.addEventListener('click', () => {
        input.value = ''; remove.value = '1';
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        const icon = document.createElement('i'); icon.className = 'fas fa-user'; icon.setAttribute('aria-hidden', 'true');
        avatar.replaceChildren(icon); button.hidden = true;
        feedback.textContent = 'Photo removed from preview. Save Changes to confirm.';
    });
    input.addEventListener('change', () => {
        const file = input.files[0]; if (!file) return;
        if (!['image/jpeg','image/png','image/gif'].includes(file.type) || file.size > 2097152) {
            input.value = ''; feedback.textContent = 'Choose a JPG, PNG or GIF under 2 MB.'; return;
        }
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);
        const image = document.createElement('img'); image.src = previewUrl; image.alt = 'New profile photo preview';
        avatar.replaceChildren(image); remove.value = '0'; button.hidden = false;
        feedback.textContent = 'New photo ready. Save Changes to confirm.';
    });
})();
