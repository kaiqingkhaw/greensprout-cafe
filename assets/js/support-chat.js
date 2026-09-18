/* Local FAQ assistant. Messages are not transmitted to café staff. */
(function () {
    'use strict';
    const popup = document.getElementById('chat-popup');
    if (!popup) return;
    const launcher = document.querySelector('.chat-launcher');
    const body = document.getElementById('chat-body');
    const input = document.getElementById('user-input');
    const typing = document.getElementById('typing-indicator');
    const log = document.createElement('div');
    log.setAttribute('role', 'log');
    log.setAttribute('aria-live', 'polite');
    log.setAttribute('aria-label', 'Conversation');
    body.insertBefore(log, typing);
    let busy = false;
    input.maxLength = 500;
    typing.querySelector('span').textContent = 'Preparing a reply…';
    const answers = {
        menu: ['Browse breakfast, mains, drinks, and desserts on the menu. Select an item for current details and pricing.', 'Browse menu', 'menu.html'],
        order: ['You can browse and add dishes as a guest. When you check out, sign in or create an account—your cart will stay with you. Then enter your delivery details and review the total.', 'Browse the menu', 'menu.html'],
        tracking: ['Waiting for an order? Open your profile and select its tracking link. “Order received” changes to “Preparing” when the café updates it. The page checks for updates every 5 seconds; it is order-status tracking, not live GPS.', 'View my orders', 'profile.php'],
        delivery: ['This demo supports delivery within Kuala Lumpur for a flat RM5 fee. Checkout also shows a 5% service charge. Enter your Kuala Lumpur delivery address to continue.', 'Browse the menu', 'menu.html'],
        payment: ['Choose cash on delivery, card demo or e-wallet demo at checkout. Card and e-wallet selections are demonstrations only: no money is charged and no real payment details are collected.', 'Open the menu', 'menu.html'],
        contact: ['Need help beyond these FAQs? Use the contact form. This chat cannot send messages to staff, change an order, or issue refunds. Contact-form submissions are saved in this demo; there is no live agent or email delivery.', 'Open contact form', 'About.us.html#contactForm'],
        hours: ['The opening hours listed on our About page are Monday–Sunday, 8:00 AM–9:00 PM. Check with the café for holiday changes.', 'Café information', 'About.us.html'],
        diet: ['Check each item’s ingredients and dietary information. I cannot guarantee that a dish is vegan or allergen-free; confirm dietary requirements with the café before ordering.', 'View menu', 'menu.html'],
        sourcing: ['Our About page describes the café’s approach to organic ingredients and local sourcing. For information about a particular ingredient or supplier, contact the café.', 'About GreenSprout', 'About.us.html'],
        location: ['The address listed on the website is Lot No. 251, Jalan Bukit Bintang, 55100 Kuala Lumpur, Malaysia.', 'Contact details', 'About.us.html'],
        account: ['Sign in with your customer account before ordering. You can update your details from your profile. Never share your password in this chat.', 'Sign in', 'login.html'],
        fallback: ['I’m not sure which café topic you mean. Try asking “How much is delivery?”, “Where is my order?” or “What time do you open?” For something else, use the contact form.', 'Contact the café', 'About.us.html#contactForm']
    };
    function resolve(text) {
        const q = text.toLowerCase().replace(/[’']/g, '');
        if (/cancel|refund|complaint|human|staff|contact|person|agent/.test(q)) return answers.contact;
        if (/track|status|prepar|received|where.*order|order.*where|arriv|late|ready|completed/.test(q)) return answers.tracking;
        if (/payment|pay\b|cash|card|paypal|credit/.test(q)) return answers.payment;
        if (/deliver|shipping|service charge/.test(q)) return answers.delivery;
        if (/allerg|vegan|vegetarian|gluten|diet/.test(q)) return answers.diet;
        if (/ingredient|source|sourcing|organic/.test(q)) return answers.sourcing;
        if (/hour|open|clos/.test(q)) return answers.hours;
        if (/where|location|address/.test(q)) return answers.location;
        if (/order|cart|checkout/.test(q)) return answers.order;
        if (/login|log in|sign in|account|password|profile/.test(q)) return answers.account;
        if (/menu|food|price|cost|drink|breakfast/.test(q)) return answers.menu;
        if (/^(hi|hello|hey)[!. ]*$/.test(q)) return ['Hello! What would you like to know about GreenSprout? Try a suggested question.'];
        if (/thank/.test(q)) return ['You’re welcome. Let me know if you need help with anything else.'];
        return answers.fallback;
    }
    function message(text, user, link) {
        const bubble = document.createElement('div');
        bubble.className = user ? 'user-message' : 'bot-message';
        const content = document.createElement('div');
        content.className = 'message-content';
        content.textContent = text;
        bubble.appendChild(content);
        if (link) {
            const a = document.createElement('a');
            a.href = link[1];
            a.textContent = link[0] + ' →';
            a.className = 'chat-answer-link';
            bubble.appendChild(a);
        }
        log.appendChild(bubble);
        body.scrollTop = body.scrollHeight;
    }
    function send(text) {
        text = text.trim().slice(0, 500);
        if (!text || busy) return;
        busy = true;
        message(text, true);
        input.value = '';
        typing.style.display = 'flex';
        popup.querySelectorAll('.quick-reply, .send-btn').forEach(b => b.disabled = true);
        body.scrollTop = body.scrollHeight;
        {
            const reply = resolve(text);
            typing.style.display = 'none';
            message(reply[0], false, reply[1] ? reply.slice(1) : null);
            busy = false;
            popup.querySelectorAll('.quick-reply, .send-btn').forEach(b => b.disabled = false);
        }
    }
    window.toggleChat = function () {
        const open = popup.getAttribute('aria-hidden') === 'true';
        popup.style.display = open ? 'flex' : 'none';
        popup.setAttribute('aria-hidden', String(!open));
        launcher.setAttribute('aria-expanded', String(open));
        if (open) {
            document.getElementById('current-time').textContent = 'Quick answers from our café guide';
            input.focus({preventScroll: true});
        } else launcher.focus({preventScroll: true});
    };
    window.sendQuickReply = send;
    window.sendUserMessage = function () { send(input.value); };
    window.updateTimestamp = function () {};
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.isComposing) { e.preventDefault(); send(input.value); }
    });
    popup.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') window.toggleChat();
    });
})();
