<header class="topbar">
    <nav class="navbar top-navbar navbar-expand-md navbar-light">
        <div class="navbar-header hidden-sm-down">
            <a class="navbar-brand" href="{{ asset('') }}">
                <b>
                    <img src="{{ asset('assets/images/putih_phcm.png') }}" width="175" alt="homepage" class="dark-logo" />
                    <img src="{{ asset('assets/images/putih_phcm.png') }}" width="175" alt="homepage"
                        class="light-logo" />
                </b>
                <span>
                </span>
            </a>
        </div>
        <div class="navbar-collapse">
            <ul class="navbar-nav mr-auto mt-md-0">
                <li class="nav-item"> <a class="nav-link nav-toggler hidden-md-up text-muted waves-effect waves-dark"
                        href="javascript:void(0)"><i class="mdi mdi-menu"></i></a> </li>
            </ul>

            <ul class="navbar-nav my-lg-0">
                <!-- ============================================================== -->
                <!-- Profile -->
                <!-- ============================================================== -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-muted waves-effect waves-dark" href=""
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img
                            src="https://ui-avatars.com/api/?name={{ Session::get('nama') }}" alt="user"
                            class="profile-pic" /></a>

                    <div class="dropdown-menu dropdown-menu-right scale-up">
                        <ul class="dropdown-user">
                            <li>
                                <div class="dw-user-box">
                                    <div class="u-img"><img
                                            src="https://ui-avatars.com/api/?name={{ Session::get('nama') }}"
                                            alt="user"></div>
                                    <div class="u-text">
                                        <h4>{{ Str::length(Session::get('nama')) > 15 ? Str::substr(Session::get('nama'), 0, 12) . '...' : Session::get('nama_simrs') }}
                                        </h4>
                                    </div>
                                </div>
                            </li>
                            <li><a href="{{ route('logout') }}"><i class="fa fa-power-off"></i> Logout</a></li>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
</header>
