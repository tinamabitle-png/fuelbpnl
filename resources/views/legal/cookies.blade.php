@extends('Layouts.app')

@section('title', 'Cookie Policy')

@section('content')
<section class="max-w-4xl mx-auto px-6 py-12">
    <div class="glass rounded-2xl p-8 space-y-6">
        <h1 class="brand-font text-3xl text-slate-900">Cookie Policy</h1>
        @php
            $effectiveDate = 'March 11, 2026';
            $lastUpdated = 'March 11, 2026';
        @endphp
        <p class="text-sm text-slate-600">Effective date: {{ $effectiveDate }}. Last updated: {{ $lastUpdated }}.</p>

        <div class="space-y-4 text-sm text-slate-700">
            <p>
                This Cookie Policy explains how Bwiser uses cookies and similar technologies on our website and web application
                (“<span class="font-semibold">Web</span>”). It also explains your choices. For information about how we process personal
                information more broadly, please read our Privacy Policy and POPIA Notice.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">1. What Are Cookies?</h2>
            <p>
                Cookies are small text files stored on your device by your browser when you visit a website. Cookies help a site remember
                information about your session (such as being logged in) and can also support security and performance features. Similar
                technologies include local storage, session storage, device identifiers, and tracking pixels. In this policy, we refer to
                all of these collectively as “cookies”, unless otherwise stated.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">2. Why Bwiser Uses Cookies</h2>
            <p>We use cookies for a limited set of operational purposes, including:</p>
            <ul class="list-disc pl-5 space-y-1">
                <li><span class="font-semibold">Security</span>: protecting sessions, preventing unauthorised requests, and detecting abuse.</li>
                <li><span class="font-semibold">Authentication</span>: keeping you signed in as you navigate between pages.</li>
                <li><span class="font-semibold">Preferences</span>: storing consent preferences and basic UI settings.</li>
                <li><span class="font-semibold">Operational continuity</span>: ensuring key workflows work reliably (for example, voucher redemptions and repayments in the web interface).</li>
                <li><span class="font-semibold">Analytics</span> (optional): measuring performance and improving usability where we have an appropriate lawful basis and, where required, your consent.</li>
            </ul>

            <h2 class="text-lg font-semibold text-slate-900">3. Cookie Categories</h2>
            <p>
                We group cookies into categories. Not all categories are necessarily used at all times. If we introduce new cookies, we may
                update this policy and/or the consent tool.
            </p>
            <h3 class="font-semibold text-slate-900">3.1 Strictly Necessary Cookies</h3>
            <p>
                These cookies are essential for the Web to function and cannot be switched off without impacting core functionality. They
                are generally used for session management, CSRF protection, and security.
            </p>
            <h3 class="font-semibold text-slate-900">3.2 Functional Cookies</h3>
            <p>
                These cookies enable improved functionality and personalisation, such as remembering consent preferences and certain UI
                settings. They may be set by us or by services we use.
            </p>
            <h3 class="font-semibold text-slate-900">3.3 Analytics / Performance Cookies (Where Enabled)</h3>
            <p>
                These cookies help us understand how visitors use the Web so we can improve performance and user experience. Where required,
                we will ask for your consent before enabling these cookies.
            </p>
            <h3 class="font-semibold text-slate-900">3.4 Third-Party Cookies (Where Applicable)</h3>
            <p>
                Some embedded features (for example, external payment pages, mapping providers, or identity providers) may set cookies on
                their own domains. Those cookies are governed by the third party’s policy, not ours.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">4. Cookies We Commonly Use</h2>
            <p>
                The exact cookies may vary by environment (local, staging, production) and browser. The list below reflects cookies
                commonly used in a Laravel-based web application and the Bwiser Web experience.
            </p>
            <div class="space-y-3">
                <div class="rounded-xl border border-slate-200 bg-white/50 p-4">
                    <p class="font-semibold text-slate-900">Session cookie</p>
                    <p>
                        Purpose: Maintains your authenticated session and keeps you signed in while you use the Web.
                        Example name: <span class="font-mono">fuellevy-session</span> (name may change by environment).
                        Type: strictly necessary.
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white/50 p-4">
                    <p class="font-semibold text-slate-900">CSRF protection cookie</p>
                    <p>
                        Purpose: Helps protect against cross-site request forgery by pairing with a server-side token.
                        Example name: <span class="font-mono">XSRF-TOKEN</span>.
                        Type: strictly necessary.
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white/50 p-4">
                    <p class="font-semibold text-slate-900">Remember-me cookie (if you choose “Remember me”)</p>
                    <p>
                        Purpose: Enables persistent login across browser sessions when you select “Remember me” on sign-in.
                        Example name: <span class="font-mono">remember_web_*</span>.
                        Type: functional/strictly necessary for the remember-me feature.
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white/50 p-4">
                    <p class="font-semibold text-slate-900">Cookie consent preference</p>
                    <p>
                        Purpose: Records whether you have accepted or declined optional cookies (where we provide a consent tool).
                        Example name: <span class="font-mono">bwiser_cookie_consent</span>.
                        Type: functional.
                    </p>
                </div>
            </div>
            <p>
                We avoid storing sensitive information such as passwords in cookies. Passwords are stored as secure hashes server-side.
                Where a cookie contains an identifier, it is generally a random value used to look up session state securely on the server.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">5. Retention and Expiry</h2>
            <p>
                Some cookies (session cookies) expire when you close your browser. Others (like remember-me cookies) may persist longer so
                you remain signed in. We aim to keep cookie lifetimes proportionate to the purpose and to rotate or invalidate cookies
                where security requires it (for example, after password changes, suspicious activity, or logout).
            </p>

            <h2 class="text-lg font-semibold text-slate-900">6. Managing Cookies and Your Choices</h2>
            <p>
                You can manage cookies in several ways:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Using the cookie consent prompt (where available) to accept or decline optional categories.</li>
                <li>Using your browser settings to block or delete cookies.</li>
                <li>Using private browsing mode (note this may still allow some cookies for the duration of your session).</li>
            </ul>
            <p>
                If you block strictly necessary cookies, the Web may not function correctly. This can affect sign-in, voucher creation,
                voucher redemption, repayment actions, and account security features.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">7. Third-Party Services</h2>
            <p>
                Depending on your use of the Platform and which features are enabled, you may be directed to or embedded with third-party
                services such as payment gateways, mapping providers, or identity providers. When you interact with those services, the
                third party may set cookies under their own policies. We encourage you to review the third party’s privacy and cookie
                policies when you leave our domain.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">8. Updates to This Policy</h2>
            <p>
                We may update this Cookie Policy from time to time to reflect operational changes, new features, or legal requirements. We
                will update the “Last updated” date above. Material changes may also be communicated through the Platform.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">9. Legal Basis and Consent (South Africa)</h2>
            <p>
                In South Africa, cookies can constitute personal information when they relate to an identifiable person or can be linked to
                an identifiable person. Where required, we rely on consent for optional cookies (for example, analytics), and we rely on
                contractual necessity and legitimate interests for strictly necessary cookies that support security and core platform
                operations.
            </p>
            <p>
                You can change your mind about optional cookies at any time by clearing cookies in your browser and revisiting the Web, or
                by using any consent controls made available. Please note that withdrawing consent does not affect the lawfulness of
                processing based on consent before it was withdrawn.
            </p>
            <p>
                Where analytics are enabled, we aim to use them to improve performance and reliability rather than to build advertising
                profiles. We prefer privacy-preserving configurations (for example, limiting retention, limiting access, and avoiding
                unnecessary sharing) and we avoid enabling optional categories unless they are genuinely useful to improve the Platform.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">10. Browser Controls (How to Block/Delete Cookies)</h2>
            <p>
                Most browsers allow you to manage cookies through settings. The exact steps differ by browser version, but typically you can:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>View which cookies are stored by a site.</li>
                <li>Delete cookies for a specific site (for example, <span class="font-mono">bwiser.co.za</span>).</li>
                <li>Block third-party cookies.</li>
                <li>Block all cookies (not recommended for the Bwiser Web experience).</li>
            </ul>
            <p>
                Common examples:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li><span class="font-semibold">Chrome</span>: Settings → Privacy and security → Third-party cookies / Site settings.</li>
                <li><span class="font-semibold">Safari (macOS/iOS)</span>: Settings/Preferences → Privacy → Manage Website Data.</li>
                <li><span class="font-semibold">Firefox</span>: Settings → Privacy & Security → Cookies and Site Data.</li>
                <li><span class="font-semibold">Edge</span>: Settings → Cookies and site permissions.</li>
            </ul>
            <p>
                If you delete or block cookies, you may be signed out and may need to sign in again. Certain actions may fail if CSRF/session
                cookies are blocked.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">11. Do Not Track Signals</h2>
            <p>
                Some browsers offer a “Do Not Track” (DNT) preference. There is no universal standard for DNT responses. Bwiser’s response
                to DNT may depend on which analytics tools are enabled and how they are configured. Where we require consent for optional
                cookies, we prefer using explicit consent rather than relying on DNT.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">12. Cookie Glossary (Plain Language)</h2>
            <p>
                Below are common cookie and web storage concepts that can help you understand what you may see in browser developer tools:
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li><span class="font-semibold">First-party cookie</span>: a cookie set by the site you are visiting (for example, <span class="font-mono">bwiser.co.za</span>).</li>
                <li><span class="font-semibold">Third-party cookie</span>: a cookie set by a different domain (often used by embedded services).</li>
                <li><span class="font-semibold">Session cookie</span>: a cookie that expires when the browser session ends.</li>
                <li><span class="font-semibold">Persistent cookie</span>: a cookie that remains until it expires or you delete it.</li>
                <li><span class="font-semibold">Local storage</span>: a browser storage area that persists until cleared (not sent with every request like cookies).</li>
                <li><span class="font-semibold">SameSite</span>: a cookie attribute that reduces cross-site request risks.</li>
                <li><span class="font-semibold">Secure</span>: a cookie attribute that ensures a cookie is only sent over HTTPS.</li>
                <li><span class="font-semibold">HttpOnly</span>: a cookie attribute that prevents JavaScript from reading the cookie (helps reduce XSS risk).</li>
            </ul>
            <p>
                Bwiser configures security-sensitive cookies with protective attributes where supported by the browser and consistent with
                platform requirements. For example, session cookies are intended to be used only for authenticated sessions and are
                protected from being transmitted over insecure channels in production.
            </p>
            <p>
                In some cases, cookies may be scoped to a domain (for example, a parent domain like <span class="font-mono">.bwiser.co.za</span>)
                so that sessions work consistently across subdomains (for example, <span class="font-mono">www</span> vs other subdomains).
                This is a security-sensitive configuration: it must be done carefully to avoid sending cookies to unintended hosts. Bwiser
                aims to scope cookies narrowly and securely, and to use <span class="font-semibold">Secure</span> and
                <span class="font-semibold">SameSite</span> attributes in production to reduce cross-site risks.
            </p>
            <p>
                If your browser blocks third-party cookies or enforces strict tracking protection, some embedded content may not work as
                expected. Common symptoms include being repeatedly logged out, forms failing to submit, or security prompts appearing. When
                this occurs, it typically indicates that the browser is preventing a necessary cookie (session or CSRF) from being stored
                or sent back to the server.
            </p>
            <p>
                If you use shared computers or devices, cookies can keep you signed in. Always sign out after using the Platform on a shared
                device, and consider disabling “Remember me” in those circumstances.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">13. Cookies in Mobile Webviews and External Pages</h2>
            <p>
                Some users access Bwiser links through a mobile app webview or an in-app browser. Webviews typically support cookies, but
                they may behave differently from full browsers (for example, cookie sharing between apps, stricter privacy defaults, or
                limited settings). If you experience sign-in loops, missing sessions, or “Page expired”/CSRF-type errors, the cause is often
                related to blocked cookies or privacy settings in the embedded browser. Where possible, open the link in a full browser and
                ensure cookies are enabled for the site.
            </p>
            <p>
                If you are using VPNs, content blockers, or strict privacy extensions, those tools can also interfere with cookies and with
                third-party scripts required for specific pages. For troubleshooting, temporarily disable blockers for <span class="font-mono">bwiser.co.za</span>
                and try again, or use a different browser profile.
            </p>
            <p>
                When you are redirected to third-party pages (for example, payment gateway pages), those pages may set cookies under their
                own policies. This is common for authentication and payment flows. Bwiser does not control third-party cookie behaviour on
                external domains.
            </p>

            <h2 class="text-lg font-semibold text-slate-900">14. Contact</h2>
            <p>
                If you have questions about cookies or consent, contact <span class="font-semibold">support@bwiser.co.za</span>.
            </p>
            <p>
                If you are reporting a cookie-related problem, include your browser name/version and whether you are using private browsing,
                a content blocker, or an in-app browser. This makes it easier to diagnose session and login issues.
            </p>
            <p>
                For most cookie issues, clearing site data for <span class="font-mono">bwiser.co.za</span> and signing in again resolves the
                problem.
            </p>
            <p>
                If the issue persists, try a different browser, disable strict tracking protection for the site, and confirm that your
                device clock is correct (time skew can break secure sessions). These steps often resolve persistent authentication issues.
            </p>

            <p class="text-xs text-slate-500">
                Note: This Cookie Policy is provided for transparency and operational readiness and does not constitute legal advice.
            </p>
        </div>
    </div>
</section>
@endsection
