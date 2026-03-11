@extends('Layouts.app')

@section('title', 'PCI DSS & ISO Compliance')

@section('content')
<section class="max-w-4xl mx-auto px-6 py-12">
    <div class="glass rounded-2xl p-8 space-y-6">
        <h1 class="brand-font text-3xl text-slate-900">PCI DSS & ISO Compliance Information</h1>
        @php
            $effectiveDate = 'March 11, 2026';
            $lastUpdated = 'March 11, 2026';
        @endphp
        <p class="text-sm text-slate-600">
            Security posture summary for production use. Effective date: {{ $effectiveDate }}. Last updated: {{ $lastUpdated }}.
        </p>

        <div class="space-y-4 text-sm text-slate-700">
            <h2 class="text-lg font-semibold text-slate-900">1. Purpose of This Page</h2>
            <p>
                This page explains Bwiser’s security and compliance posture in clear operational language. It is intended to help Drivers,
                Merchants/Stations, partners, and stakeholders understand how we protect data and what to expect from our security controls.
                It does not replace formal contractual security terms, an audit report, or a third-party certification.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">2. PCI DSS Scope and Card Data Approach</h2>
            <p>
                Where card payments are used (for example, repayments or card authorisations for Autopay), Bwiser aims to minimise exposure
                to cardholder data by using integrated payment gateways and tokenised payment workflows. As a principle:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>We do not intentionally store full primary account numbers (PAN), CVV/CVC codes, or magnetic stripe data in application databases.</li>
                <li>We rely on payment processors to capture and store sensitive payment details, returning tokenised references and transaction outcomes.</li>
                <li>We store operational metadata needed for reconciliation and audit (for example, payment reference, status, timestamps), and tokenised payment identifiers where applicable.</li>
            </ul>
            <p>
                PCI DSS scope depends on how payments are implemented and how users interact with the payment gateway. We continuously aim to
                reduce scope by keeping sensitive entry points within approved payment components and by limiting what our systems store and
                process.
            </p>
            <p>
                PCI DSS is a broad standard covering network security, secure configuration, access control, monitoring, and regular testing.
                While the exact set of PCI DSS controls applicable to Bwiser depends on system design and the payment gateway integration,
                our design approach is to keep cardholder data out of our systems wherever possible and to focus on strong security controls
                around the application and infrastructure that support payment initiation and reconciliation.
            </p>
            <p class="font-semibold text-slate-900">Examples of PCI-aligned control areas we focus on include:</p>
            <ul class="list-disc pl-5 space-y-1">
                <li><span class="font-semibold">Secure transmission</span>: enforcing HTTPS/TLS for production web traffic.</li>
                <li><span class="font-semibold">Least privilege</span>: limiting who can access repayment and settlement tooling.</li>
                <li><span class="font-semibold">Logging</span>: recording payment initiation and outcome events for audit and reconciliation.</li>
                <li><span class="font-semibold">Secure defaults</span>: restricting administrative endpoints and hardening server configuration.</li>
                <li><span class="font-semibold">Vendor due diligence</span>: selecting payment providers that can support tokenisation and have appropriate security controls.</li>
            </ul>

            <h2 class="text-lg font-semibold text-slate-900">3. Security Governance (ISO-Aligned)</h2>
            <p>
                Bwiser’s security program is designed to align with information security management principles commonly associated with
                ISO/IEC 27001. This means we aim to apply a structured approach to identifying security risks, applying controls, and
                reviewing effectiveness. Key governance concepts include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li><span class="font-semibold">Policies</span> that define acceptable use, access control, data handling, and incident response.</li>
                <li><span class="font-semibold">Risk assessment</span> to identify threats to confidentiality, integrity, and availability.</li>
                <li><span class="font-semibold">Control implementation</span> across people, processes, and technology.</li>
                <li><span class="font-semibold">Monitoring and continuous improvement</span> as systems, threats, and legal requirements evolve.</li>
            </ul>

            <h2 class="text-lg font-semibold text-slate-900">4. Access Control and Authentication</h2>
            <p>
                Access controls are designed around least privilege. Users only receive the permissions required to perform their role.
                Typical controls include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Role-based access control (RBAC) separating Driver, Station/Merchant, and Admin permissions.</li>
                <li>Server-side session management and CSRF protection for web actions.</li>
                <li>Secure password storage using one-way hashing.</li>
                <li>Optional “Remember me” session behaviour using secure cookies for persistent login.</li>
                <li>Administrative actions protected by authentication and permission checks, with audit trails where applicable.</li>
            </ul>

            <h2 class="text-lg font-semibold text-slate-900">5. Data Protection and Encryption</h2>
            <p>
                Bwiser applies layered controls to protect data:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li><span class="font-semibold">Encryption in transit</span>: web traffic is protected using TLS for production environments.</li>
                <li><span class="font-semibold">Encryption and secure handling</span>: sensitive fields may be encrypted at rest or stored as tokenised references depending on the nature of the data (for example, payment tokens).</li>
                <li><span class="font-semibold">Key management</span>: encryption keys and credentials should be stored in environment variables or secure secret stores, and rotated when necessary.</li>
                <li><span class="font-semibold">Segregation</span>: production secrets should not be committed to version control and should be restricted to authorised operators.</li>
            </ul>

            <h2 class="text-lg font-semibold text-slate-900">6. Application Security and Secure Development</h2>
            <p>
                We apply secure development practices to reduce vulnerabilities. Common measures include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Input validation and server-side enforcement of authorisation for sensitive actions.</li>
                <li>Framework-level protections against common web risks (for example, CSRF protections for forms).</li>
                <li>Security headers and cookie hardening to reduce exposure to browser-based attacks.</li>
                <li>Dependency management and patching of known vulnerabilities where practicable.</li>
                <li>Logging for security-relevant events and administrative actions.</li>
            </ul>
            <p>
                While no software can be guaranteed to be free of vulnerabilities, the goal is to reduce risk and respond quickly when
                issues are identified.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">7. Logging, Monitoring, and Audit Trails</h2>
            <p>
                The Platform maintains audit trails for key operational events to support accountability, dispute resolution, fraud
                investigations, and compliance readiness. Examples include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Voucher application, approval/decline, issuance, and redemption events.</li>
                <li>Repayment initiation, outcome, and failure events.</li>
                <li>Administrative changes affecting limits, station configurations, or user status.</li>
                <li>Security events such as login failures or unusual activity flags.</li>
            </ul>
            <p>
                Monitoring may include automated alerts for unusual patterns (velocity, repeated failures, anomalous locations) and may be
                used to trigger manual review.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">8. Infrastructure and Network Security</h2>
            <p>
                Security controls depend on the hosting environment and may include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Firewall rules allowing only necessary inbound traffic (for example, HTTPS and SSH for administration).</li>
                <li>Regular operating system and package updates where feasible.</li>
                <li>Separation of environments (development vs production) and access restrictions.</li>
                <li>Backups and disaster recovery practices appropriate to the risk and data sensitivity.</li>
            </ul>

            <h2 class="text-lg font-semibold text-slate-900">9. Vendor and Third-Party Risk Management</h2>
            <p>
                Bwiser may rely on third parties for hosting, payment processing, messaging, mapping, and analytics. We aim to manage third
                party risk by:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Performing onboarding due diligence (security posture, availability history, and compliance claims).</li>
                <li>Using contractual controls, including confidentiality, security obligations, and breach notification expectations.</li>
                <li>Limiting data sharing to what is necessary for the service.</li>
                <li>Reviewing vendors periodically based on risk and performance.</li>
            </ul>

            <h2 class="text-lg font-semibold text-slate-900">10. Incident Response and Breach Notification</h2>
            <p>
                Bwiser maintains an incident response approach designed to detect, contain, investigate, and recover from security
                incidents. If a security compromise involves personal information, we aim to comply with applicable POPIA notification
                obligations and to communicate with affected parties where required.
            </p>
            <p>
                Incident response generally includes triage, containment actions, forensic review where appropriate, corrective actions,
                and post-incident improvements.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">11. Business Continuity and Availability (ISO 22301 Principles)</h2>
            <p>
                Operational continuity is important for real-time workflows (voucher issuance and redemption). Bwiser aims to apply
                continuity practices aligned with principles commonly associated with ISO 22301, including:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Backups and restore testing appropriate to environment and risk.</li>
                <li>Monitoring for service health and alerting for critical failures.</li>
                <li>Change management to reduce deployment risk.</li>
                <li>Documented recovery steps for critical services.</li>
            </ul>

            <h2 class="text-lg font-semibold text-slate-900">12. What We Ask of Users</h2>
            <p>
                Security is shared. We ask users to:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Use strong, unique passwords and keep credentials confidential.</li>
                <li>Sign out of shared devices and avoid using public computers for sensitive actions.</li>
                <li>Keep devices updated and protected with a passcode or biometric lock.</li>
                <li>Report suspicious activity promptly.</li>
            </ul>

            <h2 class="text-lg font-semibold text-slate-900">13. Compliance Statements and Limitations</h2>
            <p>
                References to PCI DSS and ISO standards on this page describe the security controls we aim to align with and the way we
                manage scope. They do not necessarily mean that Bwiser is currently certified under a specific standard, unless expressly
                stated in a signed document. Where formal compliance evidence is required (for example, an Attestation of Compliance from a
                payment processor), it should be requested through commercial and compliance channels.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">14. Vulnerability Management</h2>
            <p>
                Bwiser aims to identify and address vulnerabilities across applications, dependencies, and infrastructure. Vulnerability
                management practices may include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Keeping critical dependencies up to date where practicable.</li>
                <li>Reviewing security advisories relevant to core frameworks and libraries.</li>
                <li>Applying patches for high-severity issues on a prioritised basis.</li>
                <li>Reviewing logs and alerts for indicators of compromise or unusual behaviour.</li>
            </ul>
            <p>
                Remediation timelines depend on severity and exploitability. High-risk issues affecting authentication, payments, or voucher
                redemption are prioritised. Where a temporary mitigation is available (for example, disabling a feature, adding a server-side
                validation rule, or tightening permissions), we may apply mitigations immediately while a permanent fix is developed and
                tested. We also aim to avoid introducing new security risk while fixing issues, which can require staged releases.
            </p>
            <p>
                If you believe you have discovered a security vulnerability, please report it responsibly to our support contact. Do not
                attempt to exploit vulnerabilities on production systems.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">15. Data Minimisation and Classification</h2>
            <p>
                We aim to minimise the amount of sensitive data processed and stored. Where personal information is required, we classify
                data by sensitivity and apply safeguards appropriate to that classification. Examples include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li><span class="font-semibold">Public</span>: content intended for public viewing (policy pages and general product information).</li>
                <li><span class="font-semibold">Internal</span>: operational information used by staff and authorised station users.</li>
                <li><span class="font-semibold">Confidential</span>: personal information, KYC documents, repayment metadata, and security logs with restricted access.</li>
                <li><span class="font-semibold">Highly sensitive</span>: tokens, secrets, and security-critical configuration, handled through protected secret management practices.</li>
            </ul>

            <h2 class="text-lg font-semibold text-slate-900">16. Change Management and Release Safety</h2>
            <p>
                Because the Platform supports financial and operational workflows, changes are managed to reduce the risk of regressions and
                outages. Practices may include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Access-controlled deployments and separation of duties where feasible.</li>
                <li>Configuration management through environment variables and secure secret handling.</li>
                <li>Rollback and recovery procedures for critical services.</li>
                <li>Post-deployment validation and monitoring to catch errors early.</li>
            </ul>
            <p>
                In addition to operational release controls, application-level secure coding practices are important. We aim to reduce common
                risks aligned with widely recognised threat categories (such as the OWASP Top 10), including injection risks, broken access
                control, insecure configuration, and sensitive data exposure. This is supported through framework protections, code review,
                and targeted testing for high-risk endpoints.
            </p>
            <p>
                Backups and recovery are part of release safety: a deployment should not compromise the ability to restore service. Where
                possible, we test restoration procedures and ensure that backup retention is configured to balance operational needs with
                privacy principles and retention requirements.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">17. Physical and Operational Security</h2>
            <p>
                Physical security controls depend on the hosting model used (cloud, data centre, or managed services). We aim to use hosting
                providers that implement appropriate physical security controls and access restrictions. Operational security also includes
                controlling access to administrative systems, keeping audit trails, and limiting administrative activities to authorised
                personnel.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">18. Contact</h2>
            <p>
                For security enquiries, suspected vulnerabilities, or incident reporting, contact
                <span class="font-semibold">support@bwiser.co.za</span>.
            </p>
            <p>
                When reporting an issue, include as much detail as possible (page/feature, timestamps, screenshots where safe, and any
                reference IDs). Please do not send sensitive secrets or full payment card information. We will acknowledge reports and may
                request further information to validate and remediate.
            </p>
            <p>
                If the issue relates to a suspected account compromise, we may recommend immediate password changes and may temporarily
                restrict account actions while we investigate.
            </p>
            <p>
                We aim to prioritise reports that affect payments, voucher redemption, authentication, and personal information.
            </p>
            <p>
                For general product support requests (not security issues), please use normal support channels so that security reports can
                be triaged quickly. If you are unsure whether something is a security issue, include that uncertainty in the report and we
                will route it appropriately.
            </p>
            <p>We appreciate responsible disclosure.</p>

            <p class="text-xs text-slate-500">
                Note: This page is provided for transparency and operational readiness and does not constitute legal advice or a formal
                certification statement.
            </p>
        </div>
    </div>
</section>
@endsection
