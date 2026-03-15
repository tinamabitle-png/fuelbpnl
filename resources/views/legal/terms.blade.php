@extends('Layouts.app')

@section('title', 'Terms & Conditions')

@section('content')
<section class="max-w-4xl mx-auto px-6 py-12">
    <div class="glass rounded-2xl p-8 space-y-6">
        <h1 class="brand-font text-3xl text-slate-900">Terms & Conditions</h1>
        @php
            $effectiveDate = 'March 11, 2026';
            $lastUpdated = 'March 11, 2026';
        @endphp
        <p class="text-sm text-slate-600">
            Effective date: {{ $effectiveDate }}. Last updated: {{ $lastUpdated }}.
        </p>

        <div class="space-y-4 text-sm text-slate-700">
            <h2 class="text-lg font-semibold text-slate-900">1. Introduction and Acceptance</h2>
            <p>
                These Terms and Conditions (“<span class="font-semibold">Terms</span>”) govern your access to and use of the Bwiser platform,
                including our website, web application, mobile application, APIs (where made available), and related services
                (collectively, the “<span class="font-semibold">Platform</span>”). By registering for an account, signing in, applying for a
                voucher, redeeming a voucher, or otherwise using the Platform, you agree to be bound by these Terms and the policies linked
                from the Platform, including our Privacy Policy, POPIA Notice, Cookie Policy, AML & KYC Policy, PAIA Manual, and Security
                & Compliance information (collectively, the “<span class="font-semibold">Policies</span>”).
            </p>
            <p>
                If you do not agree to these Terms, do not use the Platform. If you are using the Platform on behalf of a company or other
                entity, you represent and warrant that you have authority to bind that entity to these Terms.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">2. Definitions</h2>
            <p>In these Terms, unless the context indicates otherwise:</p>
            <ul class="list-disc pl-5 space-y-1">
                <li><span class="font-semibold">“Admin”</span> means authorised Bwiser staff (or authorised partners) operating administrative functions.</li>
                <li><span class="font-semibold">“Driver”</span> means a user applying for or using voucher-based financing for approved purchases.</li>
                <li><span class="font-semibold">“Merchant”</span> means a registered business user (including station operators and authorised staff) who redeems vouchers and performs operational functions.</li>
                <li><span class="font-semibold">“Station”</span> means a physical or virtual redemption location represented in the Platform, typically linked to a Merchant account.</li>
                <li><span class="font-semibold">“Voucher”</span> means a secure, uniquely identified authorisation issued by the Platform for an approved purchase amount, subject to conditions.</li>
                <li><span class="font-semibold">“Repayment”</span> means any scheduled or actual payment made by a Driver (or authorised payer) toward amounts due under an arrangement.</li>
                <li><span class="font-semibold">“Late Fee”</span> means an administrative fee applied when repayments are overdue, if applicable and lawful.</li>
                <li><span class="font-semibold">“Autopay”</span> means an authorised recurring card debit or tokenised payment method used to collect repayments automatically, where enabled.</li>
                <li><span class="font-semibold">“You/your”</span> means any person or entity using the Platform, including Drivers, Merchants, and Admins where applicable.</li>
            </ul>

            <h2 class="text-lg font-semibold text-slate-900">3. Eligibility and Account Registration</h2>
            <p>
                The Platform is intended for adult users (generally 18 years or older) with legal capacity. By registering, you confirm
                that you meet eligibility requirements and that the information you provide is complete and accurate. We may require
                verification of identity, contact information, business details, and supporting documentation, and we may refuse, suspend,
                or terminate accounts that do not meet our onboarding, compliance, or risk requirements.
            </p>
            <p>
                You are responsible for maintaining the confidentiality of your login credentials and for all activity on your account.
                Notify us promptly if you suspect unauthorised use. We may implement security controls including device recognition,
                session management, and multi-factor verification depending on risk signals and service availability.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">4. Platform Purpose and Service Description</h2>
            <p>
                Bwiser provides an operational platform connecting Drivers, Stations/Merchants, and finance/operations teams to enable a
                controlled process for approvals, secure voucher issuance, redemption, settlement, and audit visibility. The Platform may
                include features such as:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Driver onboarding and application submission (including optional document uploads).</li>
                <li>Risk, fraud, and operational checks to support approvals and limits.</li>
                <li>Voucher issuance, secure delivery, redemption verification, and audit logs.</li>
                <li>Repayment scheduling, payment collection, and settlement reporting.</li>
                <li>Notifications and operational support tools.</li>
            </ul>
            <p>
                The Platform does not guarantee approval, a specific credit limit, or continuous availability of any feature. Features may
                vary by role, geography, station availability, payment gateway availability, and other operational constraints.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">5. Onboarding, Verification, and Document Uploads</h2>
            <p>
                To protect the Platform and comply with South African legal and contractual obligations, we may require Drivers and
                Merchants to provide documentation such as identity documents, licences, proof of address, business registration documents,
                and other compliance records. Document requirements may change based on risk level, user type, and service scope.
            </p>
            <p>
                You warrant that documents submitted are authentic, current, and belong to you (or your business). Submitting false or
                misleading documentation, or attempting to impersonate another person or business, is prohibited and may result in account
                suspension, reversal of transactions (where possible), and reporting to relevant authorities.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">6. Approvals, Limits, and National Credit Act Alignment</h2>
            <p>
                Where the Platform supports credit-related approvals or repayment arrangements, we aim to operate in a manner consistent
                with South African legal requirements, including transparency, affordability principles, and fair treatment. However, the
                Platform may support multiple product structures and partners. The exact legal classification of any arrangement may depend
                on the specific product configuration, contractual relationships, and regulatory interpretation. Where required, additional
                contractual documents or disclosures may apply.
            </p>
            <p>
                Any limits displayed in the Platform are subject to change based on updated information, repayment performance, risk
                signals, station availability, and operational factors. We may reduce, suspend, or remove limits at any time where
                reasonably required to manage risk or comply with law.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">7. Voucher Issuance Rules (Drivers)</h2>
            <p>
                Voucher issuance is subject to approval and may be constrained by eligibility, account standing, compliance status, and
                risk checks. When a voucher is issued, it will be associated with a value, validity period, redemption conditions (for
                example, fuel type or product constraints), and a station or station network where redemption is permitted.
            </p>
            <p>
                Drivers must ensure that voucher requests are accurate and must not attempt to obtain vouchers for unauthorised purposes.
                We may apply controls to prevent misuse, including velocity limits, location-based validation, and device verification.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">8. Voucher Redemption Rules (Stations/Merchants)</h2>
            <p>
                Stations/Merchants may only redeem vouchers in accordance with the Platform’s redemption workflow and the voucher’s
                conditions. Redemption typically requires verification steps (for example, scanning or entering the voucher code) and
                confirmation of the authorised goods/services.
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Stations must not redeem vouchers without delivering the authorised goods/services.</li>
                <li>Stations must not split, duplicate, or re-use vouchers outside the Platform’s redemption rules.</li>
                <li>Any suspected fraud or mismatch must be reported through the Platform support channel.</li>
            </ul>
            <p>
                We may reverse, suspend, or flag redemptions where there is evidence of misuse, error, or policy violations, subject to
                applicable law and contractual constraints.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">9. Repayments, Late Fees, and Collections</h2>
            <p>
                Repayment schedules, amounts due, and any applicable fees are presented within the Platform. You are responsible for
                reviewing repayment information and ensuring timely payments. Where lawful and applicable, overdue repayments may incur a
                Late Fee at the rate disclosed in the Platform and/or in related disclosures. Late Fees are intended to reflect
                administrative and operational costs and are not presented as interest.
            </p>
            <p>
                If repayments are missed or repeatedly fail, we may take reasonable steps to recover amounts due, including sending
                reminders, restricting access to additional vouchers, requiring alternative payment methods, or escalating for manual
                review. Any collections practices will be conducted in a manner intended to be fair and proportionate, consistent with
                applicable South African law.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">10. Autopay (Card Authorisation and Tokenised Payments)</h2>
            <p>
                Where Autopay is available, Drivers may authorise a card payment method through an integrated payment gateway. The Platform
                may store tokenised references (not full card numbers) to facilitate future payments. You must ensure you have authority to
                use the payment method and that sufficient funds are available on scheduled debit dates.
            </p>
            <p>
                If Autopay fails, we may request re-authorisation, require a different payment method, or pause voucher issuance until the
                account is brought back into good standing. You may be able to enable or disable Autopay in the Platform, subject to
                restrictions where repayments are overdue or where disabling would be inconsistent with contractual requirements.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">11. Fees, Pricing, and Settlement Timelines</h2>
            <p>
                Any fees, charges, or settlement timing applicable to Merchants/Stations will be displayed in the Platform or agreed in
                written commercial terms. Settlement timing may depend on redemption verification, reconciliation, banking timelines, and
                third-party payment or banking partner performance. We do not guarantee that settlement will occur at a specific time.
            </p>
            <p>
                Where the Platform displays balances, settlement status, or reports, these are provided for operational visibility and may
                be subject to reconciliation adjustments. If you believe there is an error, you must raise it promptly with sufficient
                supporting information.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">12. Prohibited Use and Conduct</h2>
            <p>You may not use the Platform to:</p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Commit fraud, impersonation, identity theft, or misrepresentation.</li>
                <li>Submit forged documents or attempt to bypass verification requirements.</li>
                <li>Redeem vouchers without supplying authorised goods/services.</li>
                <li>Reverse engineer, interfere with, or disrupt the Platform’s security or availability.</li>
                <li>Upload malware or attempt unauthorised access to accounts, systems, or data.</li>
                <li>Use the Platform for unlawful purposes or in violation of sanctions or other restrictions.</li>
            </ul>

            <h2 class="text-lg font-semibold text-slate-900">13. Data Protection, POPIA, and PAIA</h2>
            <p>
                Personal information is processed in accordance with POPIA and our Privacy Policy and POPIA Notice. Requests for records
                under PAIA are handled in accordance with our PAIA Manual. Where we process information on behalf of a Merchant/Station or
                other partner under written instructions, we may act as an Operator and will implement appropriate safeguards and
                confidentiality controls.
            </p>
            <p>
                By using the Platform, you acknowledge that operational events (applications, approvals, redemptions, repayments) are
                logged and retained for auditing, compliance, dispute resolution, and security. You agree not to submit personal
                information about others unless you have authority to do so and it is required for a legitimate purpose.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">14. Communications and Notices</h2>
            <p>
                We may send you operational communications via email, SMS, push notification, or in-app messaging (where enabled), such as
                login alerts, voucher status updates, repayment reminders, and security notifications. You are responsible for keeping your
                contact information current. Some communications are essential for platform operation and may not be optional.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">15. Intellectual Property</h2>
            <p>
                The Platform, including its software, branding, user interface, and content (excluding user-provided content), is owned by
                or licensed to Bwiser and is protected by intellectual property laws. You receive a limited, non-exclusive, non-transferable
                right to use the Platform for its intended purpose during your authorised access. You may not copy, modify, distribute, or
                create derivative works except as permitted by law or by written agreement.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">16. Availability, Maintenance, and Changes</h2>
            <p>
                We aim to keep the Platform available and secure, but downtime may occur due to maintenance, upgrades, security events, or
                third-party failures (including hosting providers, payment gateways, and mapping services). We may change, suspend, or
                discontinue parts of the Platform at any time, including introducing new features, modifying workflows, or limiting access
                to manage risk and compliance.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">17. Disclaimers</h2>
            <p>
                The Platform is provided on an “as is” and “as available” basis. To the maximum extent permitted by law, we disclaim
                warranties of merchantability, fitness for a particular purpose, and non-infringement. We do not warrant that the Platform
                will be uninterrupted, error-free, or that any information displayed will always be complete or current.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">18. Limitation of Liability</h2>
            <p>
                To the maximum extent permitted by South African law, Bwiser will not be liable for indirect, incidental, special,
                consequential, or punitive damages, or for loss of profits, revenue, data, goodwill, or business opportunity, arising from
                your use of (or inability to use) the Platform. Where liability cannot be excluded by statute, liability will be limited to
                the minimum extent permitted and, where appropriate, limited to amounts paid for the relevant services in a reasonable
                period preceding the claim.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">19. Indemnity</h2>
            <p>
                You agree to indemnify and hold harmless Bwiser, its directors, officers, employees, and contractors against claims,
                damages, losses, and expenses (including reasonable legal fees) arising from your misuse of the Platform, breach of these
                Terms, or violation of law, including fraud or unauthorised redemption.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">20. Suspension, Termination, and Cancellation</h2>
            <p>
                We may suspend or terminate your access to the Platform if we reasonably believe you have breached these Terms, provided
                inaccurate information, failed to meet compliance requirements, or engaged in suspicious activity. You may request account
                closure subject to outstanding obligations (including amounts due). Where a voucher application is cancelled and permitted
                by the workflow, future scheduled repayments linked to that cancelled application may be adjusted or removed, subject to
                audit and dispute controls.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">21. Dispute Resolution</h2>
            <p>
                If a dispute arises, you agree to first contact support@bwiser.co.za with the relevant details and allow a reasonable
                opportunity to investigate and resolve. Disputes involving vouchers or repayments may require verification of audit logs,
                station redemption records, and payment gateway records. Nothing in this clause limits any rights you may have under
                applicable law.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">22. Governing Law and Jurisdiction</h2>
            <p>
                These Terms are governed by the laws of the Republic of South Africa. You agree to the jurisdiction of competent South
                African courts for disputes arising from these Terms or your use of the Platform, subject to mandatory legal rights.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">23. Changes to These Terms</h2>
            <p>
                We may update these Terms from time to time. Material changes may be communicated through the Platform. Continued use of
                the Platform after changes take effect constitutes acceptance of updated Terms.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">24. Contact</h2>
            <p>
                Questions about these Terms can be directed to <span class="font-semibold">support@bwiser.co.za</span>.
            </p>

            <p class="text-xs text-slate-500">
                Note: These Terms are provided for transparency and operational readiness and do not constitute legal advice. If you
                require legal advice, consult a qualified professional.
            </p>
        </div>
    </div>
</section>
@endsection
