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


                    <!-- Filter Periode -->
                    <div class="col-12 col-md-12 mt-4">
                        <div class="form-group">
                            <div class="row justify-content-center align-items-end">
                                <div class="col-5">
                                    <label for="start_date">Periode Tanggal Transaksi</label>
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
                <table id="medicationTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>IDTRANS</th>
                            <th>KARCIS</th>
                            <th>Dokter</th>
                            <th>Pasien</th>
                            <th>Tanggal</th>
                            <th>Lihat Obat</th>
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
            var startDate = moment().subtract(240, 'days');

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
                        d.search = $('input[name="search"]').val();
                    }
                },
                columns: [{
                    data: 'ID_TRANS',
                    name: 'H.ID_TRANS'
                },
                {
                    data: 'KARCIS',
                    name: 'K.KARCIS'
                },
                {
                    data: 'DOKTER',
                    name: 'N.nama'
                },
                {
                    data: 'PASIEN',
                    name: 'P.nama'
                },
                {
                    data: 'TGL_KARCIS',
                    name: 'A.TGL_KARCIS'
                },
                {
                    data: null,
                    orderable: true,
                    searchable: true,
                    render: function (data) {
                        return `
                                    <button class="btn btn-sm btn-info" onclick="lihatObat('${data.ID_TRANS}')">
                                        <i class='fas fa-eye'></i> Lihat Obat
                                    </button>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        if (data.STATUS_MAPPING === '100') {
                            return `
                                        <button class="btn btn-sm btn-primary w-100" onclick="kirimSatusehat('${data.ID_TRANS}')">
                                            <i class='fas fa-link mr-2'></i> Kirim SATUSEHAT
                                        </button>`;
                        } else if (data.STATUS_MAPPING === '200') {
                            return `<i class="text-success">Data sudah dikirim ke SATUSEHAT</i>`;
                        } else {
                            return `<i class="text-muted">Data obat belum termapping</i>`;
                        }
                    }
                }
                ],
                order: [
                    [0, 'desc']
                ]
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
                url: '{{ route('satusehat.medication-request.detail') }}',
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
                                    <td>${row.KD_BRG_KFA ? row.KD_BRG_KFA : '<strong>Kode KFA Belum Termapping</strong>'}</td>
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
        // 🚀 fungsi kirim ke SATUSEHAT
        function kirimSatusehat(idTrans) {
            if (!idTrans) return;

            if (!confirm(`Yakin ingin mengirim transaksi ${idTrans} ke SATUSEHAT?`)) {
                return;
            }

            // ambil tombol yang diklik (buat ubah statusnya sementara)
            const btn = event.currentTarget;
            const originalText = btn.innerHTML;

            // ubah tampilan tombol -> loading
            btn.disabled = true;
            btn.innerHTML = `<i class='fas fa-spinner fa-spin'></i> Mengirim...`;

            $.ajax({
                url: '{{ route('satusehat.medication-request.sendsehat') }}',
                type: 'GET', // karena route kamu pakai GET
                data: {
                    id_trans: idTrans
                },
                success: function (res) {
                    // tampilkan notifikasi / hasilnya
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: `Transaksi ${idTrans} berhasil dikirim ke SATUSEHAT.`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Gagal!',
                            text: res.message ?? 'Tidak ada pesan dari server.',
                        });
                    }

                    // reload DataTable biar status jadi 200
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    console.error(xhr);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Terjadi kesalahan saat mengirim data ke SATUSEHAT.',
                    });
                },
                complete: function () {
                    // kembalikan tombol ke kondisi semula
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            });
        }

    </script>
@endpush