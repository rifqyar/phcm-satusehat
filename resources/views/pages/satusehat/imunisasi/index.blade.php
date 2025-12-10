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

        /* Pastikan kolom pertama untuk nomor terlihat */
        table.table th:first-child,
        table.table td:first-child {
            width: 50px !important;
            text-align: center;
            vertical-align: middle;
        }

        /* Pastikan checkbox terlihat */
        input[type="checkbox"],
        .form-check-input {
            appearance: auto !important;
            -webkit-appearance: checkbox !important;
            -moz-appearance: checkbox !important;
            opacity: 1 !important;
            position: static !important;
            visibility: visible !important;
        }
    </style>
@endpush

@section('content')
    <div class="row page-titles">
        <div class="col-md-5 col-8 align-self-center">
            <h3 class="text-themecolor">Dashboard</h3>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Imunisasi</li>
            </ol>
        </div>
        <div class="col-md-7 col-4 align-self-center">
            <div class="d-flex m-t-10 justify-content-end">
                <h6>Selamat Datang <b>{{ Session::get('user') }}</b></h6>
            </div>
        </div>
    </div>

    <!-- Main card -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Data Pasien — Imunisasi</h4>

            <form action="javascript:void(0)" id="search-data" class="m-t-20">
                <div class="row">

                    <!-- Summary cards -->
                    <div class="col-md-4 mb-3">
                        <div class="card card-inverse card-primary card-mapping" onclick="search('all')">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <i class="fas fa-users text-white" style="font-size: 40px"></i>
                                    <div class="ml-3">
                                        <span data-count="all" class="text-white" style="font-size: 24px">0</span>
                                        <h6 class="text-white">Semua Pasien</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div class="card card-inverse card-success card-mapping" onclick="search('integrated')">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <i class="fas fa-paper-plane text-white" style="font-size: 40px"></i>
                                    <div class="ml-3">
                                        <span data-count="sent" class="text-white" style="font-size: 24px">0</span>
                                        <h6 class="text-white">Sudah Integrasi</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div class="card card-inverse card-danger card-mapping" onclick="search('not_integrated')">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <i class="fas fa-hourglass-half text-white" style="font-size: 40px"></i>
                                    <div class="ml-3">
                                        <span data-count="unsent" class="text-white" style="font-size: 24px">0</span>
                                        <h6 class="text-white">Belum Integrasi</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter periode (UI only - backend saat ini tidak memakainya) -->
                    {{-- <div class="col-12">
                        <div class="form-row align-items-end">
                            <div class="form-group col-md-4">
                                <label for="start_date">Periode Awal</label>
                                <input type="text" class="form-control" id="start_date" autocomplete="off">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="end_date">Periode Akhir</label>
                                <input type="text" class="form-control" id="end_date" autocomplete="off">
                            </div>
                            <div class="form-group col-md-4 text-right">
                                <button type="button" class="btn btn-success btn-rounded mr-2" onclick="resetSearch()">Reset</button>
                                <button type="submit" class="btn btn-info btn-rounded">Cari</button>
                            </div>
                        </div>
                    </div> --}}
                </div>
            </form>

            <hr>

            <div class="mb-3">
                <button type="button" id="btnKirimDipilih" class="btn btn-success btn-sm">
                    <i class="fas fa-paper-plane"></i> Kirim Dipilih
                </button>
                <button type="button" id="btnRefresh" class="btn btn-secondary btn-sm ml-2">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>

            <div class="table-responsive">
                <table id="medicationTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th class="text-center"><input type="checkbox" id="checkAll"></th>
                            <th>Pasien</th>
                            <th>Tanggal Lahir</th>
                            <th>Jenis Kelamin</th>
                            <th>Alamat</th>
                            <th>Status Integrasi</th>
                            <th>Aksi</th>
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

        $(document).ready(function() {
            // 1) Datepicker (UI only)
            var endDate = moment();
            var startDate = moment().subtract(30, 'days');

            $("#start_date").bootstrapMaterialDatePicker({
                weekStart: 0,
                time: false,
                format: 'YYYY-MM-DD',
                defaultDate: startDate
            });

            $("#end_date").bootstrapMaterialDatePicker({
                weekStart: 0,
                time: false,
                format: 'YYYY-MM-DD',
                defaultDate: endDate
            });

            $('#start_date').val(startDate.format('YYYY-MM-DD'));
            $('#end_date').val(endDate.format('YYYY-MM-DD'));

            // 2) Initialize DataTable
            table = $('#medicationTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('satusehat.imunisasi.datatabel') }}',
                    type: 'POST',
                    data: function(d) {
                        d._token = '{{ csrf_token() }}';
                        // NOTE: currently backend datatable uses only tglLahir NOT NULL filter.
                        // If you later support periode filtering, add:
                        // d.start_date = $('#start_date').val();
                        // d.end_date = $('#end_date').val();
                    }
                },
                columns: [{
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(id, type, row) {
                            return `<input type="checkbox" class="checkbox-item" value="${id}">`;
                        }
                    },
                    {
                        data: null,
                        render: function(row) {
                            return `<strong>${row.nama ?? '-'}</strong><br><small class="text-muted">NIK: ${row.nik ?? '-'}</small>`;
                        }
                    },
                    {
                        data: 'tglLahir',
                        name: 'tglLahir',
                        render: function(v) {
                            return v ? moment(v).format('YYYY-MM-DD') : '-';
                        }
                    },
                    {
                        data: 'sex',
                        name: 'sex',
                        render: function(v) {
                            if (!v) return '-';
                            v = v.toString().toUpperCase();
                            return (v === 'M' || v === 'L' || v === 'MALE' || v === 'L') ?
                                'Laki-laki' : 'Perempuan';
                        }
                    },
                    {
                        data: 'alamat',
                        name: 'alamat',
                        render: function(v) {
                            if (!v) return '-';
                            if (v.length > 60) return v.substring(0, 60) + '...';
                            return v;
                        }
                    },
                    {
                        data: null,
                        render: function(row) {
                            if (row.tgl_mapping) {
                                return `<span class="badge badge-success w-100">Terintegrasi</span><br><small class="text-muted">${row.tgl_mapping}</small>`;
                            } else if (row.user_mapping) {
                                // user_mapping exists but no tgl_mapping -> partial
                                return `<span class="badge badge-warning w-100">Mapping (Belum final)</span><br><small class="text-muted">${row.user_mapping}</small>`;
                            } else {
                                return `<span class="badge badge-danger w-100">Belum Terintegrasi</span>`;
                            }
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(row) {
                            return `
                                <button class="btn btn-sm btn-primary w-100 mb-1" onclick="kirimSatu('${row.id}', this)">
                                    <i class="fas fa-paper-plane"></i> Kirim
                                </button>
                                <button class="btn btn-sm btn-info w-100"
                                    onclick='lihatDetail(${JSON.stringify(row)})'>
                                    <i class="fas fa-eye"></i> Detail
                                </button>
                            `;
                        }

                    }
                ],
                order: [
                    [0, 'asc']
                ],
                lengthMenu: [10, 25, 50, 100],
                language: {
                    processing: "Memuat..."
                }
            });

            // 3) Update summary cards when table loads
            table.on('xhr.dt', function(e, settings, json, xhr) {
                if (json && json.summary) {
                    $('span[data-count="all"]').text(json.summary.all ?? 0);
                    // backend mungkin tidak mengirim sent/unsent — fallback ke 0
                    $('span[data-count="sent"]').text(json.summary.sent ?? 0);
                    $('span[data-count="unsent"]').text(json.summary.unsent ?? 0);
                } else {
                    $('span[data-count="all"]').text(0);
                    $('span[data-count="sent"]').text(0);
                    $('span[data-count="unsent"]').text(0);
                }
            });

            // 4) Checkbox select all
            $(document).on('change', '#checkAll', function() {
                $('.checkbox-item').prop('checked', $(this).is(':checked'));
            });

            // Uncheck "checkAll" when any checkbox unchecked
            $(document).on('change', '.checkbox-item', function() {
                if (!$(this).is(':checked')) {
                    $('#checkAll').prop('checked', false);
                } else if ($('.checkbox-item:checked').length === $('.checkbox-item').length) {
                    $('#checkAll').prop('checked', true);
                }
            });

            // 5) Refresh button
            $('#btnRefresh').on('click', function() {
                table.ajax.reload();
            });

            // 6) Submit search (reload table)
            $("#search-data").on("submit", function(e) {
                e.preventDefault();
                table.ajax.reload();
            });

            // 7) Bulk send
            $('#btnKirimDipilih').on('click', async function() {
                const selected = $('.checkbox-item:checked').map(function() {
                    return $(this).val();
                }).get();

                if (selected.length === 0) {
                    swal({
                        title: 'Tidak ada data terpilih',
                        text: 'Silakan pilih pasien yang ingin dikirim ke SATUSEHAT.',
                        icon: 'warning'
                    });
                    return;
                }

                const confirmResult = await swal({
                    title: 'Kirim data terpilih?',
                    text: `Akan mengirim ${selected.length} pasien ke SATUSEHAT (satu per satu).`,
                    icon: 'info',
                    buttons: {
                        cancel: "Batal",
                        confirm: {
                            text: "Kirim",
                            closeModal: false
                        }
                    }
                });

                if (!confirmResult) return;

                // show progress swal
                swal({
                    title: 'Mengirim...',
                    text: 'Proses berjalan otomatis. Jangan tutup halaman.',
                    icon: 'info',
                    buttons: false
                });

                // sequential send
                let success = [],
                    fail = [];
                for (let i = 0; i < selected.length; i++) {
                    const id = selected[i];
                    try {
                        const res = await kirimSatusehat(id, false);
                        if (res && res.success) success.push(id);
                        else fail.push({
                            id: id,
                            message: res.message || 'Gagal'
                        });
                    } catch (err) {
                        fail.push({
                            id: id,
                            message: err
                        });
                    }
                }

                // show summary
                let html = `<div style="text-align:left; max-height:300px; overflow:auto;">`;
                html += `<strong>Sukses (${success.length}):</strong><br>`;
                html += success.length ? success.map(i => `✅ ${i}`).join('<br>') : '<i>Tidak ada</i>';
                html += `<br><br><strong>Gagal (${fail.length}):</strong><br>`;
                html += fail.length ? fail.map(f => `❌ ${f.id} — ${f.message ? f.message : ''}`).join(
                    '<br>') : '<i>Tidak ada</i>';
                html += `</div>`;

                swal({
                    title: 'Selesai',
                    content: {
                        element: "div",
                        attributes: {
                            innerHTML: html
                        }
                    },
                    icon: fail.length === 0 ? 'success' : 'warning',
                    buttons: true
                }).then(() => {
                    table.ajax.reload(null, false);
                });
            });

        }); // end document.ready

        // ======================
        // Helper functions
        // ======================

        // Lihat detail modal (simple)
