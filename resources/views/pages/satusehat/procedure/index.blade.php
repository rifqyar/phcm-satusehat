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
                <li class="breadcrumb-item active">Procedure</li>
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

                        <button type="button" class="btn btn-primary btn-rounded mr-3" onclick="sendBundle()">
                            Kirim Bundling
                            <i class="mdi mdi-cube-send"></i>
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

    @include('modals.modal_procedure')
@endsection

@push('after-script')
    <script src="{{ asset('assets/plugins/moment/moment.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js') }}">
    </script>
    <script src="{{ asset('assets/plugins/select2/dist/js/select2.min.js') }}"></script>
    <script>
        var table
        let selectedIds = [];
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

            $("#search-data").on("submit", function(e) {
                if (this.checkValidity()) {
                    e.preventDefault();
                    $("html, body").animate({
                            scrollTop: $("#data-section").offset().top,
                        },
                        1250
                    );
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
                    url: `{{ route('satusehat.procedure.datatable') }}`,
                    method: "POST",
                    data: function(data) {
                        data._token = `${$('meta[name="csrf-token"]').attr("content")}`;
                        data.cari = $('input[name="search"]').val();
                        data.tgl_awal = $('input[name="tgl_awal"]').val();
                        data.tgl_akhir = $('input[name="tgl_akhir"]').val();
                    },
                    dataSrc: function(json) {
                        console.log(json)
                        $('#total_all').text(json.total_semua);
                        $('#total_integrasi').text(json.total_sudah_integrasi);
                        $('#total_belum_integrasi').text(json.total_belum_integrasi);
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
                            $(this).prop('checked', true);
                            if (!selectedIds.includes(id)) selectedIds.push(id);
                        });
                    }

                    updateSelectAllCheckbox();
                }
            })
        }

        // Select single row
        $(document).on('change', '.select-row', function() {
            const id = $(this).val();
            if ($(this).is(':checked')) {
                if (!selectedIds.includes(id)) selectedIds.push(id);
            } else {
                selectedIds = selectedIds.filter(item => item !== id);
            }
            updateSelectAllCheckbox();
        });

        // Select All (current page)
        $('#selectAll').on('click', function() {
            const rows = $('.select-row');
            const checked = this.checked;
            rows.each(function() {
                const id = $(this).val();
                $(this).prop('checked', checked);

                if (checked) {
                    if (!selectedIds.includes(id)) selectedIds.push(id);
                } else {
                    selectedIds = selectedIds.filter(item => item !== id);
                }
            });
        });

        // Update status checkbox selectAll
        function updateSelectAllCheckbox() {
            const totalCheckboxes = $('.select-row').length;
            const checkedCount = $('.select-row:checked').length;

            // centang setengah (indeterminate) kalau sebagian terpilih
            $('#selectAll').prop('checked', checkedCount === totalCheckboxes && totalCheckboxes > 0);
            $('#selectAll').prop('indeterminate', checkedCount > 0 && checkedCount < totalCheckboxes);
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
                `{{ route('satusehat.procedure.lihat-detail', '') }}/${btoa(param)}`,
                "show_modal",
                ""
            );
        }

        function sendBundle() {
            Swal.fire({
                title: "Konfirmasi Pengiriman Bundling",
                text: `Kirim data yang anda pilih ke SatuSehat?`,
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Ya, kirim!",
                cancelButtonText: "Batal",
            }).then(async (conf) => {
                if (conf.value || conf.isConfirmed) {
                    let formData = new FormData()
                    formData.append('_token', $('meta[name="csrf-token"]').attr('content'))

                    if ($('#selectAll').is(":checked")) {
                        formData.append('selectAll', true)
                    }

                    for (let i = 0; i < selectedIds.length; i++) {
                        const val = selectedIds[i]
                        formData.append('karcis[]', val)
                    }

                    ajaxPostFile(
                        `{{ route('satusehat.allergy-intolerance.send-bulking') }}`,
                        formData,
                        "input_success"
                    )
                }
            });
        }

        function show_modal(res) {
            const dataPasien = res.data.dataPasien
            const dataErm = res.data.dataErm
            const dataLab = res.data.dataLab
            const dataRad = res.data.dataRad
            const tindakanLab = res.data.tindakanLab
            const tindakanRad = res.data.tindakanRad
            const tindakanOp = res.data.tindakanOp

            $('#nama_pasien').html(dataPasien.NAMA)
            $('#no_rm').html(dataPasien.KBUKU)
            $('#no_peserta').html(dataPasien.NO_PESERTA)

            $('#no_karcis').html(dataErm.ID_TRANSAKSI)
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

            if (dataLab.length > 0) {
                $('#icd9-lab').prop('required', true)
                var tglLab = dataLab[0].TANGGAL_ENTRI;
                if (tglLab) {
                    const tgl = new Date(tglLab);
                    const formatted = tgl.toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                    });
                    $('#TANGGAL_LAB').text(formatted);
                }

                $('#tabel_tindakan_lab').empty();
                $.each(tindakanLab, function(index, item) {
                    $('#tabel_tindakan_lab').append(`
                        <tr>
                            <td>${index + 1}</td>
                            <td>${item.KD_TIND || '-'}</td>
                            <td>${item.NM_TIND || '-'}</td>
                        </tr>
                    `);
                });
            } else {
                $('#icd9-lab').prop('required', false)
            }

            if (dataRad.length > 0) {
                $('#icd9-rad').prop('required', true)
                var tgRad = dataRad[0].TANGGAL_ENTRI;
                if (tgRad) {
                    const tgl = new Date(tgRad);
                    const formatted = tgl.toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                    });
                    $('#TANGGAL_RAD').text(formatted);
                }

                $('#tabel_tindakan_rad').empty();
                $.each(tindakanRad, function(index, item) {
                    $('#tabel_tindakan_rad').append(`
                        <tr>
                            <td>${index + 1}</td>
                            <td>${item.KD_TIND || '-'}</td>
                            <td>${item.NM_TIND || '-'}</td>
                        </tr>
                    `);
                });
            } else {
                $('#icd9-rad').prop('required', false)
            }

            if (tindakanOp.length > 0) {
                $('#icd9-operasi').prop('required', true)
                var tglOP = tindakanOp[0].tanggal_operasi
                if (tglOP) {
                    const tgl = new Date(tglOP);
                    const formatted = tgl.toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                    });
                    $('#TANGGAL_OPERASI').text(formatted);
                }

                $('#tabel_tindakan_operasi').empty();
                $('#laporan_operasi').html(tindakanOp[0].laporan_operasi)
                $.each(tindakanOp, function(index, item) {
                    $('#tabel_tindakan_operasi').append(`
                        <tr>
                            <td>${index + 1}</td>
                            <td>${item.tind_operasi || '-'}</td>
                            <td>${item.diag_pre_operasi || '-'}</td>
                            <td>${item.diag_post_operasi || '-'}</td>
                            <td>${item.nmdok || '-'}</td>
                            <td>${item.perawat || '-'}</td>
                        </tr>
                    `);
                });
            } else {
                $('#icd9-operasi').prop('required', false)
            }

            $('#modalProcedure').modal('show')
        }

        function hitungIMT(tinggi, berat) {
            const imtInput = $('#IMT');

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
            } else {
                $('#IMT').text(``);
            }
        }

        var cacheIcd9 = {};
        $("#icd9-pemeriksaanfisik").autocomplete({
            minLength: 2,
            delay: 300,
            appendTo: "#modalProcedure",
            source: function(request, response) {
                var term = request.term;
                if (term in cacheIcd9) {
                    response(cacheIcd9[term]);
                    return;
                }

                $.ajax({
                    url: `{{ route('satusehat.procedure.geticd9') }}`,
                    type: "GET",
                    dataType: "json",
                    data: {
                        search: request.term,
                    },
                    success: function(data) {
                        response(
                            data.map(function(value) {
                                return {
                                    label: value.DIAGNOSA,
                                    kd_icd: value.KODE,
                                    kd_sub_icd: value.KODE_SUB,
                                };
                            })
                        );
                    },
                });
            },
            select: function(event, ui) {
                $("#icd9-pemeriksaanfisik").val(ui.item.label);
                $("#kd_icd_pm").val(ui.item.kd_icd);
                $("#sub_kd_icd_pm").val(ui.item.kd_sub_icd);
                return false;
            },
        });

        $('#icd9-lab').select2({
            width: '100%',
            theme: "classic",
            placeholder: 'Cari kode ICD-9...',
            minimumInputLength: 2,
            ajax: {
                url: `{{ route('satusehat.procedure.geticd9') }}`,
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return {
                        search: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.map(function(value) {
                            return {
                                id: value.KODE_SUB,
                                text: value.DIAGNOSA,
                            };
                        })
                    };
                },
                cache: true
            }
        });

        $('#icd9-rad').select2({
            width: '100%',
            theme: "classic",
            placeholder: 'Cari kode ICD-9...',
            minimumInputLength: 2,
            ajax: {
                url: `{{ route('satusehat.procedure.geticd9') }}`,
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return {
                        search: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.map(function(value) {
                            return {
                                id: value.KODE_SUB,
                                text: value.DIAGNOSA,
                            };
                        })
                    };
                },
                cache: true
            }
        });

        $('#icd9-operasi').select2({
            width: '100%',
            theme: "classic",
            placeholder: 'Cari kode ICD-9...',
            minimumInputLength: 2,
            ajax: {
                url: `{{ route('satusehat.procedure.geticd9') }}`,
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return {
                        search: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.map(function(value) {
                            return {
                                id: value.KODE_SUB,
                                text: value.DIAGNOSA,
                            };
                        })
                    };
                },
                cache: true
            }
        });

        $('#btn-send-satusehat').on('click', function() {
            if (paramSatuSehat != '') {
                sendSatuSehat(paramSatuSehat)
            }
        })

        function sendSatuSehat(param) {
            const icd9Lab = $('#icd9-lab').val();
            const icd9Rad = $('#icd9-rad').val();
            const icd9Operasi = $('#icd9-operasi').val();

            if ($('#kd_icd_pm').val() == '') {
                $.toast({
                    heading: "Kode ICD-9 belum diisi",
                    text: 'Harap Masukan Kode Tindakan ICD-9CM untuk Pemeriksaan Fisik',
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "error",
                    hideAfter: 3500,
                });
            } else if ($('#icd9-lab').prop('required') && (!icd9Lab || icd9Lab.length === 0)) {
                $.toast({
                    heading: "Kode ICD-9 belum diisi",
                    text: 'Harap masukkan Kode Tindakan ICD-9CM untuk tindakan Laboratorium',
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "error",
                    hideAfter: 3500,
                });
            } else if ($('#icd9-rad').prop('required') && (!icd9Rad || icd9Rad.length === 0)) {
                $.toast({
                    heading: "Kode ICD-9 belum diisi",
                    text: 'Harap masukkan Kode Tindakan ICD-9CM untuk tindakan Radiologi',
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "error",
                    hideAfter: 3500,
                });
            } else if ($('#icd9-operasi').prop('required') && (!icd9Operasi || icd9Operasi.length === 0)) {
                $.toast({
                    heading: "Kode ICD-9 belum diisi",
                    text: 'Harap masukkan Kode Tindakan ICD-9CM untuk tindakan Operasi',
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "error",
                    hideAfter: 3500,
                });
            } else {
                var formData = new FormData()
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'))
                formData.append('param', param)
                formData.append('icd9_pm', $('input[name="sub_kd_icd_pm"]').val())
                formData.append('text_icd9_pm', $('input[name="icd9-pemeriksaanfisik"]').val())

                let icd9LabValues = $('#icd9-lab').val() || []; // array of kode ICD
                let icd9LabTexts = $('#icd9-lab').select2('data').map(item => item.text);

                let icd9RadValues = $('#icd9-rad').val() || [];
                let icd9RadTexts = $('#icd9-rad').select2('data').map(item => item.text);

                let icd9OpValues = $('#icd9-operasi').val() || [];
                let icd9OpTexts = $('#icd9-operasi').select2('data').map(item => item.text);

                formData.append('icd9_lab', JSON.stringify(icd9LabValues));
                formData.append('text_icd9_lab', JSON.stringify(icd9LabTexts));

                formData.append('icd9_rad', JSON.stringify(icd9RadValues));
                formData.append('text_icd9_rad', JSON.stringify(icd9RadTexts));

                formData.append('icd9_op', JSON.stringify(icd9OpValues));
                formData.append('text_icd9_op', JSON.stringify(icd9OpTexts));


                Swal.fire({
                    title: "Konfirmasi Pengiriman",
                    text: `Kirim data Semua Tindakan Pasien ke SatuSehat?`,
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Ya, kirim!",
                    cancelButtonText: "Batal",
                }).then(async (conf) => {
                    if (conf.value || conf.isConfirmed) {
                        await ajaxPostFile(
                            `{{ route('satusehat.procedure.send') }}`,
                            formData,
                            "input_success",
                        );
                    }
                });
            }
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
