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

        /* ✅ Kolom checkbox */
        table.table th:first-child,
        table.table td:first-child {
            width: 50px !important;
            text-align: center;
            vertical-align: middle;
        }

        input[type="checkbox"],
        .form-check-input {
            appearance: auto !important;
            -webkit-appearance: checkbox !important;
            -moz-appearance: checkbox !important;
            opacity: 1 !important;
            position: static !important;
            visibility: visible !important;
            cursor: pointer;
        }
    </style>

@endpush

@section('content')
    <div class="row page-titles">
        <div class="col-md-5 col-8 align-self-center">
            <h3 class="text-themecolor">Medication Dispense</h3>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Medication Dispense</li>
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
            <h4 class="card-title">Data Tebus Obat (Medication Dispense)</h4>
            <form action="javascript:void(0)" id="search-data" class="m-t-40">
                <input type="hidden" name="search" value="{{ request('search') }}">

                <div class="row justify-content-center">
                    <!-- Summary Cards -->
                    <div class="col-4">
                        <div class="card card-inverse card-primary card-mapping" onclick="search('all')">
                            <div class="card-body">
                                <div class="row align-items-center ml-1">
                                    <i class="fas fa-pills text-white" style="font-size: 48px"></i>
                                    <div class="ml-3">
                                        <span data-count="all" class="text-white" style="font-size: 24px">
                                            {{ count($mergedAll ?? []) }}
                                        </span>
                                        <h4 class="text-white">Semua Data</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="card card-inverse card-success card-mapping" onclick="search('sent')">
                            <div class="card-body">
                                <div class="row align-items-center ml-1">
                                    <i class="fas fa-paper-plane text-white" style="font-size: 48px"></i>
                                    <div class="ml-3">
                                        <span data-count="sent" class="text-white" style="font-size: 24px">
                                            {{ count($sentData ?? []) }}
                                        </span>
                                        <h4 class="text-white">Data Terkirim</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="card card-inverse card-danger card-mapping" onclick="search('unsent')">
                            <div class="card-body">
                                <div class="row align-items-center ml-1">
                                    <i class="fas fa-hourglass-half text-white" style="font-size: 48px"></i>
                                    <div class="ml-3">
                                        <span data-count="unsent" class="text-white" style="font-size: 24px">
                                            {{ count($unsentData ?? []) }}
                                        </span>
                                        <h4 class="text-white">Belum Terkirim</h4>
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
                                    <label for="start_date">Periode Awal</label>
                                    <input type="text" class="form-control" id="start_date">
                                    <span class="bar"></span>
                                </div>
                                <div class="col-2 text-center">
                                    <label>&nbsp;</label>
                                    <small>-</small>
                                </div>
                                <div class="col-5">
                                    <label for="end_date">Periode Akhir</label>
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
                        Reset <i class="mdi mdi-refresh"></i>
                    </button>
                    <button type="submit" class="btn btn-rounded btn-info">
                        Cari Data <i class="mdi mdi-magnify"></i>
                    </button>
                </div>
            </form>

            <hr>

            <!-- 🧾 Tabel Data -->
            <div class="mb-3">
                <button type="button" id="btnKirimDipilih" class="btn btn-success btn-sm">
                    <i class="fas fa-paper-plane"></i> Kirim Dipilih
                </button>
            </div>

            <div class="table-responsive">
                <table id="dispenseTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th class="text-center"><input type="checkbox" id="checkAll"></th>
                            <th>Karcis/Nomor Transaksi</th>
                            <th>Pasien</th>
                            <th>Dokter</th>
                            <th>Tgl Kunjungan</th>
                            <th>Status Integrasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                </table>
            </div>
        </div>
    </div>

    @include('modals.modal_lihat_obat')
@endsection

