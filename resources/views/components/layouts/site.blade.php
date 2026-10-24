@props(['title' => null, 'description' => null, 'jsonLd' => [], 'mobileBar' => true, 'ogImage' => 'campus-aerial'])
@php
    $pageTitle = $title ? $title.' · '.hillel('name') : hillel('name').' · '.hillel('tagline');
    $metaDescription = $description ?? hillel('meta_description');
    $phone = (string) setting('phone_main');
    $admissionsPhone = (string) setting('phone_admissions');
    $admissionsEmail = (string) setting('email_admissions');
    $nav = [
        ['route' => 'about', 'match' => 'about', 'label' => 'About'],
        ['route' => 'academics', 'match' => 'academics', 'label' => 'Academics'],
        ['route' => 'admissions', 'match' => ['admissions', 'enquire', 'apply*'], 'label' => 'Admissions'],
        ['route' => 'school-life', 'match' => 'school-life', 'label' => 'School life'],
        ['route' => 'news.index', 'match' => ['news.*', 'calendar'], 'label' => 'News & events'],
        ['route' => 'careers.index', 'match' => 'careers.*', 'label' => 'Careers'],
    ];
    $address = hillel('address');
    $organization = [
        '@context' => 'https://schema.org',
        '@type' => 'School',
        'name' => hillel('name'),
        'url' => route('home'),
        'telephone' => '+'.nanp_digits($phone),
        'email' => $admissionsEmail,
        'foundingDate' => (string) hillel('founded'),
        'logo' => asset('img/brand/hillel-crest.png'),
        'image' => asset('img/'.$ogImage.'-1920.webp'),
        'address' => array_filter(['@type' => 'PostalAddress', 'streetAddress' => $address['street'], 'addressLocality' => $address['city'], 'addressRegion' => $address['region'], 'addressCountry' => $address['country']]),
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    @if (hillel('preview'))
        <meta name="robots" content="noindex, nofollow">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ hillel('name') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('img/'.$ogImage.'-1920.webp') }}">
    <meta property="og:locale" content="en_JM">
    <meta name="twitter:card" content="summary_large_image">
    @foreach (array_merge([$organization], $jsonLd) as $ld)
        <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endforeach
