// Presentation-only test fields. Never include these values in requests or storage.
window.DemoPayment = (() => {
    const field = name => document.getElementById('demo-card-' + name);
    const error = document.getElementById('card-error');
    const expiry = () => '12 / ' + String(new Date().getFullYear() + 2).slice(-2);
    let walletApproved = false, walletContext = '';
    const walletButton = document.getElementById('approve-demo-wallet');
    const walletStatus = document.getElementById('wallet-status');
    function resetWallet() {
        walletApproved = false;
        walletButton.disabled = false;
        walletButton.textContent = 'Simulate wallet approval →';
        walletStatus.textContent = 'Awaiting demo approval';
    }
    function setContext(amount, items) {
        const nextContext = amount + items;
        if (nextContext !== walletContext) resetWallet();
        walletContext = nextContext;
        document.getElementById('wallet-amount').textContent = amount;
    }
    walletButton.addEventListener('click', () => {
        walletApproved = true;
        walletButton.disabled = true;
        walletButton.textContent = 'Demo approval complete ✓';
        walletStatus.textContent = 'Ready to place your demo order. No money has moved.';
    });
    function clear() {
        resetWallet();
        ['name','number','expiry','cvc'].forEach(name => { field(name).value = ''; field(name).removeAttribute('aria-invalid'); });
        error.hidden = true;
    }
    function select(method) {
        document.getElementById('cash-panel').hidden = method !== 'cash';
        document.getElementById('card-panel').hidden = method !== 'card_demo';
        document.getElementById('wallet-panel').hidden = method !== 'ewallet_demo';
        if (method !== 'card_demo') clear();
    }
    document.getElementById('fill-test-card').addEventListener('click', () => {
        clear(); field('name').value = 'TEST CUSTOMER'; field('number').value = '4242 4242 4242 4242';
        field('expiry').value = expiry(); field('cvc').value = '123';
    });
    field('number').addEventListener('input', () => { field('number').value = field('number').value.replace(/\D/g,'').slice(0,16).replace(/(.{4})/g,'$1 ').trim(); });
    field('expiry').addEventListener('input', () => { const value = field('expiry').value.replace(/\D/g,'').slice(0,4); field('expiry').value = value.length > 2 ? value.slice(0,2) + ' / ' + value.slice(2) : value; });
    function validate(method) {
        if (method === 'ewallet_demo') {
            if (!walletApproved) {
                walletStatus.textContent = 'Select “Simulate wallet approval” to continue.';
                walletButton.focus();
            }
            return walletApproved;
        }
        if (method !== 'card_demo') return true;
        const checks = [
            ['name', field('name').value.trim() === 'TEST CUSTOMER'],
            ['number', field('number').value.replace(/\s/g,'') === '4242424242424242'],
            ['expiry', field('expiry').value.replace(/\D/g,'') === expiry().replace(/\D/g,'')],
            ['cvc', field('cvc').value === '123']
        ];
        checks.forEach(([name,valid]) => field(name).setAttribute('aria-invalid', String(!valid)));
        const failed = checks.find(([,valid]) => !valid);
        error.hidden = !failed;
        if (failed) { error.textContent = 'Use the supplied test details only. Choose “Use test card” to fill all four fields.'; field(failed[0]).focus(); }
        return !failed;
    }
    window.addEventListener('pagehide', clear);
    return {select,validate,clear,setContext};
})();
