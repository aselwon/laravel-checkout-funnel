<x-layout title="Your workspace">
<section class="container section workspace">
    <div class="eyebrow">YOUR WORKSPACE / THE FRESH START</div>
    <div class="section-heading"><div><h1 class="page-title">Small steps.<br><em>Real momentum.</em></h1><p class="lede">Welcome, {{ $lead->email }}. Your next move starts here.</p></div><span class="pill">{{ $lead->paid_at ? 'GROWTH KIT UNLOCKED' : 'FREE WORKSPACE' }}</span></div>
    @if(request('checkout') === 'cancelled')<div class="notice">Checkout cancelled. Your checklist is still right here.</div>@endif
    <div class="workspace-grid">
        <form action="{{ route('checklist') }}" method="POST" class="panel checklist" x-data="{ checked: @js($lead->checklist ?? []) }">
            @csrf
            <div class="card-top"><h2>Your growth checklist</h2><span class="pill"><span x-text="checked.length">{{ count($lead->checklist ?? []) }}</span> / 5</span></div>
            <div class="progress-track"><div :style="'width: ' + checked.length * 20 + '%'" style="width: {{ count($lead->checklist ?? []) * 20 }}%"></div></div>
            @foreach($checklist as $key => [$heading, $description])
                <label class="checklist-row"><input type="checkbox" name="completed[]" value="{{ $key }}" x-model="checked" @checked(in_array($key, $lead->checklist ?? []))><span><strong>{{ $heading }}</strong><small>{{ $description }}</small></span></label>
            @endforeach
            <button class="button primary">Save my progress <span>↗</span></button>
            <p class="form-note">Progress is saved when you press the button. Access stays in this browser.</p>
        </form>
        <aside class="panel upgrade-panel"><span class="path-icon">✳</span>
            @if($lead->paid_at)<span class="eyebrow">YOUR NEXT CHAPTER</span><h2>Your Growth Kit is ready.</h2><p>Your templates, experiment plan, and measurement guide are unlocked.</p><a href="{{ route('growth') }}" class="button light">Open my Growth Kit ↗</a>
            @elseif(config('funnel.paid_enabled'))<span class="eyebrow">WHEN YOU’RE READY</span><h2>Give your next idea a plan.</h2><p>Go from checklist to experiment with conversion templates and a 7-day action plan.</p><div class="price">$29 <span>USD · one-time</span></div><form method="POST" action="{{ route('checkout.store') }}" x-data="{ busy: false }" @submit="busy = true">@csrf<button class="button light" :disabled="busy"><span x-text="busy ? 'Opening checkout…' : 'Unlock the Growth Kit'"></span> ↗</button></form><small class="form-note">{{ config('funnel.mock_stripe') ? 'Mock checkout. No card, no charge.' : 'Stripe test checkout. No real charge.' }}</small>
            @else<h2>You’re on the right path.</h2><p>Paid upgrades are paused. Keep making progress with your free checklist.</p>@endif
        </aside>
    </div>
</section>
</x-layout>
