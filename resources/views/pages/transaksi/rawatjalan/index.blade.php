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
    <style>
        /* Tambahan pemanis sedikit untuk UI */
        .json-box {
            background-color: #212529;
            color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.85rem;
        }

        .custom-border-left {
            border-left: 4px solid #0dcaf0 !important;
            border-radius: 4px;
        }

        .modern-row:last-child {
            border-bottom: none !important;
        }

        .btn-json-collapse[aria-expanded="true"] .fa-chevron-down {
            transform: rotate(180deg);
            transition: transform 0.3s ease;
        }

        .btn-json-collapse[aria-expanded="false"] .fa-chevron-down {
            transform: rotate(0deg);
            transition: transform 0.3s ease;
        }
    </style>
    <link href="{{ asset('assets/plugins/bootstrap-material-datetimepicker/css/bootstrap-material-datetimepicker.css') }}"
        rel="stylesheet">
    <link href="{{ asset('assets/plugins/select2/dist/css/select2.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
    <div class="row page-titles">
        <!-- Existing content -->
        <div class="col-md-5 col-8 align-self-center">
            <h3 class="text-themecolor">Transaksi Rawat Jalan Satusehat</h3>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Transaksi Satusehat</li>
                <li class="breadcrumb-item active"><a href="{{ route('transaction.rawat-jalan.index') }}">Rawat Jalan</a>
                </li>
            </ol>
        </div>
        <div class="col-md-7 col-4 align-self-center">
            <div class="d-flex m-t-10 justify-content-end">
                <h6>Selamat Datang <p><b>{{ Session::get('nama') }}</b></p>
                </h6>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="card">
                <div class="card-body">
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
                                <div class="card card-inverse card-success card-mapping" onclick="search('done')">
                                    <div class="card-body">
                                        <div class="card-title">
                                            <div class="row align-items-center ml-1">
                                                <i class="fas fa-link" style="font-size: 48px"></i>
                                                <div class="ml-3">
                                                    <span style="font-size: 24px"
                                                        id="total_integrasi">{{ $result['total_integrasi'] }}</span>
                                                    <h4 class="text-white">Data Terintegrasi</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card card-inverse card-danger card-mapping" onclick="search('pending')">
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
    @include('modals.modal_transaksi', [$satuSehatMenu, $listService])
@endsection