@push('after-script')
    <script src="{{ asset('assets/plugins/moment/moment.js') }}"></script>
    <script
        src="{{ asset('assets/plugins/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js') }}"></script>
    <script>
        var table;

        $(document).ready(function () {
            // 🗓️ inisialisasi datepicker
            var endDate = moment();
            var startDate = moment().subtract(30, 'days');

            $("#start_date").bootstrapMaterialDatePicker({
                weekStart: 0, time: false, format: 'YYYY-MM-DD', defaultDate: startDate
            });

            $("#end_date").bootstrapMaterialDatePicker({
                weekStart: 0, time: false, format: 'YYYY-MM-DD', defaultDate: endDate
            });

            $('#start_date').val(startDate.format('YYYY-MM-DD'));
            $('#end_date').val(endDate.format('YYYY-MM-DD'));

            // ⚙️ DataTable
            table = $('#dispenseTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('satusehat.medication-dispense.datatable') }}',
                    type: 'POST',
                    data: function (d) {
                        d._token = '{{ csrf_token() }}';
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.search = $('input[name="search"]').val();
                    }
                },
                columns: [
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function (data) {
                            if (data.STATUS_MAPPING === '100' || data.STATUS_MAPPING === '200') {
                                return `<input type="checkbox" class="checkbox-item" value="${data.ID_TRANS}">`;
                            } else {
                                return `<i class="text-muted">-</i>`;
                            }
                        }
                    },
                    {
                        data: null,
                        name: 'NomorKarcis',
                        render: function (data) {
                            return `
                    <div>
                        <strong>${data.NomorKarcis}</strong><br>
                        <small class="text-muted">#${data.ID_TRANS}</small>
                    </div>
                `;
                        }
                    },
                    { data: 'NamaPasien', name: 'NamaPasien' },
                    { data: 'NamaDokter', name: 'NamaDokter' },
                    { data: 'TanggalKunjungan', name: 'TanggalKunjungan' },
                    {
                        data: 'STATUS_MAPPING',
                        className: 'text-center',
                        render: function (status) {
                            if (status === '200') {
                                return `<span class="badge badge-success">Sudah Dikirim</span>`;
                            } else if (status === '100') {
                                return `<span class="badge badge-warning">Siap Dikirim</span>`;
                            } else {
                                return `<span class="badge badge-danger">Belum Integrasi</span>`;
                            }
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (data) {
                            let btn = `
                                <button class="btn btn-sm btn-info w-100 mb-2" onclick="lihatObat('${data.ID_TRANS}')">
                                    <i class="fas fa-eye"></i> Lihat Obat
                                </button>`;

                            if (data.STATUS_MAPPING === '100') {
                                btn += `
                                    <button class="btn btn-sm btn-primary w-100" onclick="confirmkirimSatusehat('${data.ID_TRANS}')">
                                        <i class="fas fa-paper-plane"></i> Kirim SATUSEHAT
                                    </button>`;
                            } else if (data.STATUS_MAPPING === '200') {
                                btn += `
                                    <button class="btn btn-sm btn-warning w-100" onclick="confirmkirimSatusehat('${data.ID_TRANS}')">
                                        <i class="fas fa-redo"></i> Kirim Ulang
                                    </button>`;
                            } else {
                                btn += `
                                    <span class="badge badge-secondary w-100 py-2">
                                        <i class="fas fa-ban"></i> Belum ada MedicationRequest
                                    </span>`;
                            }

                            return btn;
                        }
                    }
                ],

                order: [[3, 'desc']]
            });

            // reload summary
            table.on('xhr.dt', function (e, settings, json) {
                if (json && json.summary) {
                    $('span[data-count="all"]').text(json.summary.all ?? 0);
                    $('span[data-count="sent"]').text(json.summary.sent ?? 0);
                    $('span[data-count="unsent"]').text(json.summary.unsent ?? 0);
                }
            });

            $("#search-data").on("submit", function (e) {
                e.preventDefault();
                table.ajax.reload();
            });
        });

        function resetSearch() {
            var endDate = moment();
            var startDate = moment().subtract(30, 'days');
            $('#start_date').val(startDate.format('YYYY-MM-DD'));
            $('#end_date').val(endDate.format('YYYY-MM-DD'));
            $('input[name="search"]').val('');
            table.ajax.reload();
        }
        // ✅ Checkbox select all
        $(document).on('change', '#checkAll', function () {
            $('.checkbox-item').prop('checked', $(this).is(':checked'));
        });

        // ✅ Tombol batch send
        $('#btnKirimDipilih').on('click', function () {
            const selected = $('.checkbox-item:checked').map(function () {
                return $(this).val();
            }).get();

            if (selected.length === 0) {
                swal({
                    title: 'Tidak ada data yang dipilih',
                    text: 'Silakan centang transaksi yang ingin dikirim.',
                    type: 'warning',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            swal({
                title: 'Kirim Data Terpilih?',
                text: `Akan mengirim ${selected.length} transaksi ke SATUSEHAT.`,
                type: 'question',
                showCancelButton: true,
                confirmButtonText: 'Kirim',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33'
            }).then(function (result) {
                if (!result.value) return;
                sendSequential(selected);
            });
        });


        // 🚀 Proses sequential
        async function sendSequential(selected) {
            let successCount = 0;
            let failCount = 0;
            let successIds = [];
            let failIds = [];

            for (let i = 0; i < selected.length; i++) {
                const idTrans = selected[i];
                swal({
                    title: 'Mengirim Data...',
                    text: `Mengirim ${i + 1} dari ${selected.length} transaksi...`,
                    type: 'info',
                    showConfirmButton: false,
                    allowOutsideClick: false
                });

                try {
                    const result = await kirimSatusehat(idTrans, null, false);
                    if (result.success) {
                        successCount++;
                        successIds.push(idTrans);
                    } else {
                        failCount++;
                        failIds.push(idTrans);
                    }
                } catch (err) {
                    failCount++;
                    failIds.push(idTrans);
                }
            }

            // 🧾 Ringkasan akhir
            let summaryHtml = `
                    <div style="text-align:left; max-height:300px; overflow-y:auto;">
                        <strong>Sukses (${successCount}):</strong><br>
                        ${successIds.length ? successIds.map(id => `✅ ${id}`).join('<br>') : '<i>Tidak ada</i>'}
                        <br><br>
                        <strong>Gagal (${failCount}):</strong><br>
                        ${failIds.length ? failIds.map(id => `❌ ${id}`).join('<br>') : '<i>Tidak ada</i>'}
                    </div>
                `;

            swal({
                title: 'Proses Selesai',
                html: summaryHtml,
                type: failCount === 0 ? 'success' : 'warning',
                width: '600px',
                confirmButtonText: 'Tutup',
                confirmButtonColor: failCount === 0 ? '#28a745' : '#f0ad4e'
            }).then(() => table.ajax.reload(null, false));
        }

        function search(type) {
            $('input[name="search"]').val(type);
            table.ajax.reload();
        }

        // Lihat data obat
        function lihatObat(idTrans) {
            $('#modalObat').modal('show');
            $('#obatDetailContent').html(`<p class='text-center text-muted'>Memuat data obat...</p>`);

            $.ajax({
                url: '{{ route('satusehat.medication-dispense.detail') }}',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}', id: idTrans },
                success: function (res) {
                    if (res.status === 'success') {
                        let html = `<table class="table table-sm table-bordered">
                                                <thead class="thead-light">
                                                    <tr><th>No</th><th>Nama Obat</th><th>KFA Code</th><th>Nama KFA</th></tr>
                                                </thead><tbody>`;

                        res.data.forEach((row, i) => {
                            html += `<tr>
                                                    <td>${i + 1}</td>
                                                    <td>${row.NAMA_OBAT ?? '-'}</td>
                                                    <td>${row.KD_BRG_KFA ?? '-'}</td>
                                                    <td>${row.NAMABRG_KFA ?? '-'}</td>
                                                </tr>`;
                        });

                        html += `</tbody></table>`;
                        $('#obatDetailContent').html(html);
                    } else {
                        $('#obatDetailContent').html(`<p class='text-danger text-center'>${res.message}</p>`);
                    }
                },
                error: function () {
                    $('#obatDetailContent').html(`<p class='text-danger text-center'>Gagal memuat data obat.</p>`);
                }
            });
        }

        function confirmkirimSatusehat(idTrans) {
            console.log('confirmKirimSatusehat called, idTrans =', idTrans);
            if (!idTrans) return;

            Swal.fire({
                title: 'Kirim ke SATUSEHAT?',
                text: `Yakin ingin mengirim transaksi ${idTrans} ke SATUSEHAT?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, kirim',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33'
            }).then((result) => {
                if (result) {
                    console.log("Lanjut kirim ke SATUSEHAT");
                    kirimSatusehat(idTrans);
                } else {
                    console.log("Dibatalkan");
                }
            });
        }

        // Kirim ke SATUSEHAT
        function kirimSatusehat(idTrans, btn = null, showSwal = true) {
            return new Promise((resolve, reject) => {
                if (!idTrans) return reject('ID_TRANS kosong.');

                $.ajax({
                    url: '{{ route('satusehat.medication-dispense.sendsehat') }}',
                    type: 'GET',
                    data: { id_trans: idTrans },
                    success: function (res) {
                        if (res.status === 'success') {
                            if (showSwal) {
                                swal({
                                    title: 'Berhasil!',
                                    text: `Transaksi ${idTrans} berhasil dikirim ke SATUSEHAT.`,
                                    type: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                            resolve({ success: true, id: idTrans });
                        } else {
                            if (showSwal) {
                                swal({
                                    title: 'Gagal!',
                                    text: `Transaksi ${idTrans} gagal dikirim.\n${res.message || ''}`,
                                    type: 'warning'
                                });
                            }
                            resolve({ success: false, id: idTrans });
                        }
                    },
                    error: function (xhr) {
                        console.error(`❌ Error kirim ${idTrans}:`, xhr);

                        // ambil pesan dari API
                        let errMsg = 'Terjadi kesalahan saat mengirim data.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        } else if (xhr.responseText) {
                            // fallback untuk response non-JSON
                            errMsg = xhr.responseText.substring(0, 500); // biar gak kepanjangan
                        }

                        if (showSwal) {
                            swal({
                                title: 'Error!',
                                html: `<b>Transaksi ${idTrans}</b> gagal dikirim.<br><br>` +
                                    `<pre style="text-align:left;white-space:pre-wrap;">${errMsg}</pre>`,
                                type: 'error',
                                width: '600px',
                                confirmButtonColor: '#d33'
                            });
                        }

                        resolve({ success: false, id: idTrans, message: errMsg });
                    }

                });
            });
        }


    </script>
@endpush