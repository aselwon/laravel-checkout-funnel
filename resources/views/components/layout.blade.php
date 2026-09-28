@props(['title' => 'A little structure. A lot more growth.'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Turn more store visitors into customers with a focused seller checklist and a practical Growth Kit.">
    <title>{{ $title }} · SellerBoost</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="site-header container">
        <a href="{{ route('home') }}" class="brand" aria-label="SellerBoost home"><span class="brand-icon">↗</span> seller<span>boost</span><span class="brand-dot">®</span></a>
        <nav aria-label="Main navigation">
            <a href="{{ route('home') }}#how-it-works" class="nav-secondary">How it works</a>
            <a href="{{ route('onboarding') }}" class="nav-secondary">My workspace</a>
            @auth
                @if(auth()->user()->is_admin)<a href="{{ route('admin.dashboard') }}" class="nav-pill">Dashboard ↗</a>@endif
            @else
                <a href="{{ route('login') }}" class="nav-pill">Admin <span>↗</span></a>
            @endauth
        </nav>
    </header>
    <main>
        @if(session('status'))<div class="container"><div class="notice" role="status">{{ session('status') }}</div></div>@endif
        @if($errors->any())<div class="container"><div class="notice error" role="alert"><strong>Let’s fix that.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
        {{ $slot }}
    </main>
    <footer class="container footer"><a href="{{ route('home') }}" class="brand small">↗ sellerboost</a><span>Small steps. Stronger stores.</span><span>Built for independent sellers · {{ date('Y') }}</span></footer>
</body>
</html>
