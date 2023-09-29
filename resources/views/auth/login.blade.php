<!DOCTYPE html>
<html lang="de" translate="no">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="google" content="notranslate">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', '') :: {{ config('app.name', 'Pfarrplaner') }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
          integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/MaterialDesign-Webfont/6.5.95/css/materialdesignicons.min.css"
          integrity="sha512-Zw6ER2h5+Zjtrej6afEKgS8G5kehmDAHYp9M2xf38MPmpUWX39VrYmdGtCrDQbdLQrTnBVT8/gcNhgS4XPgvEg=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <!-- Theme style -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.6/css/selectize.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.6/css/selectize.bootstrap3.min.css"
          rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/css/bootstrap-datepicker.css"
          rel="stylesheet"/>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.1/css/tempusdominus-bootstrap-4.min.css"/>
    <link rel="stylesheet" type="text/css"
          href="https://cdnjs.cloudflare.com/ajax/libs/jquery-date-range-picker/0.20.0/daterangepicker.min.css"/>
    <link href="{{ asset('css/pfarrplaner.css') }}" rel="stylesheet">

    <link href="{{ mix('/css/app.css') }}" rel="stylesheet">
    @routes()
    @yield('styles', '')

    <!-- favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/img/favicons/favicon-16x16.png">
    <link rel="manifest" href="/img/favicons/site.webmanifest">
    <link rel="mask-icon" href="/img/favicons/safari-pinned-tab.svg" color="#5bbad5">
    <link rel="shortcut icon" href="/img/favicons/favicon.ico">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="msapplication-config" content="/img/favicons/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">

    <style>
        .divider:after,
        .divider:before {
            content: "";
            flex: 1;
            height: 1px;
            background: #eee;
        }

        .h-custom {
            height: calc(100% - 73px);
        }

        @media (max-width: 450px) {
            .h-custom {
                height: 100%;
            }
        }
    </style>
</head>
<body
    class="notranslate hold-transition sidebar-mini sidebar-collapse">

<section style="height: 100vh;">
    <div class="container-fluid h-custom">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col-md-9 col-lg-6 col-xl-5">
                <img src="/img/logo/pfarrplaner.svg" class="img-fluid d-none d-md-inline" alt="Pfarrplaner">
            </div>
            <div class="col-md-8 col-lg-6 col-xl-4 offset-xl-1">
                <h1 class="ps-0 pl-0 ml-0 ms-0 mb-4">{{ config('app.name') }}</h1>
                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf
                    @if(!$demo)
                        <!-- Email input -->
                        <div class="form-outline mb-4">
                            <label class="form-label" for="form3Example3">E-Mailaddresse</label>
                            <input type="email" name="email" class="form-control form-control-lg"
                                   value="{{ old('email') }}"
                                   placeholder="deine@email.de" autofocus/>
                        </div>

                        <!-- Password input -->
                        <div class="form-outline mb-3">
                            <label class="form-label" for="form3Example4">Passwort</label>
                            <input type="password" name="password" class="form-control form-control-lg"
                                   placeholder="Dein Passwort"/>
                        </div>
                    @else
                        <div class="form-outline mb-4">
                            <label class="form-label" for="form3Example3">E-Mailaddresse</label>
                            <select id="users" name="email" class="form-control">
                                    value="{{ $users[0]->email }}">
                                @foreach ($users as $user)
                                    <option value="{{ $user->email }}">{{ $user->fullName(true) }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>

                    @endif

                    <div class="d-flex justify-content-between align-items-center">
                        <!-- Checkbox -->
                        <div class="form-check mb-0">
                            <input class="form-check-input me-2" type="checkbox" value="1" name="remember"/>
                            <label class="form-check-label" for="form2Example3">
                                Angemeldet bleiben
                            </label>
                        </div>
                    </div>

                    <div class="text-right text-lg-start mt-4 pt-2">
                        <input type="submit" class="btn btn-primary btn-lg"
                               style="padding-left: 2.5rem; padding-right: 2.5rem;" value="Anmelden"/>
                    </div>


                </form>
            </div>
        </div>
    </div>
    <div
        class="d-flex flex-column flex-md-row text-center text-md-start justify-content-between py-4 px-4 px-xl-5 bg-primary">
        <!-- Copyright -->
        <div class="text-white mb-3 mb-md-0">
            Copyright © <b>Pfarrplaner</b>. All rights reserved.
        </div>
        <!-- Copyright -->

        <!-- Right -->
        <div>
            <a class="me-2 mr-2 text-white" href="{{ route('what.is') }}">Was ist der Pfarrplaner?</a>
            <a href="https://pfarr.tools" class="text-white">
                <i class="fa fa-wrench"></i> pfarr.tools
            </a>
        </div>
        <!-- Right -->
    </div>
</section>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->

<!-- jQuery -->
<script>
    @auth
        window.Laravel = {!! json_encode([
       'csrfToken' => csrf_token(),
       'apiToken' => Auth::user()->api_token ?? null,
       ]); !!};
    @endauth
        @guest
        window.Laravel = {};
    @endguest

        window.Laravel.loggedIn = {{ json_encode(!Auth::guest()) }};
    window.Laravel.timeout = {{ (config('session.lifetime')*60000)-30000 }};
    window.Laravel.expires = new Date('{!! \Carbon\Carbon::now()->addMinutes(config('session.lifetime'))->toIso8601String() !!}');
    @auth
        window.Laravel.permissions = {!!  json_encode(Auth::user()->getAllPermissions()->pluck('name')) !!};
    @endauth
        @guest
        window.Laravel.permissions = [];
    @endguest

        window.Laravel.assetUrl = '{{ asset('') }}';

    window.setTimeout(function () {
        if (window.Laravel.loggedIn) {
            location.href = '{!! route('logout') !!}';
        } else {
            console.log('Refreshing window to update crsf token.');
            location.reload();
        }
    }, window.Laravel.timeout);
</script>
<!-- other libraries -->
@yield('scripts')
@if(env('MATOMO_SITE') >0)
    <script>
        var _paq = window._paq || [];
        /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
        _paq.push(['trackPageView']);
        _paq.push(['enableLinkTracking']);
        (function () {
            var u = "//matomo.pfarrplaner.de/";
            _paq.push(['setTrackerUrl', u + 'matomo.php']);
            _paq.push(['setSiteId', '{{ env('MATOMO_SITE') }}']);
            var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
            g.type = 'text/javascript';
            g.async = true;
            g.defer = true;
            g.src = u + 'matomo.js';
            s.parentNode.insertBefore(g, s);
        })();
    </script>
@endif
</body>
</html>
