<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bwiser Checkout</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --blue: #020dff;
            --soft: #f8fafc;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 15% 10%, rgba(2, 13, 255, 0.12), transparent 32%),
                linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        }

        .shell {
            padding: 18px;
        }

        .card {
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 24px 60px -42px rgba(15, 23, 42, 0.75);
        }

        .head {
            padding: 20px;
            background: linear-gradient(135deg, #020dff, #37d5ff);
            color: white;
        }

        .eyebrow {
            margin: 0;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            opacity: 0.82;
        }

        h1 {
            margin: 8px 0 0;
            font-size: 24px;
            line-height: 1.05;
        }

        .body {
            padding: 20px;
        }

        label {
            display: block;
            margin: 0 0 6px;
            font-size: 12px;
            font-weight: 800;
            color: #334155;
        }

        input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 12px 14px;
            color: var(--ink);
            background: var(--soft);
            font-size: 14px;
            outline: none;
        }

        input:focus {
            border-color: rgba(2, 13, 255, 0.45);
            box-shadow: 0 0 0 4px rgba(2, 13, 255, 0.08);
        }

        .grid {
            display: grid;
            gap: 12px;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        button {
            width: 100%;
            border: 0;
            border-radius: 999px;
            padding: 13px 18px;
            color: white;
            background: #0f172a;
            font-size: 14px;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 20px 32px -24px rgba(15, 23, 42, 0.9);
        }

        .status {
            display: none;
            margin-top: 14px;
            border-radius: 18px;
            padding: 13px;
            font-size: 13px;
            line-height: 1.45;
            background: #eff6ff;
            color: #1e3a8a;
        }

        .status.is-error {
            background: #fff1f2;
            color: #9f1239;
        }

        .status.is-ok {
            background: #ecfdf5;
            color: #065f46;
        }

        .fine {
            margin: 12px 0 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.45;
        }

        @media (max-width: 520px) {
            .row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <section class="card">
            <div class="head">
                <p class="eyebrow">Bwiser Checkout</p>
                <h1>Tapless payment session</h1>
            </div>
            <div class="body">
                <form id="bwiserCheckoutForm" class="grid">
                    <input type="hidden" name="public_key" value="{{ $publicKey }}">
                    <input type="hidden" name="station_id" value="{{ $stationId }}">

                    <div class="row">
                        <div>
                            <label for="amount">Amount</label>
                            <input id="amount" name="amount" inputmode="decimal" value="{{ $amount }}" placeholder="250.00">
                        </div>
                        <div>
                            <label for="pump_number">Pump</label>
                            <input id="pump_number" name="pump_number" value="{{ $pump }}" placeholder="Pump 3">
                        </div>
                    </div>

                    <div>
                        <label for="external_reference">POS reference</label>
                        <input id="external_reference" name="external_reference" value="{{ $reference }}" placeholder="ORDER-1001">
                    </div>

                    <div>
                        <label for="scan_input">Voucher, tap token or customer code</label>
                        <input id="scan_input" name="scan_input" placeholder="Customer voucher code">
                    </div>

                    <button type="submit">Create checkout</button>
                </form>

                <div id="bwiserCheckoutStatus" class="status" role="status" aria-live="polite"></div>
                <p class="fine">This creates a Bwiser payment intent for the station. Final authorization and redemption stay controlled by the station POS or merchant dashboard.</p>
            </div>
        </section>
    </main>

    <script>
        (function () {
            const form = document.getElementById('bwiserCheckoutForm');
            const statusBox = document.getElementById('bwiserCheckoutStatus');
            const show = (message, type = '') => {
                statusBox.className = `status ${type}`;
                statusBox.style.display = 'block';
                statusBox.textContent = message;
            };

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const data = Object.fromEntries(new FormData(form).entries());
                data.metadata = { channel: 'embedded_checkout' };

                show('Creating checkout...');

                try {
                    const response = await fetch('/api/v1/checkout/intents', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data),
                    });
                    const payload = await response.json();
                    if (!response.ok || !payload.success) {
                        throw new Error(payload.message || 'Checkout could not be created.');
                    }

                    const intent = payload.data || {};
                    show(`Checkout created. Reference ${intent.external_reference}. Status: ${intent.status}.`, 'is-ok');

                    window.parent && window.parent.postMessage({
                        type: 'bwiser:checkout-created',
                        intent,
                    }, '*');
                } catch (error) {
                    show(error.message || 'Checkout could not be created.', 'is-error');
                }
            });
        })();
    </script>
</body>
</html>
