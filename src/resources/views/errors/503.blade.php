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

    <link href="{{ mix('/css/app.css') }}" rel="stylesheet">
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
        .h-custom {
            height: calc(100% - 73px);
        }
    </style>
</head>
<body
    class="notranslate hold-transition layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse bg-body-tertiary h-100">
<section style="height: 100vh;">
    <div class="container-fluid h-custom">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col-md-9 col-lg-6 col-xl-5">
                <img src="/img/logo/pfarrplaner.svg" class="img-fluid d-none d-md-inline" alt="Pfarrplaner">
            </div>
            <div class="col-md-8 col-lg-6 col-xl-4 offset-xl-1">
                <h1 class="ps-0 pl-0 ms-0 ms-0 mb-4">{{ config('app.name') }} <span class="mdi mdi-spin mdi-cog"></span></h1>
                <p>Der Server befindet sich momentan im Wartungsmodus. Wir arbeiten daran, schnellstmöglich alle Dienste
                    wieder zur Verfügung stellen zu können.</p>
                @if(config('app.administrator'))
                    <p>Bei Fragen wenden Sie sich gerne <a href="mailto:{{ config('app.administrator') }}">an den Administrator des Servers</a>.</p>
                @endif
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
            <a class="me-2 me-2 text-white" :href="route('what.is')">Was ist der Pfarrplaner?</a>
            <a href="https://pfarr.tools" class="text-white">
                <i class="fa fa-wrench"></i> pfarr.tools
            </a>
        </div>
        <!-- Right -->
    </div>
</section>
</body>
</html>
