@php
    $prefix = $prefix ?? 'driver';
    $formId = $formId ?? 'driverRegisterForm';
    $modalId = $prefix . 'AgreementModal';
    $termsHiddenId = $prefix . 'DriverTermsAccepted';
    $creditHiddenId = $prefix . 'DriverCreditConsent';
    $versionHiddenId = $prefix . 'DriverAgreementVersion';
    $termsCheckboxId = $prefix . 'AgreementTermsCheckbox';
    $creditCheckboxId = $prefix . 'AgreementCreditCheckbox';
    $acceptBtnId = $prefix . 'AgreementAcceptBtn';
    $agreementVersion = $agreementVersion ?? 'driver-platform-v1-2026-04-13';
    $hasAgreementErrors = $errors->has('driver_terms_accepted') || $errors->has('driver_credit_consent');
@endphp

<div
    id="{{ $modalId }}"
    class="fixed inset-0 z-[120] hidden items-end justify-center bg-slate-950/70 p-4 md:items-center"
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $prefix }}AgreementTitle"
>
    <div class="w-full max-w-3xl overflow-hidden rounded-[28px] bg-white shadow-[0_40px_90px_-35px_rgba(15,23,42,0.55)]">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 md:px-6">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[#020DFF]">Driver agreement</p>
                <h3 id="{{ $prefix }}AgreementTitle" class="mt-1 text-lg font-semibold text-slate-900">
                    Bwiser Driver Account Contract & Consent
                </h3>
                <p class="mt-1 text-xs leading-5 text-slate-600">
                    Review and accept these terms before we create your driver account.
                </p>
            </div>
            <button
                type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                data-driver-agreement-close="{{ $modalId }}"
                aria-label="Close agreement"
            >
                ✕
            </button>
        </div>

        <div class="max-h-[52vh] space-y-5 overflow-y-auto px-5 py-5 text-sm leading-6 text-slate-700 md:px-6">
            <div class="rounded-2xl bg-blue-50/80 p-4 text-[13px] leading-6 text-slate-700">
                This contract is designed to align with Bwiser’s South African onboarding, credit-risk, and data-protection obligations,
                including the <span class="font-semibold">National Credit Act / NCR affordability principles where applicable</span> and the
                <span class="font-semibold">Protection of Personal Information Act (POPIA)</span>.
            </div>

            <div class="space-y-2">
                <h4 class="text-sm font-semibold text-slate-900">1. Account creation and platform use</h4>
                <p>
                    By creating a driver account, you confirm that the information you submit is true, current, and belongs to you. You
                    agree to use the Bwiser platform only for lawful voucher, repayment, and onboarding activities and to keep your login
                    credentials secure.
                </p>
            </div>

            <div class="space-y-2">
                <h4 class="text-sm font-semibold text-slate-900">2. Voucher and finance-related decisions</h4>
                <p>
                    Account registration does <span class="font-semibold">not by itself guarantee approval</span> for vouchers, payment terms,
                    or any credit-based product. Where you later apply for a product that falls within South African credit regulation,
                    Bwiser may perform affordability, fraud, identity, and risk checks and may present additional disclosures or terms before
                    any regulated transaction is concluded.
                </p>
            </div>

            <div class="space-y-2">
                <h4 class="text-sm font-semibold text-slate-900">3. POPIA and personal information processing</h4>
                <p>
                    You authorise Bwiser to collect and process the personal information needed to onboard and manage your account, including
                    identity details, contact details, ID number, address, location data, uploaded documents, operational activity, payment
                    metadata, and support records. This processing is used to provide the platform, verify your identity, detect fraud,
                    manage repayments, keep audit trails, and comply with South African law.
                </p>
                <p>
                    Where lawful and necessary, Bwiser may share relevant information with stations, payment providers, mapping or messaging
                    operators, compliance service providers, and regulators or authorities. Processing remains subject to POPIA-aligned
                    safeguards, purpose limitation, and security controls.
                </p>
            </div>

            <div class="space-y-2">
                <h4 class="text-sm font-semibold text-slate-900">4. Consent to verification, affordability, and compliance checks</h4>
                <p>
                    You consent to identity verification, document review, fraud screening, and—where applicable—affordability or other
                    responsible-lending checks needed to assess your eligibility for voucher and finance workflows. These checks may use the
                    information you provide directly, along with operational history and supporting records submitted through the platform.
                </p>
            </div>

            <div class="space-y-2">
                <h4 class="text-sm font-semibold text-slate-900">5. Communications, records, and audit trail</h4>
                <p>
                    You agree that Bwiser may send operational notices by email, SMS, WhatsApp, push notification, or in-app messaging,
                    including verification notices, approval outcomes, repayment reminders, and security alerts. You also acknowledge that
                    important actions on the platform are logged for audit, compliance, dispute resolution, and security purposes.
                </p>
            </div>

            <div class="space-y-2">
                <h4 class="text-sm font-semibold text-slate-900">6. Driver responsibilities</h4>
                <p>
                    You agree to keep your details accurate, update them when they change, upload only authentic documents, and avoid any
                    misuse of vouchers, merchant systems, or repayment tools. False information, impersonation, or fraudulent activity may
                    lead to rejection, suspension, recovery action, or reporting to the appropriate authorities.
                </p>
            </div>

            <div class="space-y-2">
                <h4 class="text-sm font-semibold text-slate-900">7. Legal documents incorporated by reference</h4>
                <p>
                    This acceptance works together with Bwiser’s platform legal documents. Please review them as part of this agreement:
                </p>
                <div class="flex flex-wrap gap-2 text-xs">
                    <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="rounded-full border border-slate-200 px-3 py-1 font-medium text-slate-700 hover:border-blue-200 hover:text-blue-700">Terms & Conditions</a>
                    <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener" class="rounded-full border border-slate-200 px-3 py-1 font-medium text-slate-700 hover:border-blue-200 hover:text-blue-700">Privacy Policy</a>
                    <a href="{{ route('legal.poppia') }}" target="_blank" rel="noopener" class="rounded-full border border-slate-200 px-3 py-1 font-medium text-slate-700 hover:border-blue-200 hover:text-blue-700">POPIA Notice</a>
                    <a href="{{ route('legal.paia') }}" target="_blank" rel="noopener" class="rounded-full border border-slate-200 px-3 py-1 font-medium text-slate-700 hover:border-blue-200 hover:text-blue-700">PAIA Manual</a>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200 bg-slate-50 px-5 py-4 md:px-6">
            <div class="space-y-3">
                <label class="flex items-start gap-3 text-sm text-slate-700">
                    <input id="{{ $termsCheckboxId }}" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>I accept the Bwiser Driver Account Contract and the linked legal documents.</span>
                </label>
                <label class="flex items-start gap-3 text-sm text-slate-700">
                    <input id="{{ $creditCheckboxId }}" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>I consent to POPIA-governed processing, verification, fraud screening, and where applicable affordability / credit-related checks for onboarding and product decisions.</span>
                </label>

                @if($errors->has('driver_terms_accepted') || $errors->has('driver_credit_consent'))
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                        {{ $errors->first('driver_terms_accepted') ?: $errors->first('driver_credit_consent') }}
                    </div>
                @endif

                <div class="flex flex-col-reverse gap-2 pt-1 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        data-driver-agreement-close="{{ $modalId }}"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        id="{{ $acceptBtnId }}"
                        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                        Accept and create account
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function initDriverAgreementModal() {
        const form = document.getElementById(@json($formId));
        const modal = document.getElementById(@json($modalId));
        const acceptButton = document.getElementById(@json($acceptBtnId));
        const termsHidden = document.getElementById(@json($termsHiddenId));
        const creditHidden = document.getElementById(@json($creditHiddenId));
        const versionHidden = document.getElementById(@json($versionHiddenId));
        const termsCheckbox = document.getElementById(@json($termsCheckboxId));
        const creditCheckbox = document.getElementById(@json($creditCheckboxId));
        const openers = document.querySelectorAll('[data-driver-agreement-open="{{ $formId }}"]');
        const closers = modal ? modal.querySelectorAll('[data-driver-agreement-close="{{ $modalId }}"]') : [];

        if (!form || !modal || !acceptButton || !termsHidden || !creditHidden || !versionHidden || !termsCheckbox || !creditCheckbox) {
            return;
        }

        const syncFromHidden = () => {
            const accepted = termsHidden.value === '1';
            const creditAccepted = creditHidden.value === '1';
            termsCheckbox.checked = accepted;
            creditCheckbox.checked = creditAccepted;
            form.dataset.agreementAccepted = accepted && creditAccepted ? 'true' : 'false';
        };

        const openModal = () => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        };

        syncFromHidden();

        openers.forEach((opener) => {
            opener.addEventListener('click', function () {
                if (!form.reportValidity()) return;
                openModal();
            });
        });

        closers.forEach((closer) => {
            closer.addEventListener('click', closeModal);
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) closeModal();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
                closeModal();
            }
        });

        form.addEventListener('submit', function (event) {
            if (form.dataset.agreementAccepted === 'true') return;
            event.preventDefault();
            if (!form.reportValidity()) return;
            openModal();
        });

        acceptButton.addEventListener('click', function () {
            if (!termsCheckbox.checked) {
                termsCheckbox.focus();
                return;
            }

            if (!creditCheckbox.checked) {
                creditCheckbox.focus();
                return;
            }

            termsHidden.value = '1';
            creditHidden.value = '1';
            versionHidden.value = @json($agreementVersion);
            form.dataset.agreementAccepted = 'true';
            closeModal();
            form.requestSubmit();
        });

        if (@json($hasAgreementErrors)) {
            openModal();
        }
    })();
</script>
