@extends('Layouts.app')

@section('title', 'AML & KYC Policy')

@section('content')
<section class="max-w-4xl mx-auto px-6 py-12">
    <div class="glass rounded-2xl p-8 space-y-6">
        <h1 class="brand-font text-3xl text-slate-900">AML & KYC Policy</h1>
        @php
            $effectiveDate = 'March 11, 2026';
            $lastUpdated = 'March 11, 2026';
        @endphp
        <p class="text-sm text-slate-600">
            Effective date: {{ $effectiveDate }}. Last updated: {{ $lastUpdated }}. This policy is drafted for South African operations
            and is intended to be aligned with applicable AML/CFT obligations, including the Financial Intelligence Centre Act, 38 of 2001
            (“FICA”), and related regulatory requirements, where applicable.
        </p>

        <div class="space-y-4 text-sm text-slate-700">
            <h2 class="text-lg font-semibold text-slate-900">1. Purpose and Scope</h2>
            <p>
                Bwiser operates an operational finance and voucher platform that may be used by Drivers, Stations/Merchants, and internal
                operational staff. This Policy sets out how we apply a risk-based approach to anti-money laundering and counter-terrorist
                financing (“<span class="font-semibold">AML/CFT</span>”), including customer due diligence (“<span class="font-semibold">KYC</span>”),
                ongoing monitoring, escalation, reporting, and recordkeeping.
            </p>
            <p>
                This Policy applies to all Bwiser employees, contractors, and authorised users who onboard customers, review applications,
                approve vouchers or limits, redeem vouchers, process repayments, and manage settlements. Where third-party partners are used
                (for example, payment gateways), Bwiser applies vendor due diligence and contractual controls to ensure appropriate safeguards.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">2. Key Principles</h2>
            <ul class="list-disc pl-5 space-y-1">
                <li><span class="font-semibold">Risk-based approach</span>: controls are proportionate to the risk presented by a user, transaction, or station.</li>
                <li><span class="font-semibold">Know your customer</span>: we aim to understand who is using the Platform and the legitimacy of their activity.</li>
                <li><span class="font-semibold">Ongoing monitoring</span>: onboarding checks are not “once-off”; we monitor behaviour and events.</li>
                <li><span class="font-semibold">Auditability</span>: decisions and key events are logged for accountability and regulatory readiness.</li>
                <li><span class="font-semibold">Escalation</span>: suspicious activity is escalated quickly for investigation and action.</li>
            </ul>

            <h2 class="text-lg font-semibold text-slate-900">3. Roles and Responsibilities</h2>
            <p>
                Bwiser maintains an internal compliance function appropriate to its size and risk profile. Responsibilities may include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li><span class="font-semibold">Information/Compliance Officer</span>: oversight of AML/KYC implementation, escalations, and reporting.</li>
                <li><span class="font-semibold">Operations staff</span>: collecting documents, verifying information, applying controls, and escalating concerns.</li>
                <li><span class="font-semibold">Engineering and security</span>: implementing technical safeguards, logging, and monitoring.</li>
                <li><span class="font-semibold">Station/Merchant admins</span>: ensuring staff follow voucher redemption rules and report suspicious activity.</li>
            </ul>

            <h2 class="text-lg font-semibold text-slate-900">4. Customer Due Diligence (KYC)</h2>
            <p>
                We collect and verify information necessary to establish and maintain accounts, and to mitigate fraud and financial crime.
                KYC may be performed at onboarding and updated periodically or when risk triggers occur.
            </p>

            <h3 class="font-semibold text-slate-900">4.1 Driver KYC (Examples)</h3>
            <p>Depending on the product and risk level, Driver onboarding may include:</p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Full name, identity number (where applicable), date of birth, and contact details (email and phone).</li>
                <li>Proof of address or confirmation of address and geolocation capture for operational purposes.</li>
                <li>Driver licence or other role-relevant authorisations.</li>
                <li>Selfie or liveness checks (where implemented) to reduce impersonation risk.</li>
                <li>Device and session metadata for fraud prevention (for example, device ID or login fingerprint).</li>
            </ul>

            <h3 class="font-semibold text-slate-900">4.2 Merchant/Station KYC (Examples)</h3>
            <p>Merchant and Station onboarding may include:</p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Registered business name, registration number (where applicable), and trading address.</li>
                <li>Authorised representative details and proof of authority to act for the business.</li>
                <li>Business documentation (for example, CK documents, licences, and proof of banking details where needed).</li>
                <li>Optional B-BBEE documentation (where applicable) and any franchise/brand selection data used for operations.</li>
                <li>Station location coordinates (latitude/longitude) for operational matching and controls.</li>
            </ul>

            <h3 class="font-semibold text-slate-900">4.3 Enhanced Due Diligence (EDD)</h3>
            <p>
                We may apply enhanced checks for higher-risk cases. Examples of triggers include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Unusual behaviour or patterns inconsistent with the stated purpose of the account.</li>
                <li>Repeated failed repayments, multiple failed card authorisations, or payment method mismatch indicators.</li>
                <li>Frequent high-value voucher requests or rapid velocity changes.</li>
                <li>Geolocation anomalies (for example, repeated redemptions far from expected locations).</li>
                <li>Adverse media, fraud flags, or sanctions/PEP risk indicators (where screening is performed).</li>
            </ul>
            <p>
                EDD may include requesting additional documents, performing additional verification steps, applying tighter limits, or
                requiring manual review before approvals.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">5. Monitoring and Transaction Controls</h2>
            <p>
                We monitor onboarding, voucher issuance, redemption, repayments, and settlement events for anomalies and potential misuse.
                Monitoring may include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Velocity checks (sudden increase in voucher applications, redemptions, or repayment attempts).</li>
                <li>Repeated redemption failures and retry patterns.</li>
                <li>Station redemption behaviours (for example, unusually high redemption rates vs peers).</li>
                <li>Geographic patterns (distance between driver location and station, and repeated out-of-area usage).</li>
                <li>Device and session patterns (multiple accounts on the same device, unusual login locations).</li>
            </ul>
            <p>
                Controls may include step-up verification, temporary holds, manual review, and in serious cases, suspension of voucher
                issuance or redemption privileges.
            </p>
            <p class="font-semibold text-slate-900">5.1 Station/Merchant Operational Controls (Examples)</p>
            <p>
                Because vouchers are redeemed at stations, merchant controls are a key part of AML and fraud prevention. Depending on risk
                and operational design, controls may include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Restricting which staff can redeem vouchers, and logging staff identifiers per redemption.</li>
                <li>Requiring voucher redemption only through the Platform workflow (scan/verify/confirm).</li>
                <li>Limiting redemption volume per station or per staff member based on operational thresholds.</li>
                <li>Flagging stations with unusual redemption-to-settlement ratios or repeated disputes.</li>
                <li>Requiring additional verification for high-value redemptions or unusual time-of-day patterns.</li>
            </ul>
            <p>
                Stations are expected to report suspected fraud promptly. Failure to comply with redemption rules or repeated suspicious
                activity may result in suspension from the Platform, delayed settlements pending review, or termination of the relationship.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">6. Suspicious Activity Escalation and Reporting</h2>
            <p>
                Bwiser encourages immediate escalation of suspicious activity. Examples include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Attempts to redeem a voucher without providing goods/services.</li>
                <li>Evidence of identity misuse, forged documents, or impersonation.</li>
                <li>Unexplained payment behaviour or use of unauthorised payment methods.</li>
                <li>Collusion between drivers and stations or repeated irregular redemptions.</li>
            </ul>
            <p>
                High-risk cases are escalated to designated compliance personnel for investigation. Where required by law and applicable to
                the services provided, suspicious activity reports may be filed with competent South African authorities, including the
                Financial Intelligence Centre, in accordance with applicable legal obligations.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">7. Sanctions and Prohibited Activity</h2>
            <p>
                Bwiser does not knowingly support activity that is prohibited by law or sanctions. Where screening is performed, we may
                check users and business entities against sanctions or other risk lists using lawful methods and reputable sources. If a
                match is suspected, we may suspend onboarding or activity and conduct further review.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">8. Training and Awareness</h2>
            <p>
                Relevant staff are trained on AML/KYC concepts appropriate to their role, including identifying red flags, handling
                documents safely, protecting personal information, and escalating concerns. Training may be refreshed periodically and
                when policies or risk conditions change.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">9. Recordkeeping and Retention</h2>
            <p>
                We keep KYC documentation, decision logs, and audit trails in accordance with our information governance program and
                applicable legal requirements. Where FICA applies, records may generally be retained for at least five years after the end
                of the business relationship or the date of a single transaction, as applicable, subject to any longer retention required
                for disputes, audit, or other legal obligations.
            </p>
            <p>
                Records are retained to support traceability of decisions and events (who approved what, when, and why), which is important
                for preventing fraud and for responding to disputes. Access to retained KYC and audit records is restricted, and we aim to
                ensure that staff access is logged and reviewed where appropriate.
            </p>
            <p>
                When retention is no longer necessary, we take reasonable steps to securely delete, de-identify, or anonymise records,
                subject to technical constraints (for example, backup retention windows).
            </p>

            <h2 class="text-lg font-semibold text-slate-900">10. Data Protection and POPIA</h2>
            <p>
                KYC involves processing personal information. We handle personal information in line with POPIA and our Privacy Policy.
                Access to KYC records is restricted to authorised personnel, and we apply security safeguards such as encryption in transit,
                access controls, and audit logging.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">11. Policy Review</h2>
            <p>
                This Policy is reviewed and updated periodically to reflect operational changes, risk evolution, and legal developments.
                Material updates may be communicated to staff and reflected in onboarding or workflow requirements.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">12. Customer Risk Rating (Illustrative)</h2>
            <p>
                Bwiser applies a risk-based approach. In practice, this means we may assign an internal risk rating to accounts and
                transactions and apply controls proportionate to that rating. Factors that may influence a risk rating include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Customer type (Driver vs Merchant/Station), business structure, and onboarding completeness.</li>
                <li>Transaction behaviour (frequency, size, timing) relative to expected usage.</li>
                <li>Geographic patterns and whether redemptions match expected operational areas.</li>
                <li>Repayment performance, failed payment patterns, and account anomalies.</li>
                <li>Fraud indicators (duplicate identities, suspicious devices, repeated verification failures).</li>
            </ul>
            <p>
                Higher-risk cases may require additional documentation, tighter operational limits, manual review, or refusal where risk
                cannot be mitigated to an acceptable level.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">13. Red Flags (Examples)</h2>
            <p>
                Staff and Station/Merchant users should be alert to red flags that may indicate fraud or financial crime. Examples include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Onboarding documents that appear altered, inconsistent, or duplicated across multiple accounts.</li>
                <li>Drivers requesting repeated vouchers and immediately redeeming at the same station in unusual patterns.</li>
                <li>Station redemptions that spike suddenly or do not align with typical operational volumes.</li>
                <li>Drivers insisting on redemption without being present or requesting redemption outside approved conditions.</li>
                <li>Multiple accounts using the same device identifiers, contact information, or banking details without a clear explanation.</li>
                <li>Unusual repayment activity, including multiple failed attempts from different payment methods.</li>
                <li>Attempts to circumvent controls, including repeated retries after declines or use of multiple identities.</li>
            </ul>
            <p>
                Red flags do not automatically mean wrongdoing, but they do require attention. Where a pattern cannot be reasonably
                explained, we may request additional supporting information, apply temporary limits, or route the case for manual review.
                This helps protect legitimate users from fraud and reduces the risk of financial losses for stations and partners.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">14. Action on Suspicion</h2>
            <p>
                When suspicion is identified, the immediate priority is to protect customers and the Platform. Actions may include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Placing a temporary hold on a voucher application, redemption, or settlement.</li>
                <li>Requesting additional verification or documentation.</li>
                <li>Suspending accounts or restricting actions pending investigation.</li>
                <li>Preserving logs and evidence for investigation and audit.</li>
            </ul>
            <p>
                Where reporting obligations apply, reporting is handled through designated compliance channels. Users should not attempt to
                “tip off” any person that a report is being considered or has been made where such disclosure is prohibited by law.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">15. Testing, Review, and Continuous Improvement</h2>
            <p>
                Financial crime risks evolve. Bwiser aims to periodically review the effectiveness of its controls and to improve them as the
                Platform grows. This may include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Reviewing alert thresholds and monitoring rules to reduce false positives while still catching high-risk cases.</li>
                <li>Sampling and review of onboarding decisions for consistency and quality.</li>
                <li>Periodic review of station redemption patterns and settlement anomalies.</li>
                <li>Updating training content when new fraud patterns are detected or when regulations change.</li>
            </ul>
            <p>
                Where feasible, we may also conduct internal audits or request external reviews for high-risk workflows. Findings may result
                in tighter controls, updated onboarding requirements, or changes to approval thresholds.
            </p>
            <p>
                Monitoring rules and risk ratings are not static. They are tuned based on observed behaviour, confirmed fraud cases, and
                operational feedback from stations and support teams. Changes are documented so that decisions remain explainable and
                auditable.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">16. Contact</h2>
            <p>
                For AML/KYC queries or to report suspicious activity, contact <span class="font-semibold">support@bwiser.co.za</span> or use
                designated internal escalation channels.
            </p>
            <p>
                Where you are reporting an issue from a station, include the station name, voucher reference, date/time, and a short
                description of what occurred. This helps us correlate the report with audit logs and take action quickly.
            </p>
            <p>
                Reports are handled confidentially and escalated to compliance or security where appropriate.
            </p>

            <p class="text-xs text-slate-500">
                Note: This policy is provided for transparency and operational readiness and does not constitute legal advice. If you
                require legal advice about AML/CFT obligations, consult a qualified professional.
            </p>
        </div>
    </div>
</section>
@endsection
