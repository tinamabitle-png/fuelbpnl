<div class="{{ $class ?? 'flex flex-wrap items-center gap-3 text-xs text-slate-600' }}">
    <a href="{{ route('legal.terms') }}" class="hover:text-blue-700">Terms & Conditions</a>
    <span aria-hidden="true">•</span>
    <a href="{{ route('legal.cookies') }}" class="hover:text-blue-700">Cookie Policy</a>
    <span aria-hidden="true">•</span>
    <a href="{{ route('legal.aml') }}" class="hover:text-blue-700">AML & KYC Policy</a>
    <span aria-hidden="true">•</span>
    <a href="{{ route('legal.poppia') }}" class="hover:text-blue-700">POPIA Notice</a>
    <span aria-hidden="true">•</span>
    <a href="{{ route('legal.paia') }}" class="hover:text-blue-700">PAIA Manual</a>
    <span aria-hidden="true">•</span>
    <a href="{{ route('legal.security') }}" class="hover:text-blue-700">PCI DSS & ISO</a>
    <span aria-hidden="true">•</span>
    <a href="{{ route('sitemap') }}" class="hover:text-blue-700">Sitemap</a>
</div>
