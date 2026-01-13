@extends('layouts.app')

@push('before-style')
    <link rel="stylesheet" href="{{ asset('assets/css/icons/font-awesome/css/fontawesome-all.min.css') }}" />
    <style>
        .card-mapping {
            border-radius: 15px;
            cursor: pointer;
        }

        .card-mapping:hover {
            box-shadow: 0 14px 18px rgba(0, 0, 0, 0.3);
            transform: translateY(-5px);
        }

        table.table th:first-child,
        table.table td:first-child {
            width: 50px !important;
            text-align: center;
        }

        input[type="checkbox"] {
            appearance: auto !important;
            visibility: visible !important;
        }
                /* ✅ Pastikan kolom pertama untuk checkbox terlihat */
        table.table th:first-child,
        table.table td:first-child {
            width: 50px !important;
            text-align: center;
            vertical-align: middle;
        }

        /* ✅ Override styling Bootstrap yang kadang menyembunyikan checkbox */
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
            <h3 class="text-themecolor">Medication Statement</h3>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Medication Statement</li>
            </ol>
        </div>
        <div class="col-md-7 col-4 align-self-center text-right">
            <h6>Selamat Datang <b>{{ Session::get('user') }}</b></h6>
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            <h4 class="card-title mb-3">Riwayat Obat Pasien (Medication Statement)</h4>

            {{-- Summary Cards --}}
            <div class="row mb-4">

                <div class="col-md-4">
                    <div class="card card-inverse card-primary card-mapping" onclick="search('all')">
                        <div class="card-body d-flex align-items-center">
                            <i class="fas fa-users text-white" style="font-size: 40px"></i>
                            <div class="ml-3">
                                <span data-count="all" style="font-size: 26px" class="text-white">0</span>
                                <h6 class="text-white">Total Pasien</h6>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-inverse card-success card-mapping" onclick="search('integrated')">
                        <div class="card-body d-flex align-items-center">
                            <i class="fas fa-paper-plane text-white" style="font-size: 40px"></i>
                            <div class="ml-3">
                                <span data-count="sent" style="font-size: 26px" class="text-white">0</span>
                                <h6 class="text-white">Sudah Terkirim</h6>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-inverse card-danger card-mapping" onclick="search('not_integrated')">
                        <div class="card-body d-flex align-items-center">
                            <i class="fas fa-times-circle text-white" style="font-size: 40px"></i>
                            <div class="ml-3">
                                <span data-count="unsent" style="font-size: 26px" class="text-white">0</span>
                                <h6 class="text-white">Belum Terkirim</h6>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">

                <!-- KIRI: Action Button -->
                <div>
                    <button type="button" id="btnKirimDipilih" class="btn btn-success btn-sm">
                        <i class="fas fa-paper-plane"></i> Kirim Dipilih
                    </button>

                    <button type="button" id="btnRefresh" class="btn btn-secondary btn-sm ml-2">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>

                <!-- KANAN: Filter Tanggal -->
            <div class="d-flex align-items-center">
                <label class="mb-0 mr-2 text-muted">Periode</label>

                <input type="text"
                    id="start_date"
                    class="form-control form-control-sm mr-2"
                    style="width: 150px"
                    placeholder="dd-mm-yyyy"
                    autocomplete="off">

                <span class="mr-2">-</span>

                <input type="text"
                    id="end_date"
                    class="form-control form-control-sm mr-2"
                    style="width: 150px"
                    placeholder="dd-mm-yyyy"
                    autocomplete="off">

                <button class="btn btn-primary btn-sm" id="btnFilter">
                    <i class="fas fa-filter"></i>
                </button>
            </div>


            </div>

            {{-- DataTable --}}
            <div class="table-responsive">
                <table id="medicationTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th><input type="checkbox" id="checkAll"></th>
                            <th>Pasien</th>
                            <th>No Karcis</th>
                            <th>Tanggal Transaksi</th>
                            <th>Waktu Kirim Dispense</th>
                            <th>Status Statement</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
                <div class="modal fade" id="modalDetail" tabindex="-1">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content">

                            <div class="modal-header">
                                <h5 class="modal-title">Detail Medication Statement</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>

                            <div class="modal-body" id="modalDetailBody">
                                <div class="text-center text-muted">
                                    <i class="fas fa-spinner fa-spin"></i> Memuat data...
                                </div>
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
        let table;
        var statusFilter = 'all';

        $(document).ready(function() {
            const today = new Date();
            const endDate = today.toISOString().slice(0, 10);

            const start = new Date();
            start.setDate(start.getDate() - 21);
            const startDate = start.toISOString().slice(0, 10);

            $('#start_date').val(startDate);
            $('#end_date').val(endDate);

            table = $('#medicationTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('satusehat.medstatement.datatabel') }}',
                    type: 'POST',
                    data: function(d) {
                        d._token = '{{ csrf_token() }}';
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.status = statusFilter;
                    }
                },
                columns: [{
                        data: null,
                        className: 'text-center',
                        render: (d, t, r, m) => m.row + m.settings._iDisplayStart + 1
                    },

                    {
                        data: 'ID_TRANS',
                        render: id =>
                            `<input type="checkbox" class="checkbox-item" value="${id}">`
                    },

                    {
                        data: null,
                        render: r => `
                        <b>${r.NMPX ?? '-'}</b><br>
                        <small>ID Trans: ${r.ID_TRANS}</small>
                    `
                    },

                    {
                        data: 'KARCIS',
                        render: v => v ?? '-'
                    },

                    {
                        data: 'TGL',
                        render: v => v ? v.substring(0, 10) : '-'
                    },

                    {
                        data: 'WAKTU_KIRIM_DISPENSE',
                        render: v =>
                            v ? v.replace('.000', '') : '-'
                    },
                    {
                        data: 'STATUS_KIRIM_STATEMENT',
                        render: v => {
                            if (v === 'Integrasi') {
                                return `<span class="badge badge-success">Integrasi</span>`;
                            }
                            return `<span class="badge badge-danger">Belum Integrasi</span>`;
                        }
                    },
                    {
                        data: null,
                        render: r => `
                        <button class="btn btn-sm btn-success w-100 mb-1"
                                onclick="kirimSatu('${r.ID_TRANS}', this)">
                            <i class="fas fa-paper-plane"></i> Kirim
                        </button>

                        <button class="btn btn-sm btn-info w-100" onclick="lihatDetail('${r.ID_TRANS}')">
                            <i class="fas fa-eye"></i> Detail
                        </button>

                    `
                    }
                ]
            });

            $('#btnFilter').on('click', function() {
                table.ajax.reload();
            });

            $('#btnRefresh').on('click', function() {
                $('#start_date').val('');
                $('#end_date').val('');
                table.ajax.reload();
            });

            table.on('xhr.dt', function (e, settings, json) {
                if (!json || !json.summary) return;

                $('span[data-count="all"]').text(json.summary.all ?? 0);
                $('span[data-count="sent"]').text(json.summary.sudah_kirim ?? 0);
                $('span[data-count="unsent"]').text(json.summary.belum_kirim ?? 0);
            });


            $("#checkAll").change(() => $('.checkbox-item').prop('checked', $("#checkAll").is(':checked')));

        });


        // =============== DETAIL (Medication Statement) =======================
        function lihatDetail(idTrans) {
            $('#modalDetail').modal('show');
            $('#modalDetailBody').html(`
            <div class="text-center text-muted">
                <i class="fas fa-spinner fa-spin"></i> Memuat data...
            </div>
            `);

            $.ajax({
                url: '{{ route('satusehat.medstatement.detail') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id_trans: idTrans
                },
                success: function(res) {
                    $('#modalDetailBody').html(res.html);
                },
                error: function() {
                    $('#modalDetailBody').html(
                        '<div class="alert alert-danger">Gagal memuat detail</div>'
                    );
                }
            });
        }



        // ============ Kirim Medication Statement =====================
        function kirimSatu(id, btn = null, done = null) {

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = 'Mengirim...';
            }

            fetch("{{ route('satusehat.medstatement.sendpayload') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ id_trans: id })
            })
            .then(res => res.json())
            .then(res => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim';
                }

                if (!done) {
                    // klik manual
                    if (res.status) {
                        Swal.fire('Sukses', 'Data berhasil dikirim', 'success');
                        $('#medicationTable').DataTable().ajax.reload(null, false);
                    } else {
                        Swal.fire('Gagal', res.message || 'Gagal kirim data', 'error');
                    }
                }

            })
            .catch(err => {
                console.error(err);
            })
            .finally(() => {
                if (done) done();
            });
        }


        function kirimSatusehat(id, btn = null) {

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
            }

            Swal.fire({
                title: 'Mengirim...',
                text: 'Mengirim Medication Statement ke SATUSEHAT',
                type: 'info',
                showConfirmButton: false,
                allowOutsideClick: false
            });

            $.ajax({
                url: "{{ route('satusehat.medstatement.sendpayload') }}",
                type: "POST",
                data: {
                    id_trans: id,
                    _token: "{{ csrf_token() }}"
                },
                success: function(res) {

                    if (res.status === true) {
                        Swal.fire({
                            title: 'Berhasil',
                            text: 'Medication Statement berhasil dikirim',
                            type: 'success'
                        });
                    } else {
                        let detail = '';

                        if (res && res.data) {
                            detail = `
                                <details style="text-align:left; margin-top:10px;">
                                    <summary style="cursor:pointer; font-weight:bold;">
                                        Detail Error
                                    </summary>
                                    <pre style="
                                        white-space: pre-wrap;
                                        word-break: break-word;
                                        background: #f8f9fa;
                                        padding: 10px;
                                        border-radius: 4px;
                                        max-height: 200px;
                                        overflow: auto;
                                    ">${JSON.stringify(res.data, null, 2)}</pre>
                                </details>
                            `;
                        }

                        Swal.fire({
                            title: 'Gagal',
                            html: `
                                <div>Pengiriman gagal</div>
                                ${detail}
                            `,
                            type: 'error',
                            width: 600
                        });

                    }

                    if (typeof table !== 'undefined') {
                        table.ajax.reload(null, false);
                    }
                },
                error: function(xhr) {

                    let msg = 'Terjadi kesalahan saat mengirim data';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        title: 'Error',
                        text: msg,
                        type: 'error'
                    });
                },
                complete: function() {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim';
                    }
                }
            });
        }

        // ngurusin search by card
        function search(type) {
            statusFilter = type;     
            table.ajax.reload();
        }


    </script>
    <script>
        document.getElementById('checkAll').addEventListener('change', function () {
            const checked = this.checked;
            document.querySelectorAll('.checkbox-item').forEach(cb => {
                cb.checked = checked;
            });
        });
    </script>
    <script>
        document.getElementById('btnKirimDipilih').addEventListener('click', function () {

            const selected = Array.from(document.querySelectorAll('.checkbox-item:checked'))
                .map(cb => cb.value);

            if (selected.length === 0) {
                Swal.fire('Info', 'Tidak ada data yang dipilih', 'info');
                return;
            }

            Swal.fire({
                title: `Kirim ${selected.length} data?`,
                text: 'Semua data yang dipilih akan dikirim ke SATUSEHAT',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Kirim',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33'
            }).then(result => {
                if (!result.value) return;

                bulkKirimSequential(selected);
            });

        });
    </script>
    <script>
        function bulkKirimSequential(list) {
            let index = 0;

            Swal.fire({
                title: 'Mengirim data…',
                html: `<b id="bulkStatus">0</b> / ${list.length}`,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            function next() {
                if (index >= list.length) {
                    Swal.fire('Selesai', 'Semua data sudah diproses', 'success');
                    $('#medicationTable').DataTable().ajax.reload(null, false);
                    return;
                }

                const id = list[index];

                // update progress
                document.getElementById('bulkStatus').innerText = index + 1;

                kirimSatu(id, null, function () {
                    index++;
                    setTimeout(next, 400); // kasih delay biar API gak dihajar
                });
            }

            next();
        }
    </script>



@endpush
