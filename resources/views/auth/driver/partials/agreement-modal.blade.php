@php
    $prefix = $prefix ?? 'driver';
    $formId = $formId ?? 'driverRegisterForm';
    $templateId = $prefix . 'AgreementTemplate';
    $termsHiddenId = $prefix . 'DriverTermsAccepted';
    $creditHiddenId = $prefix . 'DriverCreditConsent';
    $versionHiddenId = $prefix . 'DriverAgreementVersion';
    $agreementVersion = $agreementVersion ?? 'driver-platform-v1-2026-04-13';
    $hasAgreementErrors = $errors->has('driver_terms_accepted') || $errors->has('driver_credit_consent');
@endphp

@push('head')
    <link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
    <style>
        .driver-agreement-popup {
            border-radius: 28px !important;
            padding: 0 !important;
            overflow: hidden !important;
        }

        .driver-agreement-html {
            margin: 0 !important;
            text-align: left !important;
        }

        .driver-agreement-actions {
            margin: 0 !important;
            padding: 0 !important;
        }
    </style>
@endpush

<template id="{{ $templateId }}">
    <div class="bg-white text-left">
        <div class="border-b border-slate-200 px-5 py-4 md:px-6">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[#020DFF]">Driver agreement</p>
            <h3 class="mt-1 text-lg font-semibold text-slate-900">
                Bwiser Driver Account Contract & Consent
            </h3>
            <p class="mt-1 text-xs leading-5 text-slate-600">
                Review and accept these terms before we create your driver account.
            </p>
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
                    <input data-driver-agreement-terms type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>I accept the Bwiser Driver Account Contract and the linked legal documents.</span>
                </label>
                <label class="flex items-start gap-3 text-sm text-slate-700">
                    <input data-driver-agreement-credit type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>I consent to POPIA-governed processing, verification, fraud screening, and where applicable affordability / credit-related checks for onboarding and product decisions.</span>
                </label>

                @if($hasAgreementErrors)
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                        {{ $errors->first('driver_terms_accepted') ?: $errors->first('driver_credit_consent') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</template>

@push('scripts')
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        (function initDriverAgreementModal() {
            const form = document.getElementById(@json($formId));
            const template = document.getElementById(@json($templateId));
            const termsHidden = document.getElementById(@json($termsHiddenId));
            const creditHidden = document.getElementById(@json($creditHiddenId));
            const versionHidden = document.getElementById(@json($versionHiddenId));
            const openers = document.querySelectorAll('[data-driver-agreement-open="{{ $formId }}"]');

            if (!form || !template || !termsHidden || !creditHidden || !versionHidden || typeof Swal === 'undefined') {
                return;
            }

            const markAccepted = () => {
                termsHidden.value = '1';
                creditHidden.value = '1';
                versionHidden.value = @json($agreementVersion);
                form.dataset.agreementAccepted = 'true';
            };

            const isAccepted = () => form.dataset.agreementAccepted === 'true';

            const openModal = async () => {
                const result = await Swal.fire({
                    html: template.innerHTML,
                    width: window.innerWidth >= 768 ? '56rem' : '94vw',
                    padding: '0',
                    background: '#ffffff',
                    backdrop: 'rgba(15, 23, 42, 0.62)',
                    showCancelButton: true,
                    confirmButtonText: 'Accept and create account',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    focusConfirm: false,
                    allowOutsideClick: true,
                    customClass: {
                        popup: 'driver-agreement-popup',
                        htmlContainer: 'driver-agreement-html',
                        actions: 'driver-agreement-actions',
                        confirmButton: '!rounded-xl !bg-blue-600 !px-4 !py-2.5 !text-sm !font-semibold',
                        cancelButton: '!rounded-xl !border !border-slate-200 !bg-white !px-4 !py-2.5 !text-sm !font-semibold !text-slate-700'
                    },
                    didOpen: () => {
                        const container = Swal.getContainer();
                        if (container) {
                            container.style.zIndex = '2147483647';
                            container.style.backdropFilter = 'blur(14px)';
                            container.style.webkitBackdropFilter = 'blur(14px)';
                        }
                    },
                    preConfirm: () => {
                        const popup = Swal.getPopup();
                        const termsCheckbox = popup?.querySelector('[data-driver-agreement-terms]');
                        const creditCheckbox = popup?.querySelector('[data-driver-agreement-credit]');

                        if (!termsCheckbox?.checked) {
                            Swal.showValidationMessage('Please accept the driver contract before continuing.');
                            return false;
                        }

                        if (!creditCheckbox?.checked) {
                            Swal.showValidationMessage('Please consent to the POPIA and verification checks before continuing.');
                            return false;
                        }

                        return true;
                    }
                });

                if (result.isConfirmed) {
                    markAccepted();
                    form.requestSubmit();
                }
            };

            openers.forEach((opener) => {
                opener.addEventListener('click', function () {
                    if (!form.reportValidity()) return;
                    openModal();
                });
            });

            form.addEventListener('submit', function (event) {
                if (isAccepted()) return;
                event.preventDefault();
                if (!form.reportValidity()) return;
                openModal();
            });

            if (@json($hasAgreementErrors)) {
                openModal();
            }
        })();
    </script>
@endpush
