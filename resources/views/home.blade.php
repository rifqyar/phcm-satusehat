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
                            <h4 class="card-title">Trend Pengiriman SatuSehat (7 Hari Terakhir)</h4>
                            <div style="height: 300px;">
                                <canvas id="chartPerTanggal"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Distribusi per Endpoint</h4>
                            <div style="height: 300px;">
                                <canvas id="chartPerEndpoint"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Volume Pengiriman: Rawat Jalan vs Rawat Inap</h4>
                            <div style="height: 300px;">
                                <canvas id="chartRJRI"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Total Proporsi Layanan</h4>
                            <div style="height: 300px;">
                                <canvas id="chartProporsiLayanan"></canvas>
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

            // --- 1. SETTING BAR CHART (PER TANGGAL) ---
            const ctxTanggal = document.getElementById('chartPerTanggal').getContext('2d');
            const chartTanggal = new Chart(ctxTanggal, {
                type: 'bar', // Bisa diganti 'line' kalau lebih suka garis
                data: {
                    labels: ['08 Apr', '09 Apr', '10 Apr', '11 Apr', '12 Apr', '13 Apr', '14 Apr'],
                    datasets: [{
                            label: 'Total Berhasil',
                            data: [120, 150, 180, 90, 200, 250, 210],
                            backgroundColor: 'rgba(38, 198, 218, 0.8)', // Warna khas MaterialPro (Cyan)
                            borderWidth: 0,
                            borderRadius: 4 // Bikin ujung bar agak melengkung (khas UI modern)
                        },
                        {
                            label: 'Total Gagal',
                            data: [5, 12, 4, 1, 15, 8, 3],
                            backgroundColor: 'rgba(252, 75, 108, 0.8)', // Merah danger MaterialPro
                            borderWidth: 0,
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // --- 2. SETTING DONUT CHART (PER ENDPOINT) ---
            const ctxEndpoint = document.getElementById('chartPerEndpoint').getContext('2d');
            const chartEndpoint = new Chart(ctxEndpoint, {
                type: 'doughnut',
                data: {
                    labels: ['Encounter', 'Observation', 'DiagnosticReport', 'Patient'],
                    datasets: [{
                        data: [450, 850, 200, 150], // Mock data aggregate
                        backgroundColor: [
                            '#1e88e5', // Biru
                            '#26c6da', // Cyan
                            '#ffb22b', // Kuning
                            '#fc4b6c' // Merah
                        ],
                        borderWidth: 2,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%', // Bikin lubang donut-nya lebih besar/kecil
                    plugins: {
                        legend: {
                            position: 'bottom' // Biar legend gak makan tempat di samping
                        }
                    }
                }
            });
        });

        document.addEventListener("DOMContentLoaded", function() {

            // --- 1. STACKED BAR CHART (RJ vs RI) ---
            const ctxRJRI = document.getElementById('chartRJRI').getContext('2d');
            new Chart(ctxRJRI, {
                type: 'bar',
                data: {
                    labels: ['08 Apr', '09 Apr', '10 Apr', '11 Apr', '12 Apr', '13 Apr', '14 Apr'],
                    datasets: [{
                            label: 'Rawat Jalan',
                            data: [80, 100, 120, 60, 140, 180, 150],
                            backgroundColor: '#1e88e5', // Biru
                        },
                        {
                            label: 'Rawat Inap',
                            data: [40, 50, 60, 30, 60, 70, 60],
                            backgroundColor: '#7460ee', // Ungu (khas MaterialPro)
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
                            stacked: true,
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });

            // --- 2. DONUT CHART (PROPORSI LAYANAN) ---
            const ctxProporsi = document.getElementById('chartProporsiLayanan').getContext('2d');
            new Chart(ctxProporsi, {
                type: 'doughnut',
                data: {
                    labels: ['Rawat Jalan', 'Rawat Inap'],
                    datasets: [{
                        data: [830, 370], // Total akumulasi
                        backgroundColor: ['#1e88e5', '#7460ee'],
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%'
                }
            });
        });
    </script>
@endpush