</head>
<body class="min-h-screen" x-data="{ menu: false }" @keydown.escape.window="menu = false">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-surface focus:px-4 focus:py-3 focus:shadow-md">Skip to content</a>

    @if (hillel('preview'))
        <div class="bg-accent text-sm text-accent-ink">
            <p class="wrap flex items-start gap-2 py-2.5"><x-icon name="alert" class="mt-0.5 h-4 w-4 flex-none" /><span><strong class="font-bold">Concept preview prepared for Hillel Academy.</strong> This is not the school’s official website. Details shown here must be confirmed by the school.</span></p>
        </div>
    @endif

    {{-- Utility strip: the two things parents look for first. --}}
    <div class="hidden bg-ink-950 text-sm text-white/80 md:block">
        <div class="wrap flex h-10 items-center gap-6">
            <a class="inline-flex items-center gap-1.5 hover:text-white" href="{{ tel_href($admissionsPhone) }}"><x-icon name="phone" class="h-3.5 w-3.5" />Admissions {{ format_phone($admissionsPhone) }}</a>
            <a class="inline-flex items-center gap-1.5 hover:text-white" href="mailto:{{ $admissionsEmail }}"><x-icon name="mail" class="h-3.5 w-3.5" />{{ $admissionsEmail }}</a>
            <span class="ml-auto flex items-center gap-5">
                <a class="hover:text-white" href="{{ route('calendar') }}">Calendar</a>
                <a class="hover:text-white" href="{{ route('contact') }}">Contact</a>
                <a class="inline-flex items-center gap-1.5 font-semibold text-white hover:text-accent" href="{{ route('family.login') }}"><x-icon name="key" class="h-3.5 w-3.5" />Family portal</a>
            </span>
        </div>
    </div>

    <header class="sticky top-0 z-40 border-b border-line bg-bg/95 backdrop-blur supports-[backdrop-filter]:bg-bg/85">
        <div class="wrap flex h-[4.75rem] items-center gap-2 min-[400px]:gap-3">
            <a href="{{ route('home') }}" class="mr-auto flex-none rounded" aria-label="{{ hillel('name') }}, home"><x-logo /></a>

            <nav class="hidden items-center xl:flex" aria-label="Main">
                @foreach ($nav as $item)
                    @php($current = request()->routeIs($item['match']))
                    <a href="{{ route($item['route']) }}" @class(['relative whitespace-nowrap rounded px-3 py-2.5 font-semibold transition-colors', 'text-ink' => $current, 'text-ink-2 hover:text-ink' => ! $current]) @if($current) aria-current="page" @endif>
                        {{ $item['label'] }}
                        @if ($current)<span class="absolute inset-x-3 -bottom-[0.9rem] h-1 rounded-t bg-accent" aria-hidden="true"></span>@endif
                    </a>
                @endforeach
            </nav>

            <button type="button" onclick="toggleTheme()" class="hidden h-11 w-11 flex-none items-center justify-center rounded-lg text-ink-2 hover:bg-surface-2 hover:text-ink sm:inline-flex" aria-label="Switch light or dark theme">
                <x-icon name="moon" class="h-5 w-5 dark-hide" /><x-icon name="sun" class="hidden h-5 w-5 dark-show" />
            </button>
            <a href="{{ route('visit') }}" class="btn btn-outline btn-sm hidden flex-none whitespace-nowrap lg:inline-flex">Book a visit</a>
            <a href="{{ route('apply') }}" class="btn btn-accent btn-sm hidden flex-none whitespace-nowrap sm:inline-flex">Apply<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            <button type="button" class="inline-flex h-11 w-11 flex-none items-center justify-center rounded-lg ring-1 ring-line-strong xl:hidden" @click="menu = !menu" :aria-expanded="menu.toString()" aria-controls="mobile-menu" aria-label="Menu">
                <x-icon name="menu" x-show="!menu" /><x-icon name="x" x-show="menu" x-cloak />
            </button>
        </div>
        {{-- Without JavaScript the menu is a plain list under the header (x-cloak only hides it once JS runs). --}}
        <div id="mobile-menu" class="border-t border-line bg-bg xl:hidden" x-show="menu" x-cloak x-transition.opacity>
            <nav class="wrap flex flex-col py-3" aria-label="Mobile">
                <a href="{{ route('home') }}" class="font-display rounded px-1 py-2.5 text-2xl">Home</a>
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}" class="font-display rounded px-1 py-2.5 text-2xl" @if(request()->routeIs($item['match'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
                <div class="mt-3 grid gap-3 border-t border-line pt-5 sm:grid-cols-2">
                    <a href="{{ route('apply') }}" class="btn btn-accent">Start an application</a>
                    <a href="{{ route('visit') }}" class="btn btn-outline">Book a visit</a>
                    <a href="{{ route('family.login') }}" class="btn btn-outline"><x-icon name="key" class="h-4 w-4" />Family portal</a>
                    <a href="{{ tel_href($admissionsPhone) }}" class="btn btn-outline"><x-icon name="phone" class="h-4 w-4" />{{ format_phone($admissionsPhone) }}</a>
                </div>
                <button type="button" onclick="toggleTheme()" class="mt-3 flex min-h-11 items-center gap-2 rounded px-1 text-left font-semibold text-ink-2"><x-icon name="moon" class="h-5 w-5" />Switch light or dark theme</button>
            </nav>
        </div>
    </header>

    @if (session('status'))
        <div class="wrap mt-6"><p class="panel flex items-center gap-2.5 px-5 py-4 font-medium" role="status"><x-icon name="check-circle" class="h-5 w-5 text-[color:var(--good)]" />{{ session('status') }}</p></div>
    @endif

    <main id="main">
        {{ $slot }}
    </main>

    {{-- With the phone action bar, the footer itself pads below so the navy runs behind the bar. --}}
    <footer class="on-dark ruled mt-24 bg-ink-950 text-white/80 {{ $mobileBar ? 'pb-20 lg:pb-0' : '' }}">
        <div class="h-1.5 bg-accent" aria-hidden="true"></div>
        <div class="wrap grid gap-12 py-16 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.2fr]">
            <div>
                <x-logo tone="light" />
                <p class="mt-5 max-w-xs">{{ hillel('footer_line') }}</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('apply') }}" class="btn btn-accent btn-sm">Apply online</a>
                    <a href="{{ route('visit') }}" class="btn btn-on-dark btn-sm">Book a visit</a>
                </div>
            </div>
            <div>
                <h2 class="margin-note">School</h2>
                <ul class="mt-3">
                    <li><a class="inline-flex min-h-11 items-center hover:text-white lg:min-h-9" href="{{ route('about') }}">About Hillel</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:text-white lg:min-h-9" href="{{ route('academics') }}">Academics</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:text-white lg:min-h-9" href="{{ route('school-life') }}">School life</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:text-white lg:min-h-9" href="{{ route('news.index') }}">News</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:text-white lg:min-h-9" href="{{ route('calendar') }}">Calendar</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:text-white lg:min-h-9" href="{{ route('careers.index') }}">Careers</a></li>
                </ul>
            </div>
            <div>
                <h2 class="margin-note">Admissions</h2>
                <ul class="mt-3">
                    <li><a class="inline-flex min-h-11 items-center hover:text-white lg:min-h-9" href="{{ route('admissions') }}">How to apply</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:text-white lg:min-h-9" href="{{ route('admissions') }}#fees">Fees {{ setting('fees_year') }}</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:text-white lg:min-h-9" href="{{ route('enquire') }}">Ask a question</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:text-white lg:min-h-9" href="{{ route('visit') }}">Campus visits</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:text-white lg:min-h-9" href="{{ route('family.login') }}">Family portal</a></li>
                </ul>
            </div>
            <div>
                <h2 class="margin-note">Find us</h2>
                <address class="mt-5 not-italic leading-relaxed">{{ $address['street'] }}<br>{{ $address['city'] }}, {{ $address['country_name'] }}</address>
                <a class="mt-1 inline-flex min-h-11 items-center gap-1.5 hover:text-white" href="{{ hillel('map_url') }}" rel="noopener" target="_blank">Directions<x-icon name="external" class="h-3.5 w-3.5" /></a>
                <p class="mt-2"><a class="inline-flex min-h-11 items-center gap-2 hover:text-white" href="{{ tel_href($phone) }}"><x-icon name="phone" class="h-4 w-4" />Main office {{ format_phone($phone) }}</a></p>
                <p><a class="inline-flex min-h-11 items-center gap-2 hover:text-white" href="{{ tel_href($admissionsPhone) }}"><x-icon name="phone" class="h-4 w-4" />Admissions {{ format_phone($admissionsPhone) }}</a></p>
                <p><a class="inline-flex min-h-11 items-center gap-2 break-all hover:text-white" href="mailto:{{ $admissionsEmail }}"><x-icon name="mail" class="h-4 w-4 flex-none" />{{ $admissionsEmail }}</a></p>
                @if ($social = array_filter((array) hillel('social')))
                    <p class="mt-3 flex flex-wrap gap-x-5 gap-y-1">
                        @foreach ($social as $network => $url)<a class="inline-flex min-h-11 items-center hover:text-white" href="{{ $url }}" rel="noopener" target="_blank">{{ ucfirst($network) }}</a>@endforeach
                    </p>
                @endif
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="wrap flex flex-col gap-3 py-6 text-sm text-white/65 md:flex-row md:items-center md:justify-between">
                <p>© {{ date('Y') }} {{ hillel('name') }}. {{ hillel('legal_line') }}</p>
                <p class="flex flex-wrap gap-x-5 gap-y-2">
                    <a class="hover:text-white" href="{{ route('privacy') }}">Privacy</a>
                    <a class="hover:text-white" href="{{ route('credits') }}">Photo credits</a>
                    <a class="hover:text-white" href="{{ route('login') }}">Staff sign in</a>
                </p>
            </div>
        </div>
    </footer>

    @if ($mobileBar)
        {{-- Thumb-reach actions on phones: call admissions, book a visit, apply --}}
        <nav aria-label="Quick actions" class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface/95 p-3 shadow-[0_-8px_24px_-16px_rgb(20_33_61/0.4)] backdrop-blur lg:hidden">
            <div class="flex gap-2">
                <a href="{{ tel_href($admissionsPhone) }}" class="btn btn-outline flex-none px-3.5" aria-label="Call Admissions {{ format_phone($admissionsPhone) }}"><x-icon name="phone" /></a>
                <a href="{{ route('visit') }}" class="btn btn-outline flex-1 whitespace-nowrap px-2">Book a visit</a>
                <a href="{{ route('apply') }}" class="btn btn-accent flex-1 whitespace-nowrap px-2">Apply</a>
            </div>
        </nav>
    @endif
</body>
</html>
