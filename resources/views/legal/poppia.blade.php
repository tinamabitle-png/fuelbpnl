@extends('Layouts.app')

@section('title', 'POPIA Privacy Notice')

@section('content')
<section class="max-w-4xl mx-auto px-6 py-12">
    <div class="glass rounded-2xl p-8 space-y-6">
        <h1 class="brand-font text-3xl text-slate-900">POPIA Privacy Notice</h1>
        @php
            $effectiveDate = 'March 11, 2026';
            $lastUpdated = 'March 11, 2026';
        @endphp
        <p class="text-sm text-slate-600">
            Effective date: {{ $effectiveDate }}. Last updated: {{ $lastUpdated }}. This notice is provided in alignment with the
            Protection of Personal Information Act, 4 of 2013 (“POPIA”) and describes how Bwiser processes personal information in South
            Africa.
        </p>

        <div class="space-y-4 text-sm text-slate-700">
            <h2 class="text-lg font-semibold text-slate-900">1. Who We Are</h2>
            <p>
                Bwiser provides an operational platform that connects Drivers, Stations/Merchants, and operational and finance teams to
                support approvals, secure voucher issuance, redemption, settlement, and audit visibility. In POPIA terms, Bwiser is
                generally the <span class="font-semibold">Responsible Party</span> for personal information processed to operate the
                Platform. In certain scenarios, where we process personal information strictly on behalf of a Merchant/Station or another
                partner under written instructions, we may act as an <span class="font-semibold">Operator</span>.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">2. What This Notice Covers</h2>
            <p>
                This notice explains what personal information we collect, why we collect it, how we use it, who we share it with, how we
                protect it, and what rights you have as a data subject. This notice should be read with our Privacy Policy, which provides
                additional detail on our processing activities, including cookies and technical logs.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">3. Categories of Personal Information</h2>
            <p>
                The categories of personal information we process depend on whether you are a Driver, Merchant/Station user, or Admin.
                We may process:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li><span class="font-semibold">Identity and contact information</span>: names, email address, phone number, username, and identifiers you provide.</li>
                <li><span class="font-semibold">Address and location information</span>: address text, latitude/longitude, and location-derived information used for operational matching and fraud controls.</li>
                <li><span class="font-semibold">Account and authentication information</span>: password hash, sessions, remember-me identifiers, device identifiers (where implemented), and login metadata.</li>
                <li><span class="font-semibold">Operational information</span>: voucher applications, approvals, voucher codes, redemptions, settlement events, station selections, and audit logs.</li>
                <li><span class="font-semibold">Payment and repayment information</span>: payment references, transaction outcomes, and tokenised payment method metadata (not full card numbers).</li>
                <li><span class="font-semibold">Compliance documents</span>: documents you upload for onboarding or compliance (for example, licences, identity documents, and business documents).</li>
                <li><span class="font-semibold">Communications</span>: support tickets, emails, and messages you send to us.</li>
                <li><span class="font-semibold">Technical information</span>: IP address, browser and device information, performance logs, and security event logs.</li>
            </ul>
            <p>
                We do not intentionally process “special personal information” as defined by POPIA unless required by law or strictly
                necessary for a permitted purpose, and then only with appropriate safeguards.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">4. Purposes for Processing</h2>
            <p>We process personal information for purposes including:</p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Creating and administering accounts for Drivers, Merchants/Stations, and Admins.</li>
                <li>Processing voucher applications, approvals, issuance, redemption verification, and settlement operations.</li>
                <li>Performing risk, fraud, and operational checks to protect the Platform and users.</li>
                <li>Processing repayments and reconciling payment outcomes.</li>
                <li>Providing support, responding to queries, and resolving disputes.</li>
                <li>Meeting legal and regulatory obligations (including recordkeeping and responding to lawful requests).</li>
                <li>Maintaining security, auditing access, investigating incidents, and enforcing policies.</li>
                <li>Improving the Platform, including performance monitoring and error remediation.</li>
            </ul>

            <h2 class="text-lg font-semibold text-slate-900">5. Lawful Grounds / Justification</h2>
            <p>
                POPIA allows processing where a lawful basis exists. Depending on the context, our processing may be justified because:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>It is necessary to perform a contract with you or to take steps at your request prior to entering into a contract.</li>
                <li>It is necessary to comply with legal obligations.</li>
                <li>It protects your legitimate interests or ours (for example, security and fraud prevention), where permitted.</li>
                <li>You have provided consent (where processing is based on consent, you may withdraw it, subject to limitations).</li>
            </ul>
            <p>
                Where consent is required (for example, for certain optional cookies or marketing communications), we provide a choice and
                record your preference. Some processing is necessary to operate the Platform and cannot be opted out of if you wish to use
                core features.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">6. Recipients and Sharing</h2>
            <p>
                We may share personal information with recipients in order to operate the Platform, such as:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Stations/Merchants involved in a voucher application or redemption, limited to what is necessary to complete the transaction.</li>
                <li>Payment and banking partners (for example, payment gateways) to process repayments, authorisations, and transaction outcomes.</li>
                <li>Service providers who host or support the Platform (for example, cloud and infrastructure providers), under confidentiality and security obligations.</li>
                <li>Professional advisors (legal, auditors) where required for compliance and governance, subject to confidentiality.</li>
                <li>Authorities or regulators where we are legally required to do so, or where it is lawful and necessary to protect rights and safety.</li>
            </ul>
            <p>
                We do not sell personal information. We aim to share only what is necessary, and we apply contractual safeguards to Operators.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">7. Cross-Border Transfers</h2>
            <p>
                Some service providers may process information outside South Africa. Where we transfer personal information cross-border,
                we take reasonable steps to ensure that the recipient is subject to laws, binding corporate rules, or agreements that
                provide an adequate level of protection as required by POPIA, and that appropriate security safeguards are in place.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">8. Information Quality</h2>
            <p>
                You are responsible for keeping your information accurate and up to date. If your information is incorrect, this can affect
                voucher approvals, communications, repayment processing, and operational matching (such as station selection).
            </p>

            <h2 class="text-lg font-semibold text-slate-900">9. Security Safeguards</h2>
            <p>
                We implement appropriate technical and organisational measures to protect personal information. Controls may include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Access control, role-based permissions, and least-privilege principles.</li>
                <li>Encryption in transit (TLS) and protective handling of sensitive fields where appropriate.</li>
                <li>Audit logging for important operational events (applications, approvals, redemptions, repayments).</li>
                <li>Monitoring, alerting, and incident response processes.</li>
                <li>Vendor due diligence and contractual protections with Operators.</li>
            </ul>
            <p>
                No system is perfectly secure. You also play a role by protecting your credentials, using strong passwords, and avoiding
                sharing sensitive information through insecure channels.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">10. Retention</h2>
            <p>
                We keep personal information only as long as necessary for the purposes described in this notice, unless a longer retention
                period is required or permitted by law. Retention periods vary by category, for example:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Security and audit logs may be retained to support investigations and compliance.</li>
                <li>Financial and repayment records may be retained to support reconciliation, audits, and disputes.</li>
                <li>KYC documents may be retained in line with compliance and governance requirements.</li>
            </ul>
            <p>
                When retention is no longer required, we take reasonable steps to delete, de-identify, or anonymise personal information,
                subject to technical and lawful constraints (such as backup retention windows).
            </p>

            <h2 class="text-lg font-semibold text-slate-900">11. Automated Decision-Making</h2>
            <p>
                Some Platform decisions may be supported by automated checks (for example, fraud and risk signals, data validation, and
                operational rules). Where such checks are used, we aim to apply appropriate safeguards and allow for escalation or review
                in higher-risk or disputed cases. Final decisions may involve human review depending on the workflow.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">12. Your Rights as a Data Subject</h2>
            <p>
                Subject to POPIA and other applicable laws, you may request:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Access to personal information we hold about you.</li>
                <li>Correction or updating of inaccurate or incomplete information.</li>
                <li>Deletion of information where it is no longer lawfully required to be retained.</li>
                <li>Objection to processing in certain circumstances.</li>
                <li>Withdrawal of consent (where processing is based on consent).</li>
            </ul>
            <p>
                Requests may require identity verification. We may refuse or limit requests where a lawful ground applies (for example,
                where disclosure would prejudice another person’s rights, compromise security, or conflict with legal obligations).
            </p>

            <h2 class="text-lg font-semibold text-slate-900">13. Complaints</h2>
            <p>
                If you have a concern about how we process personal information, please contact us first so we can address it. If the issue
                is not resolved to your satisfaction, you may lodge a complaint with the Information Regulator (South Africa) in accordance
                with POPIA. We can provide relevant details and supporting information upon request.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">14. How to Submit a POPIA Request</h2>
            <p>
                If you want to exercise your rights (access, correction, deletion where lawful, objection, or withdrawal of consent where
                applicable), you can submit a request to our Information Officer contact channel. To help us process requests efficiently,
                include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Your full name and the email/phone number used on the Platform.</li>
                <li>The specific right you want to exercise and what outcome you are requesting.</li>
                <li>Any context (for example, the page, voucher ID, repayment reference, or timeframe) that helps us locate the relevant information.</li>
            </ul>
            <p>
                We aim to respond within a reasonable time. Some requests may take longer if they are complex or involve consultation with
                third parties (for example, where records contain other data subjects’ information).
            </p>

            <h2 class="text-lg font-semibold text-slate-900">15. Identity Verification</h2>
            <p>
                To protect users and prevent unauthorised disclosure, we may verify your identity before responding to a request. This may
                involve confirming account identifiers and, in higher-risk cases, requesting additional verification. We will not ask you to
                share your password. If you receive a suspicious request for credentials, treat it as a potential phishing attempt and
                contact support.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">16. Operators, Confidentiality, and Vendor Controls</h2>
            <p>
                Where Bwiser uses Operators (service providers) to host or support the Platform, we aim to ensure they are bound by
                confidentiality and security obligations. Operators are permitted to process personal information only for the purposes we
                specify and must implement appropriate safeguards. We also aim to limit data shared with Operators to what is necessary.
            </p>
            <p>
                Where Bwiser processes personal information on behalf of a Merchant/Station under their instructions, we implement
                safeguards appropriate to an Operator role, and we apply contractual and operational controls to prevent unauthorised use.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">17. Cookies and Communications</h2>
            <p>
                The Web experience uses cookies for security, authentication, and session management. Optional analytics cookies may be used
                where enabled and where a lawful basis exists. We also send essential operational communications (for example, security
                notices, voucher status updates, and repayment reminders) necessary to provide the Platform. Promotional marketing, where
                used, will include an opt-out option.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">18. Special Personal Information and Children</h2>
            <p>
                POPIA provides additional protections for “special personal information” (such as information about health, biometric
                information, or criminal behaviour) and for the personal information of children. Bwiser does not intentionally request
                special personal information unless it is strictly necessary for a permitted purpose and appropriate safeguards are in
                place. The Platform is generally intended for adult users. If we become aware that we have processed a child’s personal
                information without appropriate authority, we will take steps to delete it or handle it in accordance with applicable law.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">19. De-identification and Analytics</h2>
            <p>
                Where we use analytics for performance monitoring or service improvement, we aim to minimise personal information in those
                datasets. In some cases, information may be aggregated or de-identified so that it is not reasonably re-identifiable. Where
                de-identification is used, it is applied to reduce privacy risk while still allowing operational learning (for example,
                identifying slow pages, frequent errors, or drop-offs in onboarding).
            </p>
            <p>
                De-identified information may still be subject to security safeguards and governance controls. Where information remains
                personal information under POPIA, it is treated accordingly.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">20. Contact</h2>
            <p>
                For privacy requests and POPIA-related enquiries, contact:
                <span class="font-semibold">support@bwiser.co.za</span>.
            </p>
            <p>
                Where a request overlaps with PAIA (for example, a request for access to records held by a private body), we may guide you to
                the PAIA request process described in our PAIA Manual. We may also refuse or limit a request where a lawful ground applies,
                including where disclosure would reveal another person’s personal information, confidential commercial information, or
                security-sensitive internal controls.
            </p>
            <p>
                If you are requesting correction of information, please include the corrected details and any supporting proof where
                appropriate (for example, updated contact details or corrected address information). Correct information helps us reduce
                operational risk and ensures voucher and repayment workflows function correctly.
            </p>
            <p class="text-xs text-slate-500">
                Note: This POPIA Notice is provided for transparency and operational readiness and does not constitute legal advice.
            </p>
        </div>
    </div>
</section>
@endsection
