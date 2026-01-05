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
            <h3 class="text-themecolor">Dashboard</h3>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Medication Request</li>
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
            <h4 class="card-title">Data Transaksi Obat</h4>
            <form action="javascript:void(0)" id="search-data" class="m-t-40">
                <input type="hidden" name="search" value="{{ request('search') }}">

                <div class="row justify-content-center">
                    <!-- Card summary (tetap sama seperti sebelumnya) -->
                    <div class="col-4">
                        <div class="card card-inverse card-primary card-mapping" onclick="search('all')">
                            <div class="card-body">
                                <div class="row align-items-center ml-1">
                                    <i class="fas fa-pills text-white" style="font-size: 48px"></i>
                                    <div class="ml-3">
                                        <span data-count="all" class="text-white" style="font-size: 24px">
                                            {{ count($mergedAll ?? []) }}
                                        </span>
                                        <h4 class="text-white">Semua Data Transaksi Obat<br></h4>
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
                                        <h4 class="text-white">Data Terkirim<br></h4>
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
                                        <h4 class="text-white">Data Belum Terkirim<br></h4>
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
                                <div class="col-md-4">
                                    <label for="start_date">Periode Awal</label>
                                    <input type="text" class="form-control" id="start_date">
                                    <span class="bar"></span>
                                </div>

                                <!-- Tanggal Akhir -->
                                <div class="col-md-4">
                                    <label for="end_date">Periode Akhir</label>
                                    <input type="text" class="form-control" id="end_date">
                                    <span class="bar"></span>
                                </div>

                                <!-- Jenis Pelayanan -->
                                <div class="col-md-4">
                                    <label for="jenis">Jenis Pelayanan</label>
                                    <select id="jenis" name="jenis" class="form-control">
                                        <option value="">Rawat Jalan</option>
                                        <option value="ri">Rawat Inap</option>
                                    </select>
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
            <div class="mb-3">
                <button type="button" id="btnKirimDipilih" class="btn btn-success btn-sm">
                    <i class="fas fa-paper-plane"></i> Kirim Dipilih
                </button>
            </div>

            <div class="table-responsive">
                <table id="medicationTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th class="text-center">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th>KARCIS/ID Transaksi</th>
                            <th>Dokter</th>
                            <th>Pasien</th>
                            <th>Tanggal</th>
                            <th>Status Integrasi</th>
                            <th>Kirim SATUSEHAT</th>
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
    <script src="{{ asset('assets/plugins/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js') }}">
    </script>
    <script>
        var table;

        $(document).ready(function () {
            // 🗓️ datepicker
            var endDate = moment();
            var startDate = moment().subtract(7, 'days');

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

            // ✅ Checkbox select-all
            $(document).on('change', '#checkAll', function () {
                $('.checkbox-item').prop('checked', $(this).is(':checked'));
            });

            $(document).on('change', '#checkAll', function () {
                $('.checkbox-item').prop('checked', $(this).is(':checked'));
            });

            // 🚀 Fungsi utama kirim batch satu per satu
            async function sendSequential(selected) {
                let successCount = 0;
                let failCount = 0;
                let successIds = [];
                let failIds = [];

                for (let i = 0; i < selected.length; i++) {
                    const idTrans = selected[i];
                    console.log(`🚀 Mengirim ${i + 1}/${selected.length}: ${idTrans}`);

                    // tampilkan status swal progress
                    swal({
                        title: 'Mengirim Data...',
                        text: `Mengirim ${i + 1} dari ${selected.length} transaksi...`,
                        type: 'info',
                        showConfirmButton: false,
                        allowOutsideClick: false
                    });

                    try {
                        const result = await kirimSatusehat(idTrans, null, false);
                        if (result && result.success) {
                            successCount++;
                            successIds.push(idTrans);
                        } else {
                            failCount++;
                            failIds.push(idTrans);
                        }
                    } catch (err) {
                        console.error(`❌ Error kirim ${idTrans}:`, err);
                        failCount++;
                        failIds.push(idTrans);
                    }
                }

                // semua selesai
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
                }).then(function () {
                    table.ajax.reload(null, false);
                });
            }


            // ⚡ Event tombol "Kirim Dipilih"
            $('#btnKirimDipilih').on('click', function () {
                const selected = $('.checkbox-item:checked').map(function () {
                    return $(this).val();
                }).get();

                if (selected.length === 0) {
                    swal({
                        title: 'Tidak ada data yang dipilih',
                        text: 'Silakan centang data yang ingin dikirim ke SATUSEHAT.',
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
                    // SweetAlert2 v7 pakai `result.value`
                    if (!result.value) return;

                    // tampil swal awal
                    swal({
                        title: 'Mengirim Data...',
                        text: 'Proses akan berjalan otomatis, mohon tunggu.',
                        type: 'info',
                        showConfirmButton: false,
                        allowOutsideClick: false
                    });

                    // mulai proses sequential
                    sendSequential(selected);
                });
            });

            // ⚙️ DataTable
            table = $('#medicationTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('satusehat.medication-request.datatable') }}',
                    type: 'POST',
                    data: function (d) {
                        d._token = '{{ csrf_token() }}';
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.jenis      = $('#jenis').val();
                    }
                },
                columns: [
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
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
                        name: 'a.KARCIS', // ✅ pakai alias dari backend
                        render: function (data) {
                            return `
                            ${data.KARCIS ?? '-'}
                            <br/>
                            <small class="text-muted">ID: ${data.ID_TRANS ?? '-'}</small>
                        `;
                        }
                    },
                    { data: 'DOKTER', name: 'c.DOKTER' }, // ✅ alias backend
                    { data: 'PASIEN', name: 'c.NAMA_PASIEN', searchable: true }, // ✅ alias backend
                    { data: 'TGL_KARCIS', name: 'c.TANGGAL' }, // ✅ alias backend
                    {
                        data: null,
                        render: function (data) {
                            let badge = (data.STATUS_MAPPING === '200')
                                ? `<span class="badge badge-pill badge-success p-2 w-100">Sudah Integrasi</span>`
                                : `<span class="badge badge-pill badge-danger p-2 w-100">Belum Integrasi</span>`;
                            const idEncounter = data.id_satusehat_encounter || '-';
                            return `${badge}<br/><small class="text-muted">${idEncounter}</small>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (data) {
                            const btnLihat = `
                            <br/>
                            <button class="btn btn-sm btn-info w-100" onclick="lihatObat('${data.ID_TRANS}')">
                                <i class="fas fa-eye"></i> Lihat Obat
                            </button>`;
                            let btnAction = '';

                            if (data.id_satusehat_encounter === null || data.id_satusehat_encounter === '') {
                                btnAction = `<i class="text-danger">Belum Ada Encounter</i>`;
                            } 
                            else if (data.STATUS_MAPPING === '100') {
                                btnAction = `
                                    <button class="btn btn-sm btn-primary w-100"
                                        onclick="confirmkirimSatusehat('${data.ID_TRANS}')">
                                        <i class="fas fa-link mr-2"></i> Kirim SATUSEHAT
                                    </button>`;
                            }  else if (data.STATUS_MAPPING === '200') {
                                btnAction = `
                                <button class="btn btn-sm btn-warning w-100" onclick="confirmkirimSatusehat('${data.ID_TRANS}')">
                                    <i class="fas fa-link mr-2"></i> Kirim Ulang SATUSEHAT
                                </button>`;
                            } else {
                                btnAction = `<i class="text-muted">Data obat belum termapping</i>`;
                            }

                            return `${btnAction}${btnLihat}`;
                        }
                    }
                ],
                order: [[1, 'desc']]
            });


            table.on('xhr.dt', function (e, settings, json, xhr) {
                if (json && json.summary) {
                    $('span[data-count="all"]').text(json.summary.all ?? 0);
                    $('span[data-count="sent"]').text(json.summary.sent ?? 0);
                    $('span[data-count="unsent"]').text(json.summary.unsent ?? 0);
                }
            });

            // 🔍 tombol cari
            $("#search-data").on("submit", function (e) {
                e.preventDefault();
                table.ajax.reload();
            });

            // 🆕 tombol kirim SATUSEHAT
            $("#btnSendSatusehat").on("click", function () {
                if (confirm("Yakin ingin mengirim data ke SATUSEHAT?")) {
                    // nanti ganti dengan ajax atau route yang sesuai
                    alert("Fungsi kirim SATUSEHAT akan ditambahkan di tahap berikutnya 🚀");
                }
            });
        });

        // 🔄 reset filter
        function resetSearch() {
            var endDate = moment();
            var startDate = moment().subtract(30, 'days');

            $('#start_date').val(startDate.format('YYYY-MM-DD'));
            $('#end_date').val(endDate.format('YYYY-MM-DD'));
            $('input[name="search"]').val('');
            table.ajax.reload();
        }

        // 📦 filter by card
        function search(type) {
            $('input[name="search"]').val(type);
            table.ajax.reload();
        }

            function lihatObat(idTrans) {
                $('#modalObat').modal('show');
                $('#obatDetailContent').html(`<p class='text-center text-muted'>Memuat data obat...</p>`);

                $.ajax({
                    url: `{{ route('satusehat.medication-request.detail') }}`,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: idTrans // 🆕 kirim ID_TRANS
                    },
                    success: function (res) {
                        if (res.status === 'success') {
                            let html = `
                                                                        <table class="table table-sm table-bordered">
                                                                            <thead class="thead-light">
                                                                                <tr>
                                                                                    <th>No</th>
                                                                                    <th>Nama Obat</th>
                                                                                    <th>Signa</th>
                                                                                    <th>Keterangan</th>
                                                                                    <th>Jumlah</th>
                                                                                    <th>KFA Code</th>
                                                                                    <th>Nama KFA</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>`;

                            res.data.forEach((row, index) => {
                                html += `
                                                                            <tr>
                                                                                <td>${index + 1}</td>
                                                                                <td>${row.NAMA_OBAT ?? '-'}</td>
                                                                                <td>${row.SIGNA ?? '-'}</td>
                                                                                <td>${row.KET ?? '-'}</td>
                                                                                <td>${row.JUMLAH ?? '-'}</td>
                                                                                <td>
                                                                                ${
                                                                                    row.KD_BRG_KFA
                                                                                        ? (
                                                                                            row.KD_BRG_KFA === '000'
                                                                                                ? `<span class="badge badge-warning">Non Farmasi</span>`
                                                                                                : row.KD_BRG_KFA
                                                                                        )
                                                                                        : `<a href="/master_obat?kode=${row.KDBRG}" class="btn btn-sm btn-primary" target="_blank">
                                                                                                <i class='fa fa-link'></i> Mapping
                                                                                        </a>`
                                                                                }
                                                                            </td>
                                                                                <td>${row.NAMABRG_KFA ?? '-'}</td>
                                                                            </tr>`;
                            });

                            html += `</tbody></table>`;
                            $('#obatDetailContent').html(html);
                        } else {
                            $('#obatDetailContent').html(`<p class='text-danger text-center'>${res.message}</p>`);
                        }
                    },
                    error: function (err) {
                        $('#obatDetailContent').html(
                            `<p class='text-danger text-center'>Terjadi kesalahan saat memuat data obat.</p>`);
                        console.error(err);
                    }
                });
            }
        //function confirmKirim
        function confirmkirimSatusehat(idTrans) {
            if (!idTrans) return;

            swal({
                title: 'Kirim ke SATUSEHAT?',
                text: `Yakin ingin mengirim transaksi ${idTrans} ke SATUSEHAT?`,
                type: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, kirim',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33'
            }).then((result) => {
                if (result.value) {
                    console.log("Lanjut kirim ke SATUSEHAT");
                    kirimSatusehat(idTrans);
                    table.ajax.reload();
                } else {
                    console.log("Dibatalkan");
                    table.ajax.reload();
                }
            });
        }


        // 🚀 fungsi kirim ke SATUSEHAT
        function kirimSatusehat(idTrans, btn = null, showSwal = true) {
            return new Promise((resolve, reject) => {
                if (!idTrans) return reject('ID_TRANS kosong.');

                if (!btn && typeof event !== 'undefined' && event.currentTarget) {
                    btn = event.currentTarget;
                }

                const originalText = btn ? btn.innerHTML : null;

                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = `<i class='fas fa-spinner fa-spin'></i> Mengirim...`;
                }

                $.ajax({
                    url: '{{ route('satusehat.medication-request.prepsatusehat') }}',
                    type: 'GET',
                    data: { id_trans: idTrans },
                    success: function (res) {
                        if (res.status === 'success') {
                            console.log(`✅ ${idTrans} sukses dikirim`);

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
                            console.warn(`⚠️ ${idTrans} gagal:`, res.message);

                            if (showSwal) {
                                swal({
                                    title: 'Gagal Mengirim!',
                                    text: res.message || `Transaksi ${idTrans} gagal dikirim.`,
                                    type: 'warning',
                                    confirmButtonColor: '#f0ad4e'
                                });
                            }

                            resolve({ success: false, id: idTrans });
                        }
                    },
                    error: function (xhr) {
                        console.error(`❌ Error kirim ${idTrans}:`, xhr);

                        // Ambil pesan error dari API
                        let errMsg = 'Terjadi kesalahan saat mengirim data.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        } else if (xhr.responseText) {
                            errMsg = xhr.responseText.substring(0, 500);
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
                    },
                    complete: function () {
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                        }
                    }
                });
            });
        }



    </script>
@endpush
