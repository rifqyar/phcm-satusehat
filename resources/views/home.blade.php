@extends('layouts.app')

@push('before-style')
    <!-- Existing CSS links -->
    <!-- ... -->
    <link href="{{ asset('assets/plugins/chartist-js/dist/chartist.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/chartist-js/dist/chartist-init.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.css') }}"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
        integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        /* Existing styles */
        @media (max-width: 640px) {
            .ct-label.ct-horizontal.ct-end {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="row page-titles">
        <!-- Existing content -->
        <div class="col-md-5 col-8 align-self-center">
            <h3 class="text-themecolor">Dashboard</h3>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </div>
        <div class="col-md-7 col-4 align-self-center">
            <div class="d-flex m-t-10 justify-content-end">
                <h6>Selamat Datang <p><b>{{ Session::get('nama') }}</b></p>
                </h6>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-4">
        <h3 class="card-title">Transaksi Satu Sehat</h3>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-lg-8 col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Trend Kunjungan vs Pengiriman (7 Hari Terakhir)</h4>

                            <div id="loadingTrendUtama" class="text-center py-5">
                                <div class="spinner-border text-info" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <h6 class="mt-2 text-muted">Menarik data dari server...</h6>
                            </div>

                            <div id="containerTrendUtama" style="height: 350px; display: none;">
                                <canvas id="chartTrendUtama"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Distribusi Endpoint</h4>
                            <div id="loadingEndpoint" class="text-center py-5">
                                <div class="spinner-border text-info" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <h6 class="mt-2 text-muted">Menarik data dari server...</h6>
                            </div>

                            <div id="containerEndpoint" style="height: 350px; display: none;">
                                <canvas id="chartEndpoint"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Volume Pengiriman: Rawat Jalan vs Rawat Inap</h4>

                            <div id="loadingVolumeRjRi" class="text-center py-5">
                                <div class="spinner-border text-info" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <h6 class="mt-2 text-muted">Menarik data dari server...</h6>
                            </div>

                            <div id="containerVolumeRjRi" style="height: 350px; display: none;">
                                <canvas id="chartVolumeRjRi"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Proporsi Layanan</h4>

                            <div id="loadingProporsiRjRi" class="text-center py-5">
                                <div class="spinner-border text-info" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <h6 class="mt-2 text-muted">Menarik data dari server...</h6>
                            </div>

                            <div id="containerProporsiRjRi" style="height: 350px; display: none;">
                                <canvas id="chartProporsiRjRi"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('after-script')
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            function toggleLoading(chartName, isLoading) {
                const loadingEl = document.getElementById('loading' + chartName);
                const containerEl = document.getElementById('container' + chartName);

                if (loadingEl && containerEl) {
                    if (isLoading) {
                        loadingEl.style.display = 'block';
                        containerEl.style.display = 'none';
                    } else {
                        loadingEl.style.display = 'none';
                        containerEl.style.display = 'block';
                    }
                }
            }

            function showEmptyState(chartName, message = "Belum ada data transaksi") {
                const loadingEl = document.getElementById('loading' + chartName);
                const containerEl = document.getElementById('container' + chartName);

                if (loadingEl && containerEl) {
                    // Sembunyikan kanvas
                    containerEl.style.display = 'none';
                    // Tampilkan div loading, tapi isinya kita ganti jadi icon folder kosong
                    loadingEl.style.display = 'block';
                    loadingEl.innerHTML = `
                        <div class="text-muted py-4">
                            <i class="fas fa-folder-open fa-3x mb-3" style="color: #d2d6de;"></i>
                            <h6 class="text-muted">${message}</h6>
                        </div>
                    `;
                }
            }

            fetch('/dashboard-data')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(res => {
                    // --- 1. CEK DATA TREND UTAMA ---
                    // Hitung total sukses & gagal
                    const sumSukses = res.utama.sukses.reduce((a, b) => a + b, 0);
                    const sumGagal = res.utama.gagal.reduce((a, b) => a + b, 0);

                    if (sumSukses === 0 && sumGagal === 0) {
                        showEmptyState('TrendUtama', 'Tidak ada data pengiriman 7 hari terakhir');
                    } else {
                        toggleLoading('TrendUtama', false);
                        renderChartUtama(res.utama);
                    }

                    // --- 2. CEK DATA ENDPOINT ---
                    if (!res.endpoint.data || res.endpoint.data.length === 0) {
                        showEmptyState('Endpoint', 'Belum ada distribusi endpoint');
                    } else {
                        toggleLoading('Endpoint', false);
                        renderChartEndpoint(res.endpoint);
                    }

                    // --- 3. CEK DATA RJ & RI ---
                    if (res.rj_ri.total_rj === 0 && res.rj_ri.total_ri === 0) {
                        showEmptyState('VolumeRjRi', 'Tidak ada data rawat jalan & inap');
                        showEmptyState('ProporsiRjRi', 'Tidak ada proporsi layanan');
                    } else {
                        toggleLoading('VolumeRjRi', false);
                        toggleLoading('ProporsiRjRi', false);
                        renderChartRjRi(res.rj_ri);
                    }
                })
                .catch(error => {
                    console.error('Error ngambil data chart:', error);

                    const errorMessage =
                        '<div class="text-danger py-4"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>Gagal memuat data dari server.</div>';

                    document.getElementById('loadingTrendUtama').innerHTML = errorMessage;
                    document.getElementById('loadingEndpoint').innerHTML = errorMessage;
                    document.getElementById('loadingVolumeRjRi').innerHTML = errorMessage;
                    document.getElementById('loadingProporsiRjRi').innerHTML = errorMessage;
                });

            function renderChartUtama(data) {
                new Chart(document.getElementById('chartTrendUtama').getContext('2d'), {
                    type: 'bar', // Bisa ganti 'line' kalau mau dibikin gabungan bar & line
                    data: {
                        labels: data.labels,
                        datasets: [{
                                label: 'Total Kunjungan',
                                data: data.kunjungan,
                                backgroundColor: '#ffb22b', // Kuning/Warning
                                type: 'line', // Kunjungan dibikin garis biar kontras sama Bar
                                borderColor: '#ffb22b',
                                fill: false,
                                tension: 0.3
                            },
                            {
                                label: 'Kiriman Berhasil',
                                data: data.sukses,
                                backgroundColor: '#1e88e5' // Biru
                            },
                            {
                                label: 'Kiriman Gagal',
                                data: data.gagal,
                                backgroundColor: '#fc4b6c' // Merah
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }

            function renderChartEndpoint(data) {
                // Generate warna dinamis sejumlah endpoint
                const colors = ['#1e88e5', '#26c6da', '#7460ee', '#ffb22b', '#fc4b6c', '#00897b', '#8e24aa',
                    '#e53935', '#3949ab', '#43a047', '#fb8c00', '#d81b60', '#1e88e5', '#8d6e63', '#546e7a',
                    '#c0ca33', '#f4511e', '#00acc1', '#6d4c41'
                ];

                new Chart(document.getElementById('chartEndpoint').getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.data,
                            backgroundColor: colors.slice(0, data.labels.length)
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                });
            }

            function renderChartRjRi(data) {
                // 1. Bar Chart Stacked RJ vs RI
                new Chart(document.getElementById('chartVolumeRjRi').getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                                label: 'Rawat Jalan',
                                data: data.rj,
                                backgroundColor: '#1e88e5'
                            },
                            {
                                label: 'Rawat Inap',
                                data: data.ri,
                                backgroundColor: '#7460ee'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                stacked: true
                            },
                            y: {
                                stacked: true
                            }
                        }
                    }
                });

                // 2. Donut Chart Proporsi Total RJ vs RI
                new Chart(document.getElementById('chartProporsiRjRi').getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Rawat Jalan', 'Rawat Inap'],
                        datasets: [{
                            data: [data.total_rj, data.total_ri],
                            backgroundColor: ['#1e88e5', '#7460ee']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        });
    </script>
@endpush
