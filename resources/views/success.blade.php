<x-layout title="Checkout status">
<section class="container section narrow"><div class="panel checkout-panel">
@if($purchase->paid_at)
    <span class="success-icon">✓</span><div class="eyebrow">YOU’RE ALL SET</div><h1 class="page-title">Your next chapter<br><em>is unlocked.</em></h1><p>The Growth Kit is ready. Start with one idea, give it a week, and see what you learn.</p><a href="{{ route('growth') }}" class="button primary full">Open my Growth Kit ↗</a>
@else
    <span class="path-icon">◷</span><div class="eyebrow">WAITING FOR CONFIRMATION</div><h1 class="page-title">Almost<br><em>ready.</em></h1><p>We haven’t received payment confirmation yet. This page alone never unlocks paid access. Refresh after Stripe confirms your payment.</p><a href="{{ route('checkout.success', $purchase) }}" class="button primary full">Check payment status ↻</a>
@endif
<a href="{{ route('onboarding') }}" class="back-link">Return to my workspace</a></div></section>
</x-layout>
