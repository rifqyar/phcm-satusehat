<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="baseurl" content="{{ asset('') }}">

    <title>{{ config('app.name', 'PHCM - SATU SEHAT') }}
        @if (View::hasSection('title'))
            - @yield('title')
        @endif
    </title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link rel="stylesheet" href="{{ asset('/') }}assets/plugins/datatables/media/css/dataTables.bootstrap4.min.css">
    <!-- CSS -->
    @stack('before-style')
    @include('includes.style')
    @stack('after-style')
    @yield('pages-css')
</head>
<body class="fix-header fix-sidebar card-no-border">
    <!-- ============================================================== -->
    <!-- Preloader - style you can find in spinners.css -->
    <!-- ============================================================== -->
    <div class="preloader">
        <svg class="circular" viewBox="25 25 50 50">
            <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2"
                stroke-miterlimit="10" />
        </svg>
    </div>

    <div id="main-wrapper">
        @include('layouts.header')
        @include('layouts.menu')
        <div class="page-wrapper">
            <div class="container-fluid">
                @yield('content')
            </div>

            <footer class="footer">
                © 2025 PHCM | SatuSehat by EDII
            </footer>
        </div>
    </div>


    @stack('before-script')
    @include('includes.script')
    @stack('after-script')
    @yield('pages-js')

</body>
