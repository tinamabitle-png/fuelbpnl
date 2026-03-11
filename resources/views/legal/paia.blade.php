@extends('Layouts.app')

@section('title', 'PAIA Manual')

@section('content')
<section class="max-w-4xl mx-auto px-6 py-12">
    <div class="glass rounded-2xl p-8 space-y-6">
        <h1 class="brand-font text-3xl text-slate-900">PAIA Manual</h1>
        @php
            $effectiveDate = 'March 11, 2026';
            $lastUpdated = 'March 11, 2026';
        @endphp
        <p class="text-sm text-slate-600">
            Prepared under the Promotion of Access to Information Act, 2 of 2000 (“PAIA”), South Africa. Effective date: {{ $effectiveDate }}.
            Last updated: {{ $lastUpdated }}.
        </p>

        <div class="space-y-4 text-sm text-slate-700">
            <h2 class="text-lg font-semibold text-slate-900">1. Introduction</h2>
            <p>
                PAIA gives effect to the constitutional right of access to information held by public and private bodies, subject to
                conditions and limitations. This PAIA Manual is compiled for Bwiser as a private body and describes:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Contact details for the Information Officer and how to submit requests.</li>
                <li>The categories of records held by Bwiser and how they may be accessed.</li>
                <li>The request procedure, fees, and grounds for refusal under PAIA.</li>
                <li>How PAIA requests interact with POPIA where personal information is involved.</li>
            </ul>
            <p>
                This Manual is intended to support operational readiness and transparency. It should be reviewed and finalised with legal
                counsel to ensure the Manual reflects the organisation’s structure and record categories in production.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">2. Company Details</h2>
            <p>
                <span class="font-semibold">Legal name</span>: Bwiser (private body).<br>
                <span class="font-semibold">Principal place of business</span>: Johannesburg, South Africa (update with the registered address for production).<br>
                <span class="font-semibold">General contact</span>: <span class="font-semibold">support@bwiser.co.za</span>.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">3. Information Officer</h2>
            <p>
                In terms of PAIA (and POPIA), the head of a private body is deemed the Information Officer. Bwiser designates an Information
                Officer and may designate Deputy Information Officers. For operational purposes, requests should be directed to the contact
                details below. If these details change, Bwiser will update this Manual.
            </p>
            <div class="rounded-xl border border-slate-200 bg-white/50 p-4">
                <p class="font-semibold text-slate-900">Information Officer Contact</p>
                <p>
                    Email: <span class="font-semibold">support@bwiser.co.za</span><br>
                    Subject line: <span class="font-mono">PAIA Request</span><br>
                    Additional contact details: to be published in production (postal address and telephone number).
                </p>
            </div>

            <h2 class="text-lg font-semibold text-slate-900">4. Guide on How to Use PAIA</h2>
            <p>
                A guide on PAIA is available from the Information Regulator (South Africa). The guide explains how to submit requests and
                the remedies available. If you need assistance, you may also contact Bwiser’s Information Officer using the details above.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">5. Records Automatically Available</h2>
            <p>
                Certain records may be available without a formal PAIA request (for example, information on our website). Examples may
                include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Public-facing policy pages (Privacy Policy, Cookie Policy, POPIA Notice, AML/KYC Policy, Security/Compliance pages).</li>
                <li>General product information and help content.</li>
                <li>Marketing and informational material intended for public use.</li>
            </ul>

            <h2 class="text-lg font-semibold text-slate-900">6. Categories of Records Held by Bwiser</h2>
            <p>
                Bwiser holds records in various categories. Access is subject to PAIA, POPIA, confidentiality obligations, and grounds for
                refusal. Categories may include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li><span class="font-semibold">Corporate and governance records</span>: incorporation records, board resolutions, policies, and governance documents.</li>
                <li><span class="font-semibold">Financial and accounting records</span>: financial statements, reconciliations, invoices, payment records, and audit reports.</li>
                <li><span class="font-semibold">Operational records</span>: voucher issuance logs, redemption audit trails, settlement records, operational reporting, and support tickets.</li>
                <li><span class="font-semibold">Customer records</span>: onboarding information, contracts (where applicable), account profiles, and communications.</li>
                <li><span class="font-semibold">Compliance records</span>: AML/KYC decisions, verification logs, risk assessments, and incident records.</li>
                <li><span class="font-semibold">Human resources records</span>: employee and contractor records, where applicable.</li>
                <li><span class="font-semibold">IT and security records</span>: access logs, security monitoring logs, change management, and incident response documentation.</li>
                <li><span class="font-semibold">Legal records</span>: agreements, legal correspondence, and dispute records.</li>
            </ul>
            <p>
                Some records may be stored electronically and may be maintained by third-party Operators (for example, hosting providers)
                under contractual controls. Where records are held by Operators, requests will be processed through Bwiser’s Information
                Officer.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">7. Records That May Be Requested</h2>
            <p>
                Subject to PAIA, a requester may request access to records held by Bwiser. Examples may include copies of contracts (where
                applicable), account-specific operational records, or correspondence, subject to identity verification and lawful grounds.
                Requests must be specific enough for Bwiser to identify the record.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">8. Request Procedure</h2>
            <p>
                Requests must be made in the prescribed form and submitted to the Information Officer. Where a request is made on behalf of
                another person, proof of authority must be provided. A request should include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>The requester’s full names and contact details.</li>
                <li>Details of the record requested (sufficient for identification).</li>
                <li>The preferred format of access (inspection, electronic copy, printed copy).</li>
                <li>The reason the record is required (where required by PAIA for private body requests).</li>
                <li>Proof of identity and, where relevant, proof of authority to act.</li>
            </ul>
            <p>
                Bwiser may request additional information to process the request, confirm identity, or locate the record. If the request is
                unclear, Bwiser may assist the requester to clarify it.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">9. Timeframes</h2>
            <p>
                Requests are processed in accordance with PAIA timelines. Timeframes may be extended in circumstances permitted by PAIA,
                such as where the request is complex, involves a large number of records, or requires consultation with third parties.
            </p>
            <p>
                If a request is time-sensitive (for example, for an urgent legal matter), you should indicate this in the request and
                provide supporting context. While PAIA sets statutory processes, additional clarity can help us prioritise triage and reduce
                delays caused by incomplete information.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">10. Fees</h2>
            <p>
                PAIA provides for request fees and access fees for private body requests in certain circumstances. Fees may include costs
                for searching, preparing, and reproducing records. The Information Officer will advise the requester of any applicable fees
                and payment requirements before processing where required.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">11. Grounds for Refusal</h2>
            <p>
                PAIA sets out grounds on which a private body may refuse access. Grounds may include, for example:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Protection of personal information of a third party (subject to POPIA and PAIA).</li>
                <li>Protection of confidential commercial information and trade secrets.</li>
                <li>Protection of privileged legal documents and legal professional privilege.</li>
                <li>Protection of safety and security of individuals or property.</li>
                <li>Protection of records that, if disclosed, would prejudice investigations or security controls.</li>
            </ul>
            <p>
                Where access is refused, Bwiser will provide reasons as required and outline available remedies or further steps under PAIA.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">12. POPIA Alignment and Access to Personal Information</h2>
            <p>
                Where a request relates to personal information, Bwiser will handle it in a manner aligned with both PAIA and POPIA. This
                includes verifying the requester’s identity, confirming authority (where acting on behalf of another person), and applying
                lawful exemptions and protections.
            </p>
            <p>
                Data subjects may also request correction or deletion of personal information under POPIA where applicable. Such requests
                may be processed through the same contact channel and may require additional verification.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">13. Remedies</h2>
            <p>
                If you are not satisfied with the outcome of a PAIA request, you may have remedies available under PAIA, including lodging
                a complaint with the Information Regulator and/or taking other steps as permitted by law. Bwiser encourages requesters to
                contact us first to attempt to resolve disputes or misunderstandings.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">14. Availability of This Manual</h2>
            <p>
                This Manual is available on our website at the PAIA Manual page. A copy may be provided upon request in a reasonable format
                where practicable.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">15. Forms and Submission Requirements (Practical Guidance)</h2>
            <p>
                PAIA requests should be submitted using the prescribed request form. The form generally requires the requester to identify
                themselves, describe the record requested, and explain the right being exercised and why the record is required to exercise
                or protect that right (for private body requests). Where a request is made on behalf of another person, proof of authority
                must be provided.
            </p>
            <p>
                To help us process requests efficiently, include as much detail as possible, for example:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Account identifiers (registered email/phone) where the request relates to a Platform account.</li>
                <li>Transaction references, voucher IDs, repayment references, or dates for operational records.</li>
                <li>The format you prefer (PDF, printed copy, inspection at premises), where feasible.</li>
            </ul>

            <h2 class="text-lg font-semibold text-slate-900">16. Fee Principles (High-Level)</h2>
            <p>
                Fees under PAIA are determined by regulations and may change from time to time. Where fees are applicable, we may require a
                deposit prior to processing. Fees may include costs for:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Searching for and preparing the record.</li>
                <li>Reproducing the record (printing, copying, converting to digital format).</li>
                <li>Postage or courier where physical delivery is requested.</li>
            </ul>
            <p>
                If you want an estimate, include an indication of the scope (date ranges, specific transactions) so we can assess likely
                effort.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">17. Third-Party Consultation</h2>
            <p>
                Some records may contain information about third parties (for example, a station’s operational data linked to a redemption).
                PAIA may require consultation with relevant third parties before disclosure. This can extend processing timelines. Bwiser will
                apply the relevant legal process and protect personal information and confidential commercial information where applicable.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">18. Common Grounds for Refusal (Expanded)</h2>
            <p>
                In addition to the broad grounds described above, requests may be refused or partially granted where:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Disclosure would reveal confidential commercial information (trade secrets, pricing, proprietary scoring methods).</li>
                <li>Disclosure would prejudice the security of the Platform, including fraud detection measures and internal controls.</li>
                <li>Disclosure would unreasonably disclose personal information of other data subjects, unless consent or a lawful basis exists.</li>
                <li>Records are legally privileged or prepared for litigation.</li>
                <li>The request does not meet statutory requirements (for example, the right and necessity requirement for private body records).</li>
            </ul>
            <p>
                Where partial access is possible, we may redact portions of records to protect protected information while providing access
                to the remainder, in line with PAIA.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">19. How We Provide Access (Formats and Redaction)</h2>
            <p>
                Where access is granted, Bwiser will provide the record in a format that is reasonably practicable, taking into account the
                nature of the record, security considerations, and the requester’s preference. Typical formats may include:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Electronic copies (for example, PDF exports of account and transaction history).</li>
                <li>Printed copies, where electronic delivery is not suitable.</li>
                <li>Inspection of records at an agreed location (where appropriate and lawful).</li>
            </ul>
            <p>
                To protect confidentiality and personal information of other data subjects, we may redact records. Redaction means removing
                or obscuring protected parts of a record (for example, another person’s personal information, confidential commercial terms,
                or security-sensitive internal controls) while still providing access to the remainder where possible.
            </p>
            <p>
                For Platform records, a large portion of information is stored as structured audit events (voucher application, approval,
                redemption, repayment events). Where access is granted, we may extract relevant events for the requester’s account and
                provide them in a readable format.
            </p>
            <p>
                If a record cannot be provided in the requested format, we will explain why and offer an alternative format where possible.
                We may also apply additional verification steps before releasing records that could expose account security (for example,
                detailed login or device metadata). Where a request is overly broad, we may ask you to narrow the scope to specific date
                ranges, transaction references, or record types to reduce delays and costs.
            </p>
            <p>
                Bwiser’s goal is to provide access where lawful and practical. However, we must balance access with privacy, confidentiality,
                and security. In some cases, the most appropriate outcome is to provide a summary record rather than raw internal logs, or to
                provide partial records with redactions. If you believe a refusal or redaction is incorrect, you may follow the remedies
                described above.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">20. Contact</h2>
            <p>
                Submit PAIA requests to: <span class="font-semibold">support@bwiser.co.za</span> (subject: <span class="font-mono">PAIA Request</span>).
            </p>
            <p>
                If you do not receive an acknowledgement within a reasonable time, you may resend the request and include any reference
                number previously issued. Please avoid including passwords or highly sensitive credentials in email. Where additional
                verification is needed, we will request it through a secure and appropriate channel.
            </p>

            <p class="text-xs text-slate-500">
                Note: This PAIA Manual is provided for transparency and operational readiness and does not constitute legal advice.
            </p>
        </div>
    </div>
</section>
@endsection
