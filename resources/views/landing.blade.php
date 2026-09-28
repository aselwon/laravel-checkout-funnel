<x-layout>
<section class="container hero">
    <div class="hero-copy">
        <div class="eyebrow"><span class="status-dot"></span> YOUR NEXT CHAPTER STARTS HERE</div>
        @if($assignment->variant === 'A')
            <h1>Great products.<br>Now, let’s build<br><em>great growth.</em></h1>
            <p class="lede">You built the store. We’ll help with what comes next. A practical roadmap to turn more visitors into your next customers.</p>
        @else
            <h1>Less guesswork.<br>More customers.<br><em>Your next move.</em></h1>
            <p class="lede">Make every visit count. Find the gaps in your store’s journey and build a clearer path from first click to checkout.</p>
        @endif
        <a href="#start" class="button primary">Find my next step <span>↗</span></a>
        <div class="hero-note"><span>✓ Free to start</span><span>✓ No card needed</span><span>✓ Made for sellers</span></div>
    </div>
    <div class="hero-art" aria-label="Preview of your seller growth workspace">
        <div class="orbit orbit-one"></div><div class="orbit orbit-two"></div>
        <div class="art-star">✳</div>
        <div class="workspace-preview">
            <div class="preview-top"><span class="mini-icon">↗</span><span>YOUR GROWTH WORKSPACE</span><span class="dots">•••</span></div>
            <div class="preview-greeting">A clearer path forward.</div>
            <p>One focused step at a time.</p>
            <div class="chart-preview"><div class="chart-label"><span>A little progress, every day</span><span class="pill">PREVIEW</span></div><svg viewBox="0 0 340 130" role="img" aria-label="Illustrative upward growth curve"><defs><linearGradient id="fade" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#d9fa9e" stop-opacity=".65"/><stop offset="100%" stop-color="#d9fa9e" stop-opacity="0"/></linearGradient></defs><path d="M5 110 C55 110 45 77 95 83 S150 108 175 65 S220 90 250 43 S290 70 333 15 L333 125 L5 125Z" fill="url(#fade)"/><path d="M5 110 C55 110 45 77 95 83 S150 108 175 65 S220 90 250 43 S290 70 333 15" fill="none" stroke="#d9fa9e" stroke-width="4" stroke-linecap="round"/><circle cx="333" cy="15" r="5" fill="#d9fa9e"/></svg></div>
            <div class="preview-task"><span class="check-square">✓</span><div><strong>Give your store a clear promise</strong><small>Your first step toward a better funnel</small></div></div>
            <div class="preview-task muted"><span class="empty-square"></span><div><strong>Make your product the hero</strong><small>Next on your growth checklist</small></div></div>
        </div>
        <div class="floating-note"><span>↗</span><div><strong>Your store. More potential.</strong><small>Let’s turn the next step into progress.</small></div></div>
        <span class="art-caption">A PLAN YOU CAN ACTUALLY ACT ON.</span>
    </div>
</section>
<div class="benefit-strip"><div class="container"><span>BUILT FOR YOUR NEXT STAGE</span><strong>Independent stores</strong><i>✳</i><strong>Ambitious makers</strong><i>✳</i><strong>Everyday entrepreneurs</strong></div></div>
<section class="container section" id="start">
    <div class="section-heading"><div><div class="eyebrow">01 / CHOOSE YOUR START</div><h2>A small step.<br>A smarter store.</h2></div><p>No complicated playbooks. No endless to-do lists.<br>Just the right tools for where you are now.</p></div>
    <div class="path-grid">
        <article class="path-card free-card">
            <div class="card-top"><span class="path-icon">◎</span><span class="pill">BUILD YOUR FOUNDATION</span></div>
            <h3>The Fresh Start</h3><p>Get unstuck with a simple, actionable checklist for your store’s next chapter.</p>
            <div class="price">Free <span>always a good place to start</span></div>
            <ul class="feature-list"><li>A 5-step store growth checklist</li><li>Your own progress workspace</li><li>A clearer idea of what to do next</li></ul>
            <form method="POST" action="{{ route('signup') }}" x-data="{ busy: false }" @submit="busy = true">
                @csrf
                <label for="email">Your email address</label>
                <div class="input-action"><input id="email" name="email" type="email" autocomplete="email" placeholder="you@yourstore.com" value="{{ old('email') }}" required maxlength="254"><button class="button primary" :disabled="busy"><span x-text="busy ? 'Starting…' : 'Start free'"></span> ↗</button></div>
                <small class="form-note">Instant access in this browser. No account or card needed.</small>
            </form>
        </article>
        @if(config('funnel.paid_enabled'))
        <article class="path-card paid-card">
            <div class="card-top"><span class="path-icon">✳</span><span class="pill">READY FOR THE NEXT LEVEL</span></div>
            <h3>The Growth Kit</h3><p>Turn your foundation into a focused experiment. Practical tools for a stronger funnel.</p>
            <div class="price">$29 <span>USD · one payment. Yours to use.</span></div>
            <ul class="feature-list"><li>Everything in The Fresh Start</li><li>Conversion copy and offer templates</li><li>A 7-day experiment plan + measurement guide</li></ul>
            <a href="#email" class="button light">Start free, then unlock the kit <span>↗</span></a>
            <small class="form-note">{{ config('funnel.mock_stripe') ? 'Demo mode · explore checkout without a payment.' : 'Secure checkout with Stripe · test mode.' }}</small>
        </article>
        @else
        <article class="path-card paid-card"><span class="path-icon">✳</span><h3>Start with the essentials.</h3><p>The free checklist is open. Paid upgrades are currently paused — your next step can still start today.</p><a class="button light" href="#email">Get my free checklist ↗</a></article>
        @endif
    </div>
</section>
<section class="container section how-section" id="how-it-works">
    <div class="section-heading"><div><div class="eyebrow">02 / LESS FRICTION, MORE FOCUS</div><h2>You don’t need another tool.<br>You need a next step.</h2></div><span class="big-asterisk">✳</span></div>
    <div class="steps-grid"><article><span>01</span><h3>Find your starting point</h3><p>Leave your email and open a workspace designed around the basics that matter.</p></article><article><span>02</span><h3>Make one useful change</h3><p>Work through five focused steps. Save your progress and keep the momentum.</p></article><article><span>03</span><h3>Build on what works</h3><p>Ready for more? Use the Growth Kit to plan, run, and measure your next experiment.</p></article></div>
</section>
<section class="container faq-section" x-data="{ open: false }"><button class="faq-toggle" @click="open = !open" :aria-expanded="open" aria-controls="faq-answer">Do I need to connect my store?<span x-text="open ? '−' : '+'">+</span></button><p id="faq-answer" x-show="open" x-cloak>No. SellerBoost is a guided workspace, so you can use it with any store platform. Your progress is saved for this browser. This demo does not email access links or create seller accounts.</p></section>
</x-layout>
