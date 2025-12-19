@extends('layouts.app')

@push('before-style')
    <!-- Existing CSS links -->
    <!-- ... -->
    <link href="{{ asset('assets/plugins/chartist-js/dist/chartist.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/chartist-js/dist/chartist-init.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.css') }}"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/icons/font-awesome/css/fontawesome-all.min.css') }}" />

    <style>
        /* Existing styles */
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
            cursor: pointer
        }

        tbody td {
            vertical-align: middle !important
        }
    </style>
    <link href="{{ asset('assets/plugins/bootstrap-material-datetimepicker/css/bootstrap-material-datetimepicker.css') }}"
        rel="stylesheet">
@endpush

@push('after-style')
    <link href="{{ asset('assets/plugins/select2/dist/css/select2.min.css') }}" rel="stylesheet" />
    <style>
        .icon-circle {
            width: 55px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .ui-autocomplete-loading {
            background: white url("/assets/images/animated_loading.gif") right center no-repeat;
            background-repeat: no-repeat;
            background-position: center right calc(.375em + .1875rem);
            padding-right: calc(1.5em + 0.75rem);
        }

        /* Biar select2 di dalam modal dan form-group tetap rapi */
        .select2-container {
            width: 100% !important;
        }

        /* Biar area input Select2 multiple bisa diketik lebar penuh */
        .select2-container--classic .select2-selection--multiple .select2-search--inline .select2-search__field {
            width: 100% !important;
        }

        /* Sedikit perbaikan tampilan biar selaras dengan form Bootstrap */
        .select2-container--classic .select2-selection--multiple {
            border: 1px solid #ced4da;
            min-height: calc(1.5em + .75rem + 2px);
            padding: .375rem .75rem;
            border-radius: .25rem;
        }
    </style>
@endpush

@section('content')
    <div class="row page-titles">
        <!-- Existing content -->
        <div class="col-md-5 col-8 align-self-center">
            <h3 class="text-themecolor">Dashboard</h3>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Observation</li>
            </ol>
        </div>
        <div class="col-md-7 col-4 align-self-center">
            <div class="d-flex m-t-10 justify-content-end">
                <h6>Selamat Datang <p><b>{{ Session::get('user') }}</b></p>
                </h6>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Daftar Kunjungan Pasien</h4>

            <div class="card">
                <div class="card-body">
                    <div class="card-title">
                        {{-- <h4>Form Filter</h4> --}}
                    </div>

                    <form action="javascript:void(0)" id="search-data" class="m-t-40">
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <div class="row justify-content-center">
                            <div class="col-6">
                                <div class="card card-inverse card-info card-mapping" onclick="search('all')">
                                    <div class="card-body">
                                        <div class="card-title">
                                            <div class="row align-items-center ml-1">
                                                <i class="fas fa-hospital" style="font-size: 48px"></i>
                                                <div class="ml-3">
                                                    <span style="font-size: 24px"
                                                        id="total_rawat_jalan">{{ $result['total_rawat_jalan'] }}</span>
                                                    <h4 class="text-white">Total Rawat Jalan</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card card-inverse card-warning card-mapping" onclick="search('all')">
                                    <div class="card-body">
                                        <div class="card-title">
                                            <div class="row align-items-center ml-1">
                                                <i class="fas fa-bed" style="font-size: 48px"></i>
                                                <div class="ml-3">
                                                    <span style="font-size: 24px"
                                                        id="total_rawat_inap">{{ $result['total_rawat_inap'] }}</span>
                                                    <h4 class="text-white">Total Rawat Inap</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card card-inverse card-primary card-mapping" onclick="search('all')">
                                    <div class="card-body">
                                        <div class="card-title">
                                            <div class="row align-items-center ml-1">
                                                <i class="fas fa-info-circle" style="font-size: 48px"></i>
                                                <div class="ml-3">
                                                    <span style="font-size: 24px"
                                                        id="total_all">{{ $result['total_semua'] }}</span>
                                                    <h4 class="text-white">Total Data</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card card-inverse card-info card-mapping" onclick="search('mapped')">
                                    <div class="card-body">
                                        <div class="card-title">
                                            <div class="row align-items-center ml-1">
                                                <i class="fas fa-link" style="font-size: 48px"></i>
                                                <div class="ml-3">
                                                    <span style="font-size: 24px"
                                                        id="total_integrasi">{{ $result['total_sudah_integrasi'] }}</span>
                                                    <h4 class="text-white">Data Terintegrasi</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card card-inverse card-danger card-mapping" onclick="search('unmapped')">
                                    <div class="card-body">
                                        <div class="card-title">
                                            <div class="row align-items-center ml-1">
                                                <i class="fas fa-unlink" style="font-size: 48px"></i>
                                                <div class="ml-3">
                                                    <span style="font-size: 24px"
                                                        id="total_belum_integrasi">{{ $result['total_belum_integrasi'] }}</span>
                                                    <h4 class="text-white">Data belum terintegrasi</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-12">
                                <div class="form-group">
                                    <div class="row justify-content-center align-items-end">
                                        <div class="col-5">
                                            <label for="start_date">Periode Tanggal Kunjungan</label>
                                            <input type="text" class="form-control" name="tgl_awal" id="start_date">
                                            <span class="bar"></span>
                                        </div>
                                        <div class="col-2 text-center">
                                            <label>&nbsp;</label>
                                            <small>-</small>
                                        </div>
                                        <div class="col-5">
                                            <label for="end_date">&nbsp;</label>
                                            <input type="text" class="form-control" name="tgl_akhir" id="end_date">
                                            <span class="bar"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-success btn-rounded mr-3" onclick="resetSearch()">
                                Reset Pencarian
                                <i class="mdi mdi-refresh"></i>
                            </button>
                            <button type="submit" class="btn btn-rounded btn-info">
                                Cari Data
                                <i class="mdi mdi-magnify"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card" id="data-section">
                <div class="card-body">
                    <div class="row align-items-center justify-content-between m-1">
                        <div class="card-title">
                            <h4>Data Kunjungan Pasien</h4>
                        </div>

                        <button type="button" class="btn btn-warning btn-rounded" onclick="bulkSend()"
                            id="bulk-send-btn">
                            <i class="mdi mdi-send-outline"></i>
                            Kirim Terpilih ke SatuSehat
                        </button>
                    </div>
                    <!-- 🧾 Tabel Data -->
                    <div class="table-responsive">
                        <table class="display nowrap table data-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>#</th>
                                    <th>
                                        <input type="checkbox" id="selectAll" value="selected-all"
                                            class="chk-col-purple" />
                                        <label for="selectAll"
                                            style="margin-bottom: 0px !important; line-height: 25px !important; font-weight: 500">
                                            Select All </label>
                                    </th>
                                    <th>Perawatan</th>
                                    <th>Karcis</th>
                                    <th>Tgl</th>
                                    <th>No. Peserta</th>
                                    <th>No. RM</th>
                                    <th>Nama</th>
                                    <th>Dokter</th>
                                    <th>Status Integrasi</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('modals.modal_observasi')
@endsection

@push('after-script')
    <script src="{{ asset('assets/plugins/moment/moment.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js') }}">
    </script>
    <script src="{{ asset('assets/plugins/select2/dist/js/select2.min.js') }}"></script>
    <script>
        var table
        let selectedIds = [];
        let selectedJp = [];
        var paramSatuSehat = '';
        $(function() {
            // format tanggal sesuai dengan setting datepicker
            const today = moment().format('YYYY-MM-DD');
            $("#start_date").bootstrapMaterialDatePicker({
                weekStart: 0,
                time: false,
            });
            $("#end_date").bootstrapMaterialDatePicker({
                weekStart: 0,
                time: false,
            });

            // Set default value ke hari ini
            $('#start_date').val(today);
            $('#end_date').val(today);

            getAllData()

            $('#bulk-send-btn').prop('disabled', true);

            $("#search-data").on("submit", function(e) {
                if (this.checkValidity()) {
                    e.preventDefault();
                    $("html, body").animate({
                            scrollTop: $("#data-section").offset().top,
                        },
                        1250
                    );

                    selectedIds = [];
                    updateSelectAllCheckbox();

                    table.ajax.reload();
                }

                $(this).addClass("was-validated");
            });

            $('.data-table').on('click', 'button, a', function(e) {
                e.stopPropagation();
            });
        })

        function resetSearch() {
            $("#search-data").find("input.form-control").val("").trigger("blur");
            $("#search-data").find("input.form-control").removeClass("was-validated");
            $('input[name="search"]').val("false");

            selectedIds = [];
            updateSelectAllCheckbox();

            table.ajax.reload();
        }

        function getAllData() {
            table = $('.data-table').DataTable({
                responsive: {
                    details: {
                        type: 'column',
                        target: 'td.dtr-control'
                    }
                },
                processing: true,
                serverSide: false,
                scrollX: false,
                ajax: {
                    url: `{{ route('satusehat.observasi.datatable') }}`,
                    method: "POST",
                    data: function(data) {
                        data._token = `${$('meta[name="csrf-token"]').attr("content")}`;
                        data.cari = $('input[name="search"]').val();
                        data.tgl_awal = $('input[name="tgl_awal"]').val();
                        data.tgl_akhir = $('input[name="tgl_akhir"]').val();
                    },
                    dataSrc: function(json) {
                        $('#total_all').text(json.total_semua);
                        $('#total_integrasi').text(json.total_sudah_integrasi);
                        $('#total_belum_integrasi').text(json.total_belum_integrasi);
                        $('#total_rawat_jalan').text(json.total_rawat_jalan);
                        $('#total_rawat_inap').text(json.total_rawat_inap);
                        return json.data;
                    }
                },
                columns: [{
                        className: 'dtr-control',
                        orderable: false,
                        searchable: false,
                        data: null,
                        defaultContent: ''
                    },
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        searchable: false,
                        responsivePriority: 1
                    },
                    {
                        data: 'checkbox',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        responsivePriority: 1
                    },
                    {
                        data: 'JENIS_PERAWATAN',
                        name: 'JENIS_PERAWATAN',
                        responsivePriority: -1
                    },
                    {
                        data: 'KARCIS',
                        name: 'KARCIS',
                        responsivePriority: 2
                    },
                    {
                        data: 'TANGGAL',
                        name: 'TANGGAL',
                        responsivePriority: 3
                    },
                    {
                        data: 'NO_PESERTA',
                        name: 'NO_PESERTA',
                        responsivePriority: 6
                    },
                    {
                        data: 'KBUKU',
                        name: 'KBUKU',
                        responsivePriority: 5
                    },
                    {
                        data: 'NAMA_PASIEN',
                        name: 'NAMA_PASIEN',
                        responsivePriority: 4
                    },
                    {
                        data: 'DOKTER',
                        name: 'DOKTER',
                        responsivePriority: 8
                    },
                    {
                        data: 'status_integrasi',
                        name: 'status_integrasi',
                        responsivePriority: 3
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        responsivePriority: 1
                    },
                ],
                order: [
                    [4, 'desc']
                ],
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ],
                pageLength: 10,
                drawCallback: function(settings) {
                    $('.select-row').each(function() {
                        const id = $(this).val();
                        $(this).prop('checked', selectedIds.includes(id));
                    });

                    if ($('#selectAll').is(':checked')) {
                        $('.select-row').each(function() {
                            const id = $(this).val();
                            const param = $(this).data('param');
                            $(this).prop('checked', true);
                            if (!selectedIds.includes(id)) {
                                selectedIds.push({
                                    id: id,
                                    param: param
                                });
                            }
                        });
                    }

                    updateSelectAllCheckbox();
                }
            })
        }

        // Select single row
        $(document).on('change', '.select-row', function(e) {
            e.stopPropagation();
            const id = $(this).val();
            const param = $(this).data('param');

            if ($(this).is(':checked')) {
                if (!selectedIds.some(item => item.id === id)) {
                    selectedIds.push({
                        id: id,
                        param: param
                    });
                }
            } else {
                selectedIds = selectedIds.filter(item => item.id !== id);
            }
            updateSelectAllCheckbox();
        });

        $(document).on('click', '.select-row', function(e) {
            e.stopPropagation();
        });

        // Select All (current page)
        $('#selectAll').on('click', function(e) {
            e.stopPropagation();
            const rows = $('.select-row');
            const checked = this.checked;
            rows.each(function() {
                const id = $(this).val();
                const param = $(this).data('param');
                $(this).prop('checked', checked);

                if (checked) {
                    if (!selectedIds.some(item => item.id === id)) {
                        selectedIds.push({
                            id: id,
                            param: param
                        });
                    }
                } else {
                    selectedIds = selectedIds.filter(item => item.id !== id);
                }

                updateSelectAllCheckbox();
            });
        });

        function updateSelectAllCheckbox() {
            const totalCheckboxes = $('.select-row').length;
            const checkedCount = $('.select-row:checked').length;

            // centang setengah (indeterminate) kalau sebagian terpilih
            $('#selectAll').prop('checked', checkedCount === totalCheckboxes && totalCheckboxes > 0);
            $('#selectAll').prop('indeterminate', checkedCount > 0 && checkedCount < totalCheckboxes);

            // Update bulk send button state
            const bulkSendBtn = $('#bulk-send-btn');
            if (selectedIds.length > 0) {
                bulkSendBtn.prop('disabled', false);
                bulkSendBtn.html(`<i class="mdi mdi-send-outline"></i> Kirim ${selectedIds.length} Data ke SatuSehat`);
            } else {
                bulkSendBtn.prop('disabled', true);
                bulkSendBtn.html('<i class="mdi mdi-send-outline"></i> Kirim Terpilih ke SatuSehat');
            }
        }

        $('.data-table').on('click', 'button, a', function(e) {
            e.stopPropagation();
        });

        function search(type) {
            $('input[name="search"]').val(type)
            table.ajax.reload()
        }

        function lihatDetail(param, paramSS) {
            paramSatuSehat = paramSS
            ajaxGetJson(
                `{{ route('satusehat.observasi.lihat-detail', '') }}/${btoa(param)}`,
                "show_modal",
                ""
            );
        }

        function show_modal(res) {
            const dataPasien = res.data.dataPasien
            const dataErm = res.data.dataErm

            if (dataErm.jenis_perawatan == 'RJ') {
                $('#integrasi_anamnese').hide()
                $('#success_anamnese').hide()
                $('#failed_anamnese').hide()

                $('#nama_pasien').html(dataPasien.NAMA)
                $('#no_rm').html(dataPasien.KBUKU)
                $('#no_peserta').html(dataPasien.NO_PESERTA)

                $('#no_karcis').html(dataErm.KARCIS)
                $('#dokter').html(dataErm.CRTUSR)

                let htmlDiag = '';

                if (dataErm) {
                    htmlDiag += `<span>${dataErm.KODE_DIAGNOSA_UTAMA || '-'} - ${dataErm.DIAG_UTAMA || '-'}</span>`;

                    if (dataErm.KODE_DIAGNOSA_SEKUNDER || dataErm.DIAG_SEKUNDER) {
                        htmlDiag += `<br><span>${dataErm.KODE_DIAGNOSA_SEKUNDER} - ${dataErm.DIAG_SEKUNDER}</span>`;
                    }

                    if (dataErm.KODE_DIAGNOSA_KOMPLIKASI || dataErm.DIAG_KOMPLIKASI) {
                        htmlDiag += `<br><span>${dataErm.KODE_DIAGNOSA_KOMPLIKASI} - ${dataErm.DIAG_KOMPLIKASI}</span>`;
                    }

                    if (dataErm.KODE_DIAGNOSA_PENYEBAB || dataErm.PENYEBAB) {
                        htmlDiag += `<br><span>${dataErm.KODE_DIAGNOSA_PENYEBAB} - ${dataErm.PENYEBAB}</span>`;
                    }

                    $.each(dataErm, function(key, value) {
                        console.log(key, value)
                        const $el = $('#pemeriksaan_fisik #' + key);
                        if ($el.length) {
                            $el.text(value ? value : '-');
                        }
                    });

                    const tglRaw = dataErm.CRTDT;
                    if (tglRaw) {
                        const tgl = new Date(tglRaw);
                        const formatted = tgl.toLocaleDateString('id-ID', {
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric'
                        });
                        $('#pemeriksaan_fisik #TANGGAL').text(formatted);
                    }

                    hitungIMT(dataErm.TB, dataErm.BB)
                } else {
                    htmlDiag = `<em>Tidak ada data diagnosa</em>`;
                }
                $('#data_diagnosa').html(htmlDiag)

                // Sudah Kirim Pemeriksaan Fisik
                if (dataErm.sudah_integrasi > 0) {
                    $('#integrasi_anamnese').show()
                    $('#success_anamnese').show()
                } else {
                    $('#btn-simpan-pemeriksaanfisik').show();
                    $('#failed_anamnese').show()
                }

                $('#modalObservasi').modal('show')
            } else {
                $('#integrasi_anamnese_ri').hide()
                $('#success_anamnese_ri').hide()
                $('#failed_anamnese_ri').hide()

                $('#nama_pasien_ri').html(dataPasien.NAMA)
                $('#no_rm_ri').html(dataPasien.KBUKU)
                $('#no_peserta_ri').html(dataPasien.NO_PESERTA)

                $('#no_karcis_ri').html(dataErm.KARCIS)
                $('#dokter_ri').html(dataErm.CRTUSR)

                $.each(dataErm, function(key, value) {
                    console.log(key, value)
                    const $el = $('#pemeriksaan_fisik_ri #' + key + '_ri');
                    if ($el.length) {
                        $el.text(value ? value : '-');
                    }
                });

                const tglRaw = dataErm.CRTDT;
                if (tglRaw) {
                    const tgl = new Date(tglRaw);
                    const formatted = tgl.toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                    });
                    $('#pemeriksaan_fisik_ri #TANGGAL_ri').text(formatted);
                }

                hitungIMT(dataErm.TB, dataErm.BB)

                // Sudah Kirim Pemeriksaan Fisik
                if (dataErm.sudah_integrasi > 0) {
                    $('#integrasi_anamnese_ri').show()
                    $('#success_anamnese_ri').show()
                } else {
                    $('#btn-simpan-pemeriksaanfisik').show();
                    $('#failed_anamnese_ri').show()
                }
                $('#modalObservasiRanap').modal('show')
            }
        }

        function hitungIMT(tinggi, berat) {
            const imtInput = $('#IMT');
            const imtInputRi = $('#IMT_ri');

            if (tinggi > 0 && berat > 0) {
                const tinggiMeter = tinggi / 100; // ubah cm ke meter
                const imt = berat / (tinggiMeter * tinggiMeter);
                let kategori = '';

                // Tentukan kategori IMT
                if (imt < 18.5) kategori = 'Kurus';
                else if (imt < 25) kategori = 'Normal';
                else if (imt < 30) kategori = 'Berat badan berlebih';
                else kategori = 'Obesitas';

                $('#IMT').text(`${imt.toFixed(1)} (${kategori})`);
                $('#IMT_ri').text(`${imt.toFixed(1)} (${kategori})`);
            } else {
                $('#IMT').text(``);
                $('#IMT_ri').text(``);
            }
        }

        function sendSatuSehat(param) {
            Swal.fire({
                title: "Konfirmasi Pengiriman",
                text: `Kirim Data Observasi Pasien ke SatuSehat?`,
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Ya, kirim!",
                cancelButtonText: "Batal",
            }).then(async (conf) => {
                if (conf.value || conf.isConfirmed) {
                    await ajaxGetJson(
                        `{{ route('satusehat.observasi.send', '') }}/${btoa(param)}`,
                        "input_success",
                        "",
                    );
                }
            });
        }

        function resendSatuSehat(param) {
            Swal.fire({
                title: "Konfirmasi Pengiriman Ulang",
                text: `Kirim Ulang Data Observasi Pasien ke SatuSehat?`,
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Ya, kirim!",
                cancelButtonText: "Batal",
            }).then(async (conf) => {
                if (conf.value || conf.isConfirmed) {
                    await ajaxGetJson(
                        `{{ route('satusehat.observasi.resend', '') }}/${btoa(param)}`,
                        "input_success",
                        "",
                    );
                }
            });
        }

        function bulkSend() {
            if (selectedIds.length === 0) {
                $.toast({
                    heading: "Peringatan!",
                    text: "Pilih data yang akan dikirim terlebih dahulu.",
                    position: "top-right",
                    icon: "warning",
                    hideAfter: 3000
                });
                return;
            }

            Swal.fire({
                title: "Konfirmasi Bulk Send",
                text: `Kirim ${selectedIds.length} data Observasi ke SatuSehat?`,
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Ya, kirim semua!",
                cancelButtonText: "Batal",
            }).then(async (result) => {
                if (result.value) {
                    await ajaxPostJson(`{{ route('satusehat.observasi.bulk-send') }}`, {
                        _token: $('meta[name="csrf-token"]').attr("content"),
                        selected_ids: selectedIds
                    }, "input_success", "");
                }
            });
        }

        function input_success(res) {
            if (res.status != 200) {
                input_error(res);
                return false;
            }

            $.toast({
                heading: "Berhasil!",
                text: res.message,
                position: "top-right",
                icon: "success",
                hideAfter: 2500,
                beforeHide: function() {
                    let text = "";
                    if (res.redirect.need) {
                        text =
                            "<h5>Berhasil Kirim Data,<br> Mengembalikan Anda ke halaman sebelumnya...</h5>";
                    } else {
                        text = "<h5>Berhasil Kirim Data</h5>";
                    }

                    Swal.fire({
                        html: text,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                    });

                    Swal.showLoading();
                },
                afterHidden: function() {
                    if (res.redirect.need) {
                        window.location.href = res.redirect.to;
                    } else {
                        Swal.close();
                        table.ajax.reload()
                    }
                },
            });
        }

        function input_error(err) {
            console.log(err);
            $.toast({
                heading: "Gagal memproses data!",
                text: err.message,
                position: "top-right",
                icon: "error",
                hideAfter: 5000,
            });
        }
    </script>
@endpush
