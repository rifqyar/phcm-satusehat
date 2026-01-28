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
            <h4 class="card-title">Data Riwayat Imunisasi Pasien</h4>

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

                    <!-- Filter Periode + Jenis Pelayanan -->
                    <div class="col-12 mt-4">
                        <div class="form-group">
                            <div class="row justify-content-center align-items-end">

                                <!-- Tanggal Mulai -->
                                <div class="col-md-6">
                                    <label for="start_date">Periode Awal</label>
                                    <input type="text" class="form-control" id="start_date">
                                    <span class="bar"></span>
                                </div>

                                <!-- Tanggal Akhir -->
                                <div class="col-md-6">
                                    <label for="end_date">Periode Akhir</label>
                                    <input type="text" class="form-control" id="end_date">
                                    <span class="bar"></span>
                                </div>

                                <!-- Jenis Pelayanan -->
                                {{-- <div class="col-md-4">
                                    <label for="jenis">Jenis Pelayanan</label>
                                    <select id="jenis" name="jenis" class="form-control">
                                        <option value="">Rawat Jalan</option>
                                        <option value="ri">Rawat Inap</option>
                                    </select>
                                    <span class="bar"></span>
                                </div> --}}

                            </div>
                        </div>
                    </div>

                </div>

                <!-- Tombol Aksi -->
                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-success btn-rounded mr-3" onclick="resetSearch()">
                        Reset Pencarian <i class="mdi mdi-refresh"></i>
                    </button>
                    <button type="button" id="btnCariData" class="btn btn-rounded btn-info">
                        Cari Data <i class="mdi mdi-magnify"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <div class="row align-items-center justify-content-between m-1">
                    <div class="card-title">
                        <h4>Data Imunisasi</h4>
                    </div>

                    <button type="button" id="btnKirimDipilih" class="btn btn-warning btn-rounded">
                        <i class="fas fa-paper-plane"></i> Kirim Terpilih ke SatuSehat
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="dataTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th class="text-center">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th>Karcis</th>
                            <th>Nama Pasien</th>
                            <th>Tanggal Vaksin</th>
                            <th>Jenis Vaksin</th>
                            <th>Status Integrasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>

        </div>
    </div>

    <!-- Modal Detail Imunisasi -->
    <div class="modal fade" id="modalDetailImunisasi" tabindex="-1" role="dialog"
        aria-labelledby="modalDetailImunisasiLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">

                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalDetailImunisasiLabel">
                        <i class="fas fa-syringe"></i> Detail Imunisasi
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <!-- info umum -->
                    <table class="table table-sm table-bordered mb-3">
                        <tr>
                            <th width="30%">Karcis</th>
                            <td id="detail_karcis">-</td>
                        </tr>
                        <tr>
                            <th>Nama Pasien</th>
                            <td id="detail_nama">-</td>
                        </tr>
                        <tr>
                            <th>Status SATUSEHAT</th>
                            <td id="detail_status">-</td>
                        </tr>
                    </table>

                    <!-- riwayat imunisasi -->
                    <h6>Riwayat Imunisasi</h6>
                    <table class="table table-sm table-striped table-bordered">
                        <thead class="bg-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenis Vaksin</th>
                                <th>Display Vaksin</th>
                                <th>Kode Centra</th>
                                <th>Kode KFA</th>
                                <th>Dosis</th>
                                <th>Input Date</th>
                            </tr>
                        </thead>
                        <tbody id="detail_imunisasi_body">
                            <tr>
                                <td colspan="4" class="text-center text-muted">
                                    (dummy data)
                                </td>
                            </tr>
                        </tbody>
                    </table>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Tutup
                    </button>
                </div>

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
        var firstLoad = true;
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
            table = $('#dataTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '{{ route('satusehat.imunisasi.datatabel') }}',
                    type: 'POST',
                    data: function(d) {
                        d._token = '{{ csrf_token() }}';
                        d.tgl_awal = $('#start_date').val();
                        d.tgl_akhir = $('#end_date').val();
                    }
                },
                columns: [
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function (data, type, row) {

                            if (type !== 'display') {
                                return '-';
                            }

                            if (row.SATUSEHAT_STATUS === 'SUCCESS') {
                                return '-';
                            }

                            return `
                                <input type="checkbox"
                                    class="checkbox-item"
                                    value="${row.ID_IMUNISASI_PX}">
                            `;
                        }
                    },
                    {
                        data: 'karcis',
                        render: function(v) {
                            return `<strong>${v ?? '-'}</strong>`;
                        }
                    },
                    {
                        data: 'NAMA_PASIEN',
                        render: function(v) {
                            return `<strong>${v ?? '-'}</strong>`;
                        }
                    },
                    {
                        data: 'TANGGAL',
                        render: function(v) {
                            return v ? moment(v).format('DD-MM-YYYY') : '-';
                        }
                    },
                    {
                        data: 'JENIS_VAKSIN',
                        render: function(v) {
                            return v ?? '-';
                        }
                    },
                    {
                        data: 'SATUSEHAT_STATUS',
                        className: 'text-center',
                        render: function(v, type, row) {

                            // untuk search / sort -> pakai value asli
                            if (type !== 'display') {
                                return v ?? 'DRAFT';
                            }

                            if (v === 'SUCCESS') {
                                return `
                                    <span class="badge badge-pill badge-success p-2 w-100">
                                        Sudah Integrasi
                                    </span>
                                `;
                            }

                            return `
                                <span class="badge badge-pill badge-danger p-2 w-100">
                                    Belum Integrasi
                                </span>
                            `;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(row) {
                            let btnKirim = `
                                <button class="btn btn-sm btn-primary w-100"
                                    onclick='confirmkirimSatusehat(${JSON.stringify(row)})'>
                                    <i class="fas fa-paper-plane mr-2"></i> Kirim SATUSEHAT
                                </button>
                            `;

                            // jika sudah SUCCESS → kirim ulang
                            if (row.SATUSEHAT_STATUS === 'SUCCESS') {
                                btnKirim = `
                                    <button class="btn btn-sm btn-warning w-100"
                                        onclick='confirmkirimSatusehat(${JSON.stringify(row)})'>
                                        <i class="fas fa-sync-alt mr-2"></i> Kirim Ulang SATUSEHAT
                                    </button>
                                `;
                            }

                            const btnLihat = `
                                <br/>
                                <button class="btn btn-sm btn-info w-100"
                                    onclick='cekData(${JSON.stringify(row)})'>
                                    <i class="fas fa-eye"></i> Data Imunisasi
                                </button>
                            `;

                            return btnKirim + btnLihat;
                        }

                    }
                ],
                order: [[4, 'desc']],
                lengthMenu: [10, 25, 50, 100],
                language: {
                    processing: "Memuat data..."
                },
                drawCallback: function (settings) {
                    var api = this.api();
                    var start = api.page.info().start;

                    api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                        cell.innerHTML = start + i + 1;
                    });
                }
            });

            $('#btnCariData').on('click', function() {
                table.ajax.reload();
            });

            $('#dataTable').on('xhr.dt', function(e, settings, json) {
                if (!json || !json.summary) return;

                const summary = json.summary;

                $('[data-count="all"]').text(summary.all ?? 0);
                $('[data-count="sent"]').text(summary.success ?? 0);

                const belum = (summary.all ?? 0) - (summary.success ?? 0);
                $('[data-count="unsent"]').text(belum);

                if (firstLoad) {
                    firstLoad = false;

                    setTimeout(() => {
                        search('not_integrated');
                    }, 0);
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

            // bulk sned
            $('#btnKirimDipilih').on('click', async function () {

            const selected = $('.checkbox-item:checked')
                .map(function () { return $(this).val(); })
                .get();

            if (selected.length === 0) {
                swal('Tidak ada data terpilih',
                    'Silakan pilih pasien yang ingin dikirim ke SATUSEHAT.',
                    'warning');
                return;
            }

            const confirm = await swal({
                title: 'Kirim data terpilih?',
                text: `Akan mengirim ${selected.length} data ke SATUSEHAT.`,
                icon: 'info',
                buttons: {
                    cancel: 'Batal',
                    confirm: { text: 'Kirim', closeModal: false }
                }
            });

            if (!confirm) return;

            const total = selected.length;
            const success = [];
            const fail = [];

            // === PROGRESS MODAL ===
            Swal.fire({
                title: 'Mengirim ke SATUSEHAT',
                html: `
                    <div id="progressContent" style="text-align:left">
                        <p>Proses <b>0</b> / <b>${total}</b></p>
                        <p>✔ Sukses : <b>0</b></p>
                        <p>✖ Gagal : <b>0</b></p>
                    </div>
                `,
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });


            // === LOOP KIRIM (SATU KALI SAJA) ===
            for (let i = 0; i < total; i++) {
                const id = selected[i];

                try {
                    const res = await kirimSatusehat(id, false);

                    if (res && res.success) {
                        success.push(id);
                    } else {
                        fail.push({
                            id,
                            message: res?.message || 'Gagal'
                        });
                    }

                } catch (err) {
                    fail.push({
                        id,
                        message: err
                    });
                }

                // UPDATE UI
                $('#progressContent').html(
                    renderProgress(i + 1, total, success.length, fail.length)
                );
            }

            // === SUMMARY ===
            let html = `<div style="text-align:left; max-height:300px; overflow:auto;">`;
            html += `<strong>✔ Sukses (${success.length})</strong><br>`;
            html += success.length
                ? success.map(id => `✅ ${id}`).join('<br>')
                : '<i>Tidak ada</i>';

            html += `<br><br><strong>✖ Gagal (${fail.length})</strong><br>`;
            html += fail.length
                ? fail.map(f => `❌ ${f.id} — ${f.message}`).join('<br>')
                : '<i>Tidak ada</i>';

            html += `</div>`;

            Swal.fire({
                title: 'Selesai',
                html: html,
                icon: fail.length === 0 ? 'success' : 'warning',
                confirmButtonText: 'Tutup'
            }).then(() => {
                table.ajax.reload(null, false);
            });


        });


        }); // end document.ready
        function renderProgress(done, total, ok, fail) {
            return `
                <div style="text-align:left">
                    <p>Proses <b>${done}</b> / <b>${total}</b></p>
                    <p>✔ Sukses : <b>${ok}</b></p>
                    <p>✖ Gagal : <b>${fail}</b></p>
                </div>
            `;
        }

        function search(type) {
            // reset semua filter
            table.search('').columns().search('');

            if (type === 'all') {
                table.draw();
                return;
            }

            // kolom STATUS = index 5
            if (type === 'integrated') {
                table
                    .column(6)
                    .search('^SUCCESS$', true, false) // exact match
                    .draw();
                return;
            }

            if (type === 'not_integrated') {
                table
                    .column(6)
                    .search('^(?!SUCCESS$).*', true, false) // NOT SUCCESS
                    .draw();
                return;
            }
        }

        function cekData(row) {
            if (!row) return;

            $('#detail_karcis').text(row.karcis ?? '-');
            $('#detail_nama').text(row.NAMA_PASIEN ?? '-');

            if (row.SATUSEHAT_STATUS === 'SUCCESS') {
                $('#detail_status').html(`
                    <span class="badge badge-pill badge-success p-2">
                        Sudah Integrasi
                    </span>
                `);
            } else {
                $('#detail_status').html(`
                    <span class="badge badge-pill badge-danger p-2">
                        Belum Integrasi
                    </span>
                `);
            }

            const rows = `
                <tr>
                    <td>${moment(row.TANGGAL).format('DD-MM-YYYY')}</td>
                    <td>${row.JENIS_VAKSIN ?? '-'}</td>
                    <td>${row.DISPLAY_VAKSIN ?? '-'}</td>
                    <td>${row.KODE_CENTRA ?? '-'}</td>
                    <td>${row.KODE_VAKSIN ?? '-'}</td>
                    <td>${row.DOSIS ?? '-'}</td>
                    <td>${row.CRTDT ?? '-'}</td>
                </tr>
            `;

            $('#detail_imunisasi_body').html(rows);

            $('#modalDetailImunisasi').modal('show');
        }

        function confirmkirimSatusehat(row) {

            if (!row || !row.ID_IMUNISASI_PX) {
                Swal.fire('Error', 'Data imunisasi tidak valid', 'error');
                return;
            }

            Swal.fire({
                title: 'Kirim ke SATUSEHAT?',
                html: `
                    <div style="text-align:left">
                        <b>Pasien</b> : ${row.NAMA_PASIEN}<br>
                        <b>Vaksin</b> : ${row.DISPLAY_VAKSIN}<br>
                        <b>Tanggal</b> : ${moment(row.TANGGAL).format('DD-MM-YYYY')}
                    </div>
                `,
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Kirim',
                cancelButtonText: 'Batal',
                allowOutsideClick: false
            }).then(function (result) {
                if (result.value === true) {
                    kirimSatusehat(row.ID_IMUNISASI_PX)
                    .then(() => {
                        table.ajax.reload(null, false);
                    })
                    .catch(() => {
                        table.ajax.reload(null, false);
                    });
                }
            });
        }

        function kirimSatusehat(idImunisasiPx, showSwal = true) {
            return new Promise((resolve, reject) => {

                if (showSwal) {
                    Swal.fire({
                        title: 'Mengirim ke SATUSEHAT',
                        text: 'Mohon tunggu...',
                        type: 'info',
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        onOpen: () => Swal.showLoading()
                    });
                }

                $.ajax({
                    url: "{{ route('satusehat.imunisasi.sendsatusehat') }}",
                    method: "POST",
                    dataType: "json",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id_imunisasi_px: idImunisasiPx
                    },
                    success: function (res) {
                    if (showSwal) Swal.close();

                    if (res.success === true) {
                        if (showSwal) {
                            Swal.fire('Berhasil', 'Data imunisasi berhasil dikirim', 'success');
                        }
                        resolve(res);
                    } else {

                    let message = res.message || 'Gagal Kirim Ke SATUSEHAT';
                    let rawResponse = res.response ? prettyJSON(res.response) : '-';

                    if (showSwal) {
                        Swal.fire({
                            title: 'Gagal',
                            html: `
                                <div style="text-align:left">
                                    <p>${message}</p>

                                    <details style="margin-top:10px; cursor:pointer;">
                                        <summary style="font-weight:bold; color:#d9534f;">
                                            Detail Error (SATUSEHAT)
                                        </summary>

                                        <pre style="
                                            margin-top:10px;
                                            background:#f8f9fa;
                                            padding:10px;
                                            max-height:300px;
                                            overflow:auto;
                                            border-radius:5px;
                                            font-size:12px;
                                        ">${rawResponse}</pre>
                                    </details>
                                </div>
                            `,
                            icon: 'error',
                            width: 700,
                            confirmButtonText: 'OK'
                        });
                    }

                    reject(res);
                }

                },
                    error: function (xhr) {

                        if (showSwal) Swal.close();

                        let msg = xhr.responseJSON?.message || 'Kesalahan jaringan';

                        if (showSwal) {
                            Swal.fire('Error', msg, 'error');
                        }

                        reject(msg);
                    }
                });
            });
        }

        function kirimSatu(id, btn = null) {

        }

        function prettyJSON(obj) {
            try {
                return JSON.stringify(obj, null, 2);
            } catch (e) {
                return String(obj);
            }
        }




        // Reset search helper
        function resetSearch() {
            var endDate = moment();
            var startDate = moment().subtract(30, 'days');

            $('#start_date').val(startDate.format('YYYY-MM-DD'));
            $('#end_date').val(endDate.format('YYYY-MM-DD'));
            table.ajax.reload();
        }
    </script>
@endpush
