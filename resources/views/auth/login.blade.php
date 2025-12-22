<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="baseurl" content="{{ asset('') }}">

    <title>{{ config('app.name', 'PHCM - SATU SEHAT') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- CSS -->
    @include('includes.style')
</head>

<body>
    <div class="preloader">
        <svg class="circular" viewBox="25 25 50 50">
            <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2"
                stroke-miterlimit="10" />
        </svg>
    </div>

    <section id="wrapper" class="login-register login-sidebar"
        style="background-image:url({{ asset('assets/images/background/bg1.jpg') }});">
        <div class="login-box card">
            <div class="card-body">
                <a href="javascript:void(0)" class="text-left db">
                    <img src="{{ asset('assets/images/warna_phcm.png') }}" alt="Home" style="width: 50%" />
                </a>
                <h3 class="box-title m-b-20 m-t-20">Login to Your Account</h3>
                <form class="floating-labels m-t-40" id="loginform" method="POST" action="{{ route('do.login') }}">
                    @csrf
                    <div class="form-group m-b-40">
                        <select name="id_unit" id="id_unit" class="form-control" required>
                            <option selected disabled class="text-center">--- Pilih Unit ---</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->IDUNIT }}">{{ $site->NAMA_UNIT }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group m-b-40">
                        <input class="form-control" type="text" required name="nipp" id="nipp">
                        <span class="bar"></span>
                        <label for="nipp">NIPP</label>
                    </div>
                    <div class="form-group m-b-40">
                        <input class="form-control" type="password" required name="password" id="password">
                        <span class="bar"></span>
                        <label for="password">Password</label>
                    </div>
                    <div class="form-group">
                        <div class="d-flex no-block align-items-center">
                            <div class="checkbox checkbox-primary p-t-0">
                                <input id="checkbox-signup" type="checkbox" name="bypass">
                                <label for="checkbox-signup"> Bypass Centra </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group text-center m-t-20">
                        <div class="col-xs-12">
                            <button class="btn btn-info btn-lg btn-block text-uppercase waves-effect waves-light"
                                type="submit">Log In</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        </div>
    </section>

    @include('includes.script')
</body>

</html>