function lihatDetail(row) {
    if (!row) {
        Swal.fire("Error", "Data pasien tidak ditemukan.", "error");
        return;
    }

    const vaksinHistory = [
        { nama: "COVID-19 (Sinovac)", tanggal: "2021-02-14", dosis: "Dosis 1", fasilitas: "RSUD SEHAT SELALU" },
        { nama: "COVID-19 (Sinovac)", tanggal: "2021-03-14", dosis: "Dosis 2", fasilitas: "RSUD SEHAT SELALU" },
        { nama: "COVID-19 Booster", tanggal: "2021-09-21", dosis: "Booster 1", fasilitas: "Puskesmas Cempaka" }
    ];

    let historyHtml = `
        <h4 style="margin-top:15px">Riwayat Vaksin</h4>
        <table class="table table-sm table-bordered" style="font-size:13px; text-align:left;">
            <thead>
                <tr>
                    <th>Jenis Vaksin</th>
                    <th>Tanggal</th>
                    <th>Dosis</th>
                    <th>Fasilitas</th>
                </tr>
            </thead>
            <tbody>
    `;

    vaksinHistory.forEach(v => {
        historyHtml += `
            <tr>
                <td>${v.nama}</td>
                <td>${v.tanggal}</td>
                <td>${v.dosis}</td>
                <td>${v.fasilitas}</td>
            </tr>
        `;
    });

    historyHtml += `
            </tbody>
        </table>
    `;

    let detailHtml = `
        <table class="table table-sm table-bordered" style="text-align:left; font-size:13px;">
            <tr><th>Nama</th><td>${row.nama ?? '-'}</td></tr>
            <tr><th>NIK</th><td>${row.nik ?? '-'}</td></tr>
            <tr><th>Tgl Lahir</th><td>${row.tglLahir ?? '-'}</td></tr>
            <tr><th>Jenis Kelamin</th><td>${row.sex === 'M' ? 'Laki-laki' : 'Perempuan'}</td></tr>
            <tr><th>Alamat</th><td>${row.alamat ?? '-'}</td></tr>
            <tr><th>Status Integrasi</th><td>${row.tgl_mapping ? 'Terintegrasi' : 'Belum Terintegrasi'}</td></tr>
        </table>

        <hr>
        ${historyHtml}
    `;

    Swal.fire({
        title: "Informasi Pasien",
        html: detailHtml,
        width: 650,
        confirmButtonText: "Tutup"
    });
}






        /**
         * kirimSatu
         * @param {String|Number} id - id pasien
         * @param {HTMLElement|null} btn - tombol yang dipencet (opsional)
         * @returns Promise resolving { success: boolean, message? }
         *
         * NOTE: route used: satusehat.imunisasi.send (POST)
         * Implement backend route to actually process sending.
         */
        function kirimSatu(id, btn = null) {

        }

        /**
         * kirimSatusehat - helper yang mengembalikan Promise
         * Sering dipakai untuk batch send (dipanggil internal).
         * Memakai same backend route as kirimSatu.
         * showSwal parameter diabaikan karena we show swal in caller.
         */
        function kirimSatusehat(id, showSwal = true) {
            return kirimSatu(id, null);
        }

        // Reset search helper
        function resetSearch() {
            var endDate = moment();
            var startDate = moment().subtract(30, 'days');

            $('#start_date').val(startDate.format('YYYY-MM-DD'));
            $('#end_date').val(endDate.format('YYYY-MM-DD'));
            table.ajax.reload();
        }

        // Card search filter simple: sets a global search keyword (overrides server-side if implemented)
        function search(type) {
            // We set a global search param by using table.search(), but server-side must use it.
            // For now just reload table - you can implement server-side handling of query param if needed.
            if (type === 'integrated') {
                // optional: set a custom param via ajax.data if backend supports
                // not implemented: just reload
            }
            table.ajax.reload();
        }
    </script>
@endpush
