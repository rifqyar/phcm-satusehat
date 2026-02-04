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
    </style>
    <link href="{{ asset('assets/plugins/bootstrap-material-datetimepicker/css/bootstrap-material-datetimepicker.css') }}"
        rel="stylesheet">
@endpush

@section('content')
    <div class="row page-titles">
        <!-- Existing content -->
        <div class="col-md-5 col-8 align-self-center">
            <h3 class="text-themecolor">Dashboard</h3>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Episode Of Care</li>
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
            <h4 class="card-title">Episode Of Care</h4>

            <div class="card">
                <div class="card-body">
                    <form action="javascript:void(0)" id="search-data" class="m-t-40">
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <div class="row justify-content-center">
                            <div class="col-6">
                                <div class="card card-inverse card-info card-mapping" onclick="search('rawat_jalan')">
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
                                <div class="card card-inverse card-warning card-mapping" onclick="search('rawat_inap')">
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
                                <div class="card card-inverse card-success card-mapping" onclick="search('mapped')">
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
                            <h4>Data Pasien</h4>
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
                                    <th>ID Transaksi</th>
                                    <th>Jenis Perawatan</th>
                                    <th>No. Peserta</th>
                                    <th>No. RM</th>
                                    <th>Nama Pasien</th>
                                    <th>Tgl. Masuk</th>
                                    <th>Dokter</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
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
        var selectedIds = [];
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

            getAllData();

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

            // Handle select all checkbox
            $(document).on('change', '#selectAll', function() {
                var isChecked = $(this).is(':checked');
                $('.row-checkbox').prop('checked', isChecked);

                if (isChecked) {
                    $('.row-checkbox:checked').each(function() {
                        var id = $(this).data('id');
                        if (!selectedIds.includes(id)) {
                            selectedIds.push(id);
                        }
                    });
                } else {
                    selectedIds = [];
                }

                updateSelectAllCheckbox();
            });

            // Handle individual checkbox
            $(document).on('change', '.row-checkbox', function() {
                var id = $(this).data('id');
                var isChecked = $(this).is(':checked');

                if (isChecked) {
                    if (!selectedIds.includes(id)) {
                        selectedIds.push(id);
                    }
                } else {
                    selectedIds = selectedIds.filter(item => item !== id);
                }

                updateSelectAllCheckbox();
            });

            // Preserve checkbox state on table draw
            table.on('draw', function() {
                $('.row-checkbox').each(function() {
                    var id = $(this).data('id');
                    if (selectedIds.includes(id)) {
                        $(this).prop('checked', true);
                    }
                });
                updateSelectAllCheckbox();
            });
        });

        function resetSearch() {
            $("#search-data").find("input.form-control").val("").trigger("blur");
            $("#search-data").find("input.form-control").removeClass("was-validated");
            $('input[name="search"]').val("false");

            selectedIds = [];
            updateSelectAllCheckbox();

            table.ajax.reload();
        }

        function search(type) {
            $('input[name="search"]').val(type);
            table.ajax.reload();
        }

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

        // Select single row
        $(document).on('change', '.select-row', function(e) {
            e.stopPropagation();
            const id = $(this).val();
            const param = $(this).data('param');

            if ($(this).is(':checked')) {
                if (!selectedIds.some(item => item.id === id)) {
                    selectedIds.push({
                        id: id,
                        param: param,
                        resend: $(this).data('resend')
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
                            param: param,
                            resend: $(this).data('resend')
                        });
                    }
                } else {
                    selectedIds = selectedIds.filter(item => item.id !== id);
                }

                updateSelectAllCheckbox();
            });
        });

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
                    url: `{{ route('satusehat.episode-of-care.datatable') }}`,
                    method: "POST",
                    data: function(data) {
                        data._token = `${$('meta[name="csrf-token"]').attr("content")}`;
                        data.cari = $('input[name="search"]').val();
                        data.tgl_awal = $('input[name="tgl_awal"]').val();
                        data.tgl_akhir = $('input[name="tgl_akhir"]').val();
                    },
                    dataSrc: function(json) {
                        if(json.summary != undefined){
                            $('#total_all').text(json.summary.total_semua);
                            $('#total_rawat_jalan').text(json.summary.total_rawat_jalan);
                            $('#total_rawat_inap').text(json.summary.total_rawat_inap);
                            $('#total_integrasi').text(json.summary.total_sudah_integrasi);
                            $('#total_belum_integrasi').text(json.summary.total_belum_integrasi);
                        }
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
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'checkbox',
                        name: 'checkbox',
                        orderable: false,
                        searchable: false,
                        responsivePriority: 2
                    },
                    {
                        data: 'ID_TRANSAKSI',
                        name: 'ID_TRANSAKSI',
                        responsivePriority: 3
                    },
                    {
                        data: 'JENIS_PERAWATAN',
                        name: 'JENIS_PERAWATAN',
                        responsivePriority: 4
                    },
                    {
                        data: 'NO_PESERTA',
                        name: 'NO_PESERTA',
                        responsivePriority: 6
                    },
                    {
                        data: 'KBUKU',
                        name: 'KBUKU',
                        responsivePriority: 7
                    },
                    {
                        data: 'NAMA_PASIEN',
                        name: 'NAMA_PASIEN',
                        responsivePriority: 5
                    },
                    {
                        data: 'TANGGAL',
                        name: 'TANGGAL',
                        responsivePriority: 8
                    },
                    {
                        data: 'DOKTER',
                        name: 'DOKTER',
                        responsivePriority: 9
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
                    [8, 'desc']
                ],
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ],
                pageLength: 10,
            })
        }

        $('.data-table').on('click', 'button, a', function(e) {
            e.stopPropagation();
        });

        function show_modal(response) {
            $('#nama_pasien').text(response.dataPasien.NAMA || '-');
            $('#no_rm').text(response.dataPasien.KBUKU || '-');
            $('#no_peserta').text(response.dataPasien.NO_PESERTA || '-');
            $('#no_karcis').text(response.dataPasien.KARCIS || '-');
            $('#dokter').text(response.dataPasien.DOKTER || '-');

            // Populate resume medis data
            $('#plan').text(response.dataErm.PLAN_TERAPI || '-');
            $('#diagnosa').text(response.dataErm.DIAGNOSA || '-');

            // Show status based on integration status
            $('#integrasi_care_plan, #success_care_plan, #failed_care_plan').hide();

            if (!response.dataPasien.statusIntegrated) {
                $('#integrasi_care_plan').show();
            } else if (response.dataPasien.statusIntegrated) {
                $('#success_care_plan').show();
            }

            // Show modal
            $('#modalCarePlan').modal('show');
        }

        function sendSatuSehat(param) {
            var formData = new FormData();
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
            formData.append('param', param);

            Swal.fire({
                title: "Konfirmasi Pengiriman",
                text: `Kirim data Episode Of Care ke SatuSehat?`,
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Ya, kirim!",
                cancelButtonText: "Batal",
            }).then(async (conf) => {
                if (conf.value || conf.isConfirmed) {
                    await ajaxPostFile(
                        `{{ route('satusehat.episode-of-care.send') }}`,
                        formData,
                        "input_success",
                    );
                }
            });
        }

        function resendSatuSehat(param) {
            var formData = new FormData();
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
            formData.append('param', param);

            Swal.fire({
                title: "Konfirmasi Pengiriman Ulang",
                text: `Kirim ulang Episode Of Care?`,
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Ya, kirim!",
                cancelButtonText: "Batal",
            }).then(async (conf) => {
                if (conf.value || conf.isConfirmed) {
                    await ajaxPostFile(
                        `{{ route('satusehat.episode-of-care.resend', '') }}`,
                        formData,
                        "input_success",
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
                text: `Kirim ${selectedIds.length} data Episode Of Care ke SatuSehat?`,
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Ya, kirim semua!",
                cancelButtonText: "Batal",
            }).then(async (result) => {
                if (result.value) {
                    await ajaxPostJson(`{{ route('satusehat.episode-of-care.bulk-send') }}`, {
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
    </script>
@endpush