@push('after-script')
    <script src="{{ asset('assets/plugins/moment/moment.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js') }}">
    </script>
    <script src="{{ asset('assets/plugins/select2/dist/js/select2.min.js') }}"></script>
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

            // Jalankan Select2 saat modal sudah selesai melakukan animasi "show"
            $('#modal_transaksi').on('shown.bs.modal', function() {
                $('#service').select2({
                    width: '100%',
                    theme: "classic",
                    placeholder: "Harap pilih Service",
                    // Ubah dropdownParent ke .modal-content agar z-index mengikuti konten modal
                    dropdownParent: $('#modal_transaksi .modal-content')
                });
            });

            // Opsional: Hapus instance Select2 saat modal ditutup agar tidak duplikat saat dibuka lagi
            $('#modal_transaksi').on('hidden.bs.modal', function() {
                if ($('#service').hasClass("select2-hidden-accessible")) {
                    $('#service').select2('destroy');
                }
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
                serverSide: true,
                scrollX: false,
                ajax: {
                    url: `{{ route('transaction.rawat-jalan.datatable') }}`,
                    method: "POST",
                    data: function(data) {
                        data._token = `${$('meta[name="csrf-token"]').attr("content")}`;
                        data.cari = $('input[name="search"]').val();
                        data.tgl_awal = $('input[name="tgl_awal"]').val();
                        data.tgl_akhir = $('input[name="tgl_akhir"]').val();
                    },
                    dataSrc: function(json) {
                        if (json.summary != undefined) {
                            $('#total_all').text(json.summary.total_semua);
                            $('#total_integrasi').text(json.summary.total_integrasi);
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

        function lihatDetail(param) {
            paramSatuSehat = param;
            ajaxGetJson(
                `{{ route('transaction.rawat-jalan.lihat-detail', '') }}/${btoa(param)}`,
                "show_modal",
                ""
            );
        }

        function show_modal(response) {
            $('#nama_pasien').text(response.dataPasien.NAMA || '-');
            $('#no_rm').text(response.dataPasien.KBUKU || '-');
            $('#no_peserta').text(response.dataPasien.NO_PESERTA || '-');
            $('#no_karcis').text(response.dataPasien.KARCIS || '-');
            $('#dokter').text(response.dataPasien.DOKTER || '-');

            let keys = Object.keys(response.dataKirimanSatusehat)
            for (let i = 0; i < keys.length; i++) {
                const id = keys[i];
                const val = response.dataKirimanSatusehat[id]
                let bg_color = ''
                let text_color = ''
                let icon = ''
                if (val == '1') {
                    bg_color = 'card-success'
                    text_color = 'text-white'
                    icon = 'fa-check-circle'
                } else {
                    bg_color = 'card-danger'
                    text_color = 'text-white'
                    icon = 'fa-times-circle'
                }

                $(`#${id}`).addClass(bg_color)
                $(`#${id}`).children().addClass(text_color)
                $(`#${id}`).find('i').addClass(icon)
            }

            // Show modal
            $('#modal_transaksi').modal('show');
        }

        function sendSatuSehat(param) {
            var formData = new FormData();
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
            formData.append('param', param);

            Swal.fire({
                title: "Konfirmasi Pengiriman",
                text: `Kirim data Transaksi pasien ini ke SatuSehat?`,
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Ya, kirim!",
                cancelButtonText: "Batal",
            }).then(async (conf) => {
                if (conf.value || conf.isConfirmed) {
                    await ajaxPostFile(
                        `{{ route('transaction.rawat-jalan.send-satusehat') }}`,
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

        $('#service').on('change', function() {
            const serviceName = $(this).val();
            ajaxGetJson(`{{ route('transaction.rawat-jalan.get-log', '') }}/${paramSatuSehat}/${serviceName}`,
                "show_log", "")
        });

        // Helper: Ubah camelCase jadi kalimat biasa
        function humanizeText(str) {
            return str
                .replace(/([A-Z])/g, ' $1')
                .replace(/^./, function(char) {
                    return char.toUpperCase();
                });
        }

        // JSON Nested Accordion
        function jsonToModernUI(data, level = 0) {
            if (typeof data !== 'object' || data === null) {
                return `<span class="text-dark fw-bold">${data}</span>`;
            }

            let html = '';

            for (let key in data) {
                let val = data[key];
                let readableKey = humanizeText(key);

                let icon = 'fas fa-chevron-right';
                let keyLower = key.toLowerCase();
                if (keyLower.includes('date') || keyLower.includes('time')) icon = 'far fa-clock text-warning';
                else if (keyLower.includes('reference')) icon = 'fas fa-link text-primary';
                else if (keyLower.includes('system')) icon = 'fas fa-globe text-info';
                else if (keyLower.includes('display')) icon = 'fas fa-tag text-success';

                if (key === 'resourceType') {
                    html += `
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom mb-2">
                        <span class="text-muted small fw-bold"><i class="fas fa-notes-medical text-danger me-1"></i> Resource Type</span>
                        <span class="badge badge-primary rounded-pill px-3 py-2 shadow-sm" style="font-size:0.85rem;">${val}</span>
                    </div>`;
                    continue;
                }

                if (Array.isArray(val) || (typeof val === 'object' && val !== null)) {
                    let isArray = Array.isArray(val);
                    let titleIcon = isArray ? 'fas fa-list-ul' : 'fas fa-cube';
                    let itemCount = isArray ?
                        `<span class="badge badge-secondary ms-2 ml-2" style="font-size:0.65rem;">${val.length} Item</span>` :
                        '';

                    // ID Unik tanpa spasi atau karakter aneh biar Bootstrap nggak error
                    let uniqueId = `collapse_${Math.random().toString(36).substr(2, 9)}`;

                    let isOpen = level === 0 ? 'show' : '';
                    let btnClass = level === 0 ? '' : 'collapsed';
                    let isExpanded = level === 0 ? 'true' : 'false';

                    html += `
                    <div class="mt-2 mb-2 border rounded shadow-sm overflow-hidden">
                        <button class="btn btn-light w-100 text-start text-left d-flex justify-content-between align-items-center p-2 border-0 shadow-none btn-json-collapse ${btnClass}"
                                type="button"
                                data-toggle="collapse" data-target="#${uniqueId}"
                                data-bs-toggle="collapse" data-bs-target="#${uniqueId}"
                                aria-expanded="${isExpanded}">
                            <span class="text-secondary small fw-bold text-uppercase">
                                <i class="${titleIcon} me-2 text-primary"></i> ${readableKey} ${itemCount}
                            </span>
                            <i class="fas fa-chevron-down text-muted small"></i>
                        </button>

                        <div class="collapse ${isOpen}" id="${uniqueId}">
                            <div class="p-3 bg-white border-top">
                                ${isArray ?
                                    `<div class="d-flex flex-column gap-2 ps-2 pl-2 custom-border-left">
                                                ${val.map((item, idx) => `
                                            <div class="bg-light p-2 rounded border">
                                                <div class="text-muted small mb-1 border-bottom pb-1"><i>Item #${idx + 1}</i></div>
                                                ${jsonToModernUI(item, level + 1)}
                                            </div>
                                        `).join('')}
                                            </div>`
                                    :
                                    jsonToModernUI(val, level + 1)
                                }
                            </div>
                        </div>
                    </div>`;
                } else {
                    let valueDisplay =
                        `<span class="text-dark fw-bold text-end text-right" style="word-break: break-word;">${val}</span>`;

                    if (key === 'code' || keyLower.includes('status')) {
                        valueDisplay = `<span class="badge bg-dark text-white px-2 py-1">${val}</span>`;
                    } else if (key === 'id') {
                        valueDisplay = `<span class="text-secondary text-end text-right font-monospace">${val}</span>`;
                    }

                    html += `
                    <div class="d-flex justify-content-between align-items-start py-2 border-bottom" style="gap: 15px;">
                        <span class="text-muted small text-nowrap"><i class="${icon} me-1" style="font-size:0.8rem;"></i> ${readableKey}</span>
                        ${valueDisplay}
                    </div>`;
                }
            }

            return html;
        }

        // FUNGSI UTAMA
        function show_log(res) {
            const container = document.getElementById('log-container');
            container.innerHTML = '';
            res = res.log

            if (!Array.isArray(res) || res.length === 0) {
                container.innerHTML =
                    '<div class="alert alert-warning text-center mt-4">Tidak ada data log ditemukan.</div>';
                return;
            }

            let accordionHTML = '<div class="accordion mt-4" id="logAccordion">';

            res.forEach((log, index) => {
                let reqObj = {},
                    resObj = {};

                try {
                    reqObj = JSON.parse(log.request);
                } catch (e) {
                    reqObj = log.request;
                }
                try {
                    resObj = JSON.parse(log.response);
                } catch (e) {
                    resObj = log.response;
                }

                let isError = false;
                if (resObj.resourceType === "OperationOutcome" || resObj.issue || resObj.error) {
                    isError = true;
                }

                let badgeClass = isError ? 'bg-danger' : 'bg-success';
                let badgeText = isError ? 'Failed' : 'Success';
                let headerIcon = isError ? '<i class="fas fa-times-circle text-danger me-2"></i>' :
                    '<i class="fas fa-check-circle text-success me-2"></i>';

                let isOpen = index === 0 ? 'show' : '';
                let isCollapsed = index === 0 ? '' : 'collapsed';

                accordionHTML += `
                    <div class="card mb-3 border-0 shadow">
                        <div class="card-header bg-white border-bottom p-0" id="heading${index}">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left text-decoration-none text-dark d-flex justify-content-between align-items-center w-100 p-3 ${isCollapsed}" type="button" data-toggle="collapse" data-bs-toggle="collapse" data-target="#collapse${index}" data-bs-target="#collapse${index}" aria-expanded="${index === 0 ? 'true' : 'false'}" aria-controls="collapse${index}">
                                    <div>
                                        ${headerIcon}
                                        <strong>#${log.id}</strong> | ${log.service}
                                        <small class="text-muted ml-3 ms-3"><i class="far fa-clock"></i> ${log.created_at}</small>
                                    </div>
                                    <span class="badge ${badgeClass} text-white px-3 py-2 rounded-pill">${badgeText}</span>
                                </button>
                            </h2>
                        </div>

                        <div id="collapse${index}" class="collapse ${isOpen}" aria-labelledby="heading${index}" data-parent="#logAccordion" data-bs-parent="#logAccordion">
                            <div class="card-body bg-light">
                                <div class="row">
                                    <div class="col-md-6 mb-4 mb-md-0">
                                        <h6 class="text-primary fw-bold mb-3"><i class="fas fa-cloud-upload-alt me-1"></i> Data Request</h6>

                                        <div class="bg-white p-3 rounded border shadow-sm">
                                            ${jsonToModernUI(reqObj)}
                                        </div>

                                        <details class="mt-3 text-muted" style="cursor: pointer;">
                                            <summary class="small fw-bold"><i class="fas fa-code"></i> Tampilkan JSON Mentah</summary>
                                            <pre class="json-box mt-2"><code>${JSON.stringify(reqObj, null, 2)}</code></pre>
                                        </details>
                                    </div>

                                    <div class="col-md-6">
                                        <h6 class="${isError ? 'text-danger' : 'text-success'} fw-bold mb-3"><i class="fas fa-cloud-download-alt me-1"></i> Balasan Server</h6>

                                        <div class="bg-white p-3 rounded border shadow-sm">
                                            ${jsonToModernUI(resObj)}
                                        </div>

                                        <details class="mt-3 text-muted" style="cursor: pointer;">
                                            <summary class="small fw-bold"><i class="fas fa-code"></i> Tampilkan JSON Mentah</summary>
                                            <pre class="json-box mt-2"><code>${JSON.stringify(resObj, null, 2)}</code></pre>
                                        </details>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            accordionHTML += '</div>';
            container.innerHTML = accordionHTML;
        }
    </script>
@endpush
