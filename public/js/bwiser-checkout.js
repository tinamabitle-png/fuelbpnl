(function () {
    const script = document.currentScript;
    if (!script) return;

    const dataset = script.dataset || {};
    const publicKey = dataset.bwiserPublicKey || dataset.publicKey || '';
    const station = dataset.bwiserStation || dataset.station || dataset.stationId || '';
    const amount = dataset.bwiserAmount || dataset.amount || '';
    const reference = dataset.bwiserReference || dataset.reference || '';
    const pump = dataset.bwiserPump || dataset.pump || '';
    const label = dataset.bwiserLabel || dataset.label || 'Pay with Bwiser';
    const mountSelector = dataset.bwiserMount || dataset.mount || '';
    const origin = new URL(script.src, window.location.href).origin;

    const buildUrl = () => {
        const url = new URL('/checkout/bwiser/embed', origin);
        url.searchParams.set('public_key', publicKey);
        url.searchParams.set('station_id', station);
        if (amount) url.searchParams.set('amount', amount);
        if (reference) url.searchParams.set('reference', reference);
        if (pump) url.searchParams.set('pump', pump);
        return url.toString();
    };

    const styles = document.createElement('style');
    styles.textContent = `
        .bwiser-checkout-button{display:inline-flex;align-items:center;justify-content:center;min-height:44px;border:0;border-radius:999px;background:#0f172a;color:#fff;padding:0 18px;font:800 14px/1.1 ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;box-shadow:0 20px 35px -25px rgba(15,23,42,.95);cursor:pointer}
        .bwiser-checkout-overlay{position:fixed;inset:0;z-index:2147483000;display:none;align-items:center;justify-content:center;padding:18px;background:rgba(15,23,42,.54);backdrop-filter:blur(8px)}
        .bwiser-checkout-overlay.is-open{display:flex}
        .bwiser-checkout-frame-shell{position:relative;width:min(440px,100%);height:min(660px,calc(100vh - 36px));border-radius:30px;overflow:hidden;background:#fff;box-shadow:0 30px 90px -35px rgba(0,0,0,.8)}
        .bwiser-checkout-frame{width:100%;height:100%;border:0;background:#fff}
        .bwiser-checkout-close{position:absolute;right:12px;top:12px;z-index:2;border:0;border-radius:999px;background:rgba(255,255,255,.92);color:#0f172a;width:36px;height:36px;font:900 18px/1 ui-sans-serif,system-ui;cursor:pointer}
    `;
    document.head.appendChild(styles);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'bwiser-checkout-button';
    button.textContent = label;

    const mount = mountSelector ? document.querySelector(mountSelector) : null;
    if (mount) {
        mount.appendChild(button);
    } else {
        script.insertAdjacentElement('afterend', button);
    }

    const overlay = document.createElement('div');
    overlay.className = 'bwiser-checkout-overlay';
    overlay.innerHTML = `
        <div class="bwiser-checkout-frame-shell" role="dialog" aria-modal="true" aria-label="Bwiser checkout">
            <button type="button" class="bwiser-checkout-close" aria-label="Close Bwiser checkout">×</button>
            <iframe class="bwiser-checkout-frame" title="Bwiser checkout"></iframe>
        </div>
    `;
    document.body.appendChild(overlay);

    const frame = overlay.querySelector('iframe');
    const close = () => overlay.classList.remove('is-open');
    overlay.querySelector('.bwiser-checkout-close').addEventListener('click', close);
    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) close();
    });

    button.addEventListener('click', () => {
        frame.src = buildUrl();
        overlay.classList.add('is-open');
    });
})();
