@extends('layouts.app')

@push('before-style')
    <link href="{{ asset('assets/plugins/chartist-js/dist/chartist.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/chartist-js/dist/chartist-init.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.css') }}"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/icons/font-awesome/css/fontawesome-all.min.css') }}" />
    <link href="{{ asset('assets/plugins/bootstrap-material-datetimepicker/css/bootstrap-material-datetimepicker.css') }}"
        rel="stylesheet">

    <style>
        .table-responsive {
            overflow: visible;
        }

        .card-mapping {
            border-radius: 15px;
        }

        .card-mapping:hover {
            box-shadow: 0 14px 18px rgba(0, 0, 0, 0.473);
            transform: translateY(-5px);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .card-stat {
            transition: all 0.3s ease;
        }

        .card-stat:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .border-left-secondary {
            border-left: 4px solid #6c757d !important;
        }

        .border-left-success {
            border-left: 4px solid #28a745 !important;
        }

        .border-left-warning {
            border-left: 4px solid #ffc107 !important;
        }
    </style>
@endpush

@section('content')
    <div class="row page-titles">
        <div class="col-md-5 col-8 align-self-center">
            <h3 class="text-themecolor">Dashboard</h3>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Laporan Pemeriksaan</li>
            </ol>
        </div>
        <div class="col-md-7 col-4 align-self-center">
            <div class="d-flex m-t-10 justify-content-end">
                <h6>Selamat Datang <b>{{ Session::get('user') }}</b></h6>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Data Laporan Pemeriksaan</h4>
            <form action="javascript:void(0)" id="search-data" class="m-t-40">
                <input type="hidden" name="search" value="{{ request('search') }}">

                <div class="row justify-content-center">
                    <!-- Card summary -->
                    <div class="col-4">
                        <div class="card card-inverse card-primary card-mapping" onclick="search('all')">
                            <div class="card-body">
                                <div class="row align-items-center ml-1">
                                    <i class="fas fa-file-medical text-white" style="font-size: 48px"></i>
                                    <div class="ml-3">
                                        <span data-count="all" class="text-white" style="font-size: 24px">
                                            0
                                        </span>
                                        <h4 class="text-white">Semua Laporan Pemeriksaan<br></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="card card-inverse card-success card-mapping" onclick="search('sent')">
                            <div class="card-body">
                                <div class="row align-items-center ml-1">
                                    <i class="fas fa-check-circle text-white" style="font-size: 48px"></i>
                                    <div class="ml-3">
                                        <span data-count="sent" class="text-white" style="font-size: 24px">
                                            0
                                        </span>
                                        <h4 class="text-white">Data Terkirim<br></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="card card-inverse card-danger card-mapping" onclick="search('pending')">
                            <div class="card-body">
                                <div class="row align-items-center ml-1">
                                    <i class="fas fa-clock text-white" style="font-size: 48px"></i>
                                    <div class="ml-3">
                                        <span data-count="pending" class="text-white" style="font-size: 24px">
                                            0
                                        </span>
                                        <h4 class="text-white">Data Belum Terkirim<br></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Filter Periode -->
                    <div class="col-12 col-md-12 mt-4">
                        <div class="form-group">
                            <div class="row justify-content-center align-items-end">
                                <div class="col-5">
                                    <label for="start_date">Periode Tanggal Upload</label>
                                    <input type="text" class="form-control" id="start_date">
                                    <span class="bar"></span>
                                </div>
                                <div class="col-2 text-center">
                                    <label>&nbsp;</label>
                                    <small>-</small>
                                </div>
                                <div class="col-5">
                                    <label for="end_date">&nbsp;</label>
                                    <input type="text" class="form-control" id="end_date">
                                    <span class="bar"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi -->
                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-success btn-rounded mr-3" onclick="resetSearch()">
                        Reset Pencarian <i class="mdi mdi-refresh"></i>
                    </button>
                    <button type="submit" class="btn btn-rounded btn-info">
                        Cari Data <i class="mdi mdi-magnify"></i>
                    </button>
                </div>
            </form>

            <hr>

            <!-- 🧾 Tabel Data -->
            <div class="table-responsive">
                <table id="diagnosticTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>Pasien</th>
                            <th>Kategori & File</th>
                            <th>Diupload Oleh</th>
                            <th>Tanggal Upload</th>
                            <th width="300"></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>


@endsection

@push('after-script')
    <script src="{{ asset('assets/plugins/moment/moment.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js') }}">
    </script>
    <script>
        var table;

        $(document).ready(function () {
            const today = moment().format('YYYY-MM-DD');
            const sevenDaysAgo = moment().subtract(7, 'days').format('YYYY-MM-DD');

            // 🗓️ datepicker
            $("#start_date").bootstrapMaterialDatePicker({
                weekStart: 0,
                time: false,
                format: 'YYYY-MM-DD'
            });

            $("#end_date").bootstrapMaterialDatePicker({
                weekStart: 0,
                time: false,
                format: 'YYYY-MM-DD'
            });

            // Leave date fields empty by default (no date filter)
            $('#start_date').val(sevenDaysAgo);
            $('#end_date').val(today);

            // ⚙️ DataTable
            table = $('#diagnosticTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('satusehat.diagnostic-report.datatable') }}",
                    type: 'POST',
                    data: function (d) {
                        d._token = '{{ csrf_token() }}';
                        d.tgl_awal = $('#start_date').val();
                        d.tgl_akhir = $('#end_date').val();
                        d.search = $('input[name="search"]').val();
                    }
                },
                columns: [
                    {
                        data: 'pasien',
                        name: 'c.NAMA'
                    },
                    {
                        data: 'kategori_file',
                        name: 'b.nama_kategori',
                        orderable: false
                    },
                    {
                        data: 'diupload_oleh',
                        name: 'a.usr_crt'
                    },
                    {
                        data: 'tanggal_upload',
                        name: 'a.crt_dt',
                        type: 'date'
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [3, 'desc'] // Order by tanggal_upload column (index 3) descending
                ]
            });

            table.on('xhr.dt', function (e, settings, json, xhr) {
                if (json && json.summary) {
                    $('span[data-count="all"]').text(json.summary.all ?? 0);
                    $('span[data-count="sent"]').text(json.summary.sent ?? 0);
                    $('span[data-count="pending"]').text(json.summary.pending ?? 0);
                }
            });

            // 🔍 tombol cari
            $("#search-data").on("submit", function (e) {
                e.preventDefault();
                table.ajax.reload();
            });
        });

        // 🔄 reset filter
        function resetSearch() {
            $('#start_date').val('');
            $('#end_date').val('');
            $('input[name="search"]').val('');
            table.ajax.reload();
        }

        // 📦 filter by card
        function search(type) {
            $('input[name="search"]').val(type);
            table.ajax.reload();
        }

        // 📂 Open file in new window
        function openFile(url) {
            window.open(url, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
        }

        // 🗑️ Confirm delete document
        function confirmDelete(docId) {
            if (!docId) return;

            swal({
                title: "Hapus Dokumen?",
                text: "Yakin ingin menghapus dokumen ini? Tindakan ini tidak dapat dibatalkan.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#dd6b55",
                confirmButtonText: "Ya, hapus!",
                cancelButtonText: "Batal",
                closeOnConfirm: false
            }, function() {
                deleteDocument(docId);
            });
        }

    </script>
@endpush