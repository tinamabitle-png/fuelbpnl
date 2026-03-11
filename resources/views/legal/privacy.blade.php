@extends('Layouts.app')

@section('title', 'Privacy Policy')

@section('content')
<section class="max-w-4xl mx-auto px-6 py-12">
    <div class="glass rounded-2xl p-8 space-y-6">
        <div class="space-y-2">
            <h1 class="brand-font text-3xl text-slate-900">Privacy Policy</h1>
            @php
                $effectiveDate = 'March 11, 2026';
                $lastUpdated = 'March 11, 2026';
            @endphp
            <p class="text-sm text-slate-600">
                Effective date: {{ $effectiveDate }}. Last updated: {{ $lastUpdated }}. This policy describes how Bwiser processes personal information in South Africa in
                accordance with the Protection of Personal Information Act, 4 of 2013 (POPIA) and, where relevant, the Promotion of Access to
                Information Act, 2 of 2000 (PAIA).
            </p>
        </div>

        <div class="space-y-4 text-sm text-slate-700">
            <h2 class="text-lg font-semibold text-slate-900">1. Who We Are</h2>
            <p>
                Bwiser provides a platform that connects drivers, merchants/stations, and operations and finance teams to enable financing,
                secure voucher issuance, redemption, settlement, and audit visibility. In POPIA terms, Bwiser is generally the
                <span class="font-semibold">Responsible Party</span> for personal information processed to operate the platform. In some cases,
                where we process information on behalf of a merchant, station, or partner under written instructions, we may act as an
                <span class="font-semibold">Operator</span>.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">2. Scope</h2>
            <p>
                This Privacy Policy applies to personal information collected or generated through our websites, web application, mobile
                application, customer support channels, integrations, and operational processes. It covers drivers, merchants/stations,
                administrators, employees/contractors, and applicants interacting with Bwiser.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">3. Personal Information We Collect</h2>
            <p>
                We collect personal information directly from you, from your device, from your interactions with the platform, and (where
                lawful) from third parties and public sources. Depending on your role and use of the platform, we may process:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li><span class="font-semibold">Identity and contact details</span> such as names, email addresses, phone numbers, and usernames.</li>
                <li><span class="font-semibold">Address and location data</span> such as home/business address, latitude/longitude, and location-derived suggestions.</li>
                <li><span class="font-semibold">Account and authentication data</span> such as password hashes, session identifiers, device identifiers, and login metadata.</li>
                <li><span class="font-semibold">Operational data</span> such as voucher applications, approvals, redemptions, settlements, and audit trails.</li>
                <li><span class="font-semibold">Payment and repayment metadata</span> such as payment references, transaction outcomes, and tokenised payment method metadata.</li>
                <li><span class="font-semibold">Compliance and onboarding documents</span> you upload (e.g., licences, business documents) and related verification results.</li>
                <li><span class="font-semibold">Communications</span> such as support messages, emails, call logs, and feedback you submit.</li>
                <li><span class="font-semibold">Technical and usage data</span> such as browser type, IP address, device details, pages/actions, and error logs.</li>
            </ul>
            <p>
                We do not intentionally collect special personal information (as defined by POPIA) unless it is required by law or strictly
                necessary for a permitted purpose, and then only with appropriate safeguards.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">4. Why We Process Personal Information</h2>
            <p>We process personal information for the following purposes:</p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Creating and managing accounts for drivers, merchants/stations, administrators and staff.</li>
                <li>Assessing eligibility, affordability, risk and fraud indicators for financing and operational approvals.</li>
                <li>Issuing, securing, delivering, redeeming and auditing vouchers and related operational events.</li>
                <li>Processing repayments and payments, reconciling settlements, and supporting financial reporting.</li>
                <li>Maintaining platform security, monitoring abuse, investigating incidents, and preventing fraud.</li>
                <li>Providing customer support, troubleshooting, and responding to queries and complaints.</li>
                <li>Meeting legal and regulatory obligations, including record-keeping and compliance requirements.</li>
                <li>Improving services, analytics, and product performance, including load/performance monitoring.</li>
            </ul>
            <p>
                Where we use location information (such as latitude/longitude) we do so to help you select or confirm an address, to improve
                operational accuracy, and to support fraud prevention and auditability (for example, confirming where a station redemption or
                operational event occurred). You can often choose to type an address instead of using device location.
            </p>
            <p>
                Location, mapping, and address suggestions may be provided using third-party mapping or geocoding services. These suggestions
                are best-effort and may not always include a full street-level address (for example, missing house numbers, unit numbers, or
                informal settlement naming). You remain responsible for checking and confirming your address details and ensuring they are
                accurate for onboarding, compliance, communication, and operational purposes.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">5. Lawful Bases for Processing (POPIA)</h2>
            <p>
                We only process personal information where permitted by POPIA. Our lawful bases may include:
                <span class="font-semibold">your consent</span> (where required), <span class="font-semibold">contractual necessity</span>
                (to provide the platform), <span class="font-semibold">legal obligation</span> (to comply with laws and regulations),
                and <span class="font-semibold">legitimate interests</span> (such as fraud prevention and service security), balanced against
                your rights and reasonable expectations.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">6. Accuracy and Updates</h2>
            <p>
                We take reasonable steps to keep information accurate and up to date. You are responsible for providing correct information
                and updating details when they change. Where available, the platform may assist by suggesting addresses and capturing
                coordinates to reduce errors.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">7. Cookies and Similar Technologies</h2>
            <p>
                We use cookies and similar technologies for essential site functionality, security, session management, and preferences.
                Additional cookies (for analytics or performance) may be used where configured. See our Cookie Policy for details, including
                how to manage your preferences.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">8. Automated Decisions and Profiling</h2>
            <p>
                Some platform functions may use automated checks to support operational decisions, for example credit or risk indicators,
                fraud signals, or eligibility checks used to route applications for further review. Where automation is used, it is designed
                to operate within predefined business rules and internal thresholds. We maintain human oversight for material outcomes, and
                we may request additional information to confirm accuracy before finalising a decision.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">9. Sharing and Disclosure</h2>
            <p>
                We do not sell personal information. We may share personal information with:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li><span class="font-semibold">Stations/merchants</span> to redeem vouchers, process operational events, and settle transactions.</li>
                <li><span class="font-semibold">Payment providers and banks</span> to process payments, verify transactions, and support settlements.</li>
                <li><span class="font-semibold">Service providers</span> (operators) who help us run the platform (hosting, email/SMS, analytics, security, mapping, support tools).</li>
                <li><span class="font-semibold">Regulators and authorities</span> where disclosure is required by law or a valid lawful request.</li>
                <li><span class="font-semibold">Professional advisers</span> such as auditors and legal advisers under confidentiality obligations.</li>
            </ul>
            <p>
                We require operators to implement appropriate security safeguards and to process information only for authorised purposes.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">10. Direct Marketing</h2>
            <p>
                We may send service-related communications (such as security notices, voucher status updates, repayment reminders, or support
                responses) as part of providing the platform. Where we send promotional or marketing communications, we will do so in
                accordance with applicable law. You can opt out of non-essential marketing messages using the unsubscribe mechanism provided
                or by contacting support.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">11. Cross-Border Transfers</h2>
            <p>
                Some service providers or infrastructure may be located outside South Africa. Where personal information is transferred
                cross-border, we implement safeguards aligned with POPIA, such as contractual protections and assessing the recipient’s
                security posture, to ensure that your personal information remains protected.
            </p>
            <p>
                Cross-border processing may occur for hosting, monitoring, secure content delivery, error reporting, or messaging services.
                Where we use Operators outside South Africa, we aim to ensure they are bound by confidentiality and security obligations and
                that they provide a level of protection that is substantially similar to POPIA’s requirements.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">12. Security Safeguards</h2>
            <p>
                We implement reasonable technical and organisational measures to protect personal information against loss, damage,
                unauthorised access, and unlawful processing. Measures may include access controls, encryption in transit, audit logs,
                segregation of duties, monitoring, secure development practices, and vendor due diligence.
            </p>
            <p>
                We also apply operational safeguards such as restricting access to onboarding documents, reviewing high-risk actions, and
                maintaining event logs for key workflows (voucher issuance, redemption, and repayment events). If you suspect unauthorised
                activity, contact us immediately so we can investigate and take appropriate action.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">13. Data Retention</h2>
            <p>
                We keep personal information only as long as necessary for the purposes described in this policy, unless a longer retention
                period is required or permitted by law (for example, for auditing, dispute resolution, and financial record-keeping).
                Retention periods may vary depending on the type of information and the applicable legal requirements.
            </p>
            <p>Examples of retention approaches (illustrative) include:</p>
            <ul class="list-disc pl-5 space-y-1">
                <li><span class="font-semibold">Operational audit logs</span> retained to support investigations, reconciliation, and dispute resolution.</li>
                <li><span class="font-semibold">Financial records</span> retained to support statutory recordkeeping and audits.</li>
                <li><span class="font-semibold">KYC documents</span> retained to support compliance, onboarding validation, and ongoing monitoring obligations.</li>
                <li><span class="font-semibold">Support communications</span> retained for a reasonable period to support service quality and complaint handling.</li>
            </ul>
            <p>
                When retention is no longer required, we take reasonable steps to securely delete, de-identify, or anonymise personal
                information, subject to technical limitations and lawful exceptions (for example, information retained in backups for a
                limited time for disaster recovery).
            </p>

            <h2 class="text-lg font-semibold text-slate-900">14. Your Rights Under POPIA</h2>
            <p>
                Subject to POPIA and other applicable laws, you may request to:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Access personal information we hold about you.</li>
                <li>Correct or update your personal information.</li>
                <li>Delete personal information where it is no longer lawfully required to be retained.</li>
                <li>Object to processing in certain circumstances.</li>
                <li>Withdraw consent (where processing is based on consent), noting that this may affect service availability.</li>
                <li>Lodge a complaint with us and, if unresolved, with the Information Regulator.</li>
            </ul>
            <p>
                Requests may require identity verification. We may refuse or limit a request where a lawful ground applies (for example,
                where disclosure would prejudice another person’s rights, compromise security, or conflict with legal obligations).
            </p>

            <h2 class="text-lg font-semibold text-slate-900">15. PAIA and Access to Records</h2>
            <p>
                PAIA provides a right of access to records held by private bodies, subject to conditions and grounds for refusal. Bwiser’s
                PAIA process is described in our PAIA Manual. Where a PAIA request relates to personal information, we handle the request in
                a manner aligned with POPIA and PAIA, including verifying the requester’s authority and applying lawful exemptions.
            </p>
            <p>
                If you wish to request records under PAIA, please follow the procedure in our PAIA Manual, including submitting the required
                prescribed forms, specifying the record requested, and paying any applicable fees in accordance with PAIA regulations.
            </p>
            <p>
                In certain cases we may request additional information to identify the record, confirm your authority to request it, or to
                process the request within statutory timelines. PAIA also sets out grounds on which access may be refused, for example to
                protect confidential commercial information, the safety of individuals, or where disclosure would be unlawful.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">16. Children</h2>
            <p>
                The platform is intended for adult users. If we become aware that we have processed personal information of a child without
                appropriate authority, we will take steps to delete it or otherwise handle it in accordance with applicable law.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">17. Changes to This Policy</h2>
            <p>
                We may update this Privacy Policy from time to time to reflect changes in law, platform features, or our processing
                activities. The “Last updated” date above will be updated, and material changes may be communicated through the platform.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">18. Practical Examples (What This Means in Real Use)</h2>
            <p>
                The Platform is built for operational auditability. In practice, this means that when you apply for a voucher, redeem a
                voucher, or make a repayment, the Platform records a structured event (who, what, when, and relevant references) so that we
                can support reconciliation, dispute resolution, and fraud prevention. For example:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>If a voucher is redeemed, we record the station identifier, the voucher identifier, the time of redemption, and the redemption outcome.</li>
                <li>If a payment attempt fails, we record the payment reference and failure outcome to support retries and troubleshooting.</li>
                <li>If location is enabled, we may record latitude/longitude at the moment an address is selected or a key operational event occurs to improve accuracy.</li>
            </ul>
            <p>
                These records are not collected for curiosity. They exist to make the system reliable and to support fair resolution of
                disagreements (for example, a driver claiming a voucher was not redeemed, or a station reporting a mismatch). We aim to
                minimise what we collect and to limit access to authorised personnel.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">19. Contact and Complaints</h2>
            <p>
                For privacy requests, POPIA enquiries, or PAIA-related requests, contact our Information Officer (or designated representative)
                at <span class="font-semibold">support@bwiser.co.za</span>. For operational support, use the in-app support channels where
                available.
            </p>
            <p>
                If you are not satisfied with our response, you may lodge a complaint with the Information Regulator in accordance with POPIA.
                We encourage you to contact us first so that we can address concerns promptly.
            </p>
            <p class="text-xs text-slate-500">
                Note: This Privacy Policy is provided for transparency and operational readiness and does not constitute legal advice. If you
                require legal advice, consult a qualified professional.
            </p>
        </div>
    </div>
</section>
@endsection
