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

    .card-stat {
        transition: all 0.3s ease;
    }

    .card-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .border-left-secondary {
        border-left: 4px solid #6c757d !important;
    }

    .border-left-success {
        border-left: 4px solid #28a745 !important;
    }

    .border-left-warning {
        border-left: 4px solid #ffc107 !important;
    }
</style>
@endpush

@section('content')
<div class="row page-titles">
    <div class="col-md-5 col-8 align-self-center">
        <h3 class="text-themecolor">Dashboard</h3>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Laporan Pemeriksaan</li>
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
        <h4 class="card-title">Data Laporan Pemeriksaan</h4>
        <form action="javascript:void(0)" id="search-data" class="m-t-40">
            <input type="hidden" name="search" value="{{ request('search') }}">

            <div class="row justify-content-center">
                <!-- Card summary -->
                <div class="col-4">
                    <div class="card card-inverse card-primary card-mapping" onclick="search('all')">
                        <div class="card-body">
                            <div class="row align-items-center ml-1">
                                <i class="fas fa-file-medical text-white" style="font-size: 48px"></i>
                                <div class="ml-3">
                                    <span data-count="all" class="text-white" style="font-size: 24px">
                                        0
                                    </span>
                                    <h4 class="text-white">Semua Laporan Pemeriksaan<br></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-4">
                    <div class="card card-inverse card-success card-mapping" onclick="search('sent')">
                        <div class="card-body">
                            <div class="row align-items-center ml-1">
                                <i class="fas fa-check-circle text-white" style="font-size: 48px"></i>
                                <div class="ml-3">
                                    <span data-count="sent" class="text-white" style="font-size: 24px">
                                        0
                                    </span>
                                    <h4 class="text-white">Data Terkirim<br></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-4">
                    <div class="card card-inverse card-danger card-mapping" onclick="search('pending')">
                        <div class="card-body">
                            <div class="row align-items-center ml-1">
                                <i class="fas fa-clock text-white" style="font-size: 48px"></i>
                                <div class="ml-3">
                                    <span data-count="pending" class="text-white" style="font-size: 24px">
                                        0
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
                                <label for="start_date">Periode Tanggal Upload</label>
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

        <!-- Bulk Send Button -->
        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn btn-warning btn-rounded" onclick="bulkSend()" id="bulk-send-btn" disabled>
                <i class="mdi mdi-send-outline"></i> Kirim Terpilih ke SatuSehat
            </button>
        </div>

        <!-- 🧾 Tabel Data -->
        <div class="table-responsive">
            <table id="diagnosticTable" class="display nowrap table data-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>NO</th>
                        <th>
                            <input type="checkbox" id="selectAll" value="selected-all"
                                class="chk-col-purple" />
                            <label for="selectAll"
                                style="margin-bottom: 0px !important; line-height: 25px !important; font-weight: 500">
                                Select All </label>
                        </th>
                        <th>Karcis Asal</th>
                        {{-- <th>Karcis Rujukan</th> --}}
                        <th>Kategori</th>
                        <th>Pasien</th>
                        {{-- <th>Item Lab</th> --}}
                        {{-- <th>File</th> --}}
                        <th>Diupload Oleh</th>
                        {{-- <th>Tanggal Upload</th> --}}
                        <th>Status Integrasi</th>
                        <th></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>


@endsection

@push('after-script')
<script src="{{ asset('assets/plugins/moment/moment.js') }}"></script>
<script src="{{ asset('assets/plugins/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js') }}"></script>
<script>
    var table;
    let selectedIds = [];

    $(document).ready(function() {
        const today = moment().format('YYYY-MM-DD');
        const sevenDaysAgo = moment().subtract(7, 'days').format('YYYY-MM-DD');

        // 🗓️ datepicker
        $("#start_date").bootstrapMaterialDatePicker({
            weekStart: 0,
            time: false,
            format: 'YYYY-MM-DD'
        });

        $("#end_date").bootstrapMaterialDatePicker({
            weekStart: 0,
            time: false,
            format: 'YYYY-MM-DD'
        });

        // Default dates
        $('#start_date').val(sevenDaysAgo);
        $('#end_date').val(today);

        // Initialize bulk send button state
        $('#bulk-send-btn').prop('disabled', true);

        // Initial summary refresh
        refreshSummary();

        // ⚙️ DataTable
        table = $('#diagnosticTable').DataTable({
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
                url: "{{ route('satusehat.diagnostic-report.datatable') }}",
                type: 'POST',
                data: function(d) {
                    d._token = '{{ csrf_token() }}';
                    d.tgl_awal = $('#start_date').val();
                    d.tgl_akhir = $('#end_date').val();
                    d.search = $('input[name="search"]').val();
                }
            },
            columns: [
                {
                    className: 'dtr-control',
                    orderable: false,
                    searchable: false,
                    data: null,
                    defaultContent: '',
                    responsivePriority: 1
                },
                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
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
                    data: 'karcis_asal',
                    name: 'l.karcis_asal',
                    responsivePriority: 3
                },
                // {
                //     data: 'karcis_rujukan',
                //     name: 'l.karcis_rujukan',
                //     responsivePriority: 1
                // },
                {
                    data: 'kategori',
                    name: 'b.nama_kategori',
                    responsivePriority: 1
                },
                {
                    data: 'pasien',
                    name: 'c.NAMA',
                    responsivePriority: 1
                },
                // {
                //     data: 'item_lab',
                //     name: 'm.NM_TIND',
                //     orderable: false,
                //     responsivePriority: 1
                // },
                // {
                //     data: 'file',
                //     name: 'a.file_name',
                //     orderable: false,
                //     responsivePriority: 3
                // },
                {
                    data: 'diupload_oleh',
                    name: 'a.usr_crt',
                    responsivePriority: 5
                },
                // {
                //     data: 'tanggal_upload',
                //     name: 'a.crt_dt',
                //     type: 'date',
                //     responsivePriority: 5
                // },
                {
                    data: 'status_integrasi',
                    name: 'status_integrasi',
                    responsivePriority: 1
                },
                {
                    data: 'aksi',
                    name: 'aksi',
                    orderable: false,
                    searchable: false,
                    responsivePriority: 1
                }
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
        });

        // maintain checkbox state after draw
        table.on('draw', function() {
            $('.select-row').each(function() {
                const id = $(this).val();
                $(this).prop('checked', selectedIds.includes(id));
            });

            updateSelectAllCheckbox();
        });

        table.on('xhr.dt', function(e, settings, json, xhr) {
            if (json && json.summary) {
                $('span[data-count="all"]').text(json.summary.all ?? 0);
                $('span[data-count="sent"]').text(json.summary.sent ?? 0);
                $('span[data-count="pending"]').text(json.summary.pending ?? 0);
            }
        });

        // 🔍 tombol cari
        $("#search-data").on("submit", function(e) {
            e.preventDefault();
            refreshSummary();
            table.ajax.reload();
        });
    });

    function refreshSummary() {
        $.ajax({
            url: "{{ route('satusehat.diagnostic-report.datatable') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                tgl_awal: $('#start_date').val(),
                tgl_akhir: $('#end_date').val(),
                search: '',
                length: 0 // Just get summary, no data
            },
            success: function(response) {
                if (response.summary) {
                    $('span[data-count="all"]').text(response.summary.all ?? 0);
                    $('span[data-count="sent"]').text(response.summary.sent ?? 0);
                    $('span[data-count="pending"]').text(response.summary.pending ?? 0);
                }
            },
            error: function(err) {
                console.error("Failed to update summary:", err);
            }
        });
    }

    // 🔄 reset filter
    function resetSearch() {
        $('#start_date').val('');
        $('#end_date').val('');
        $('input[name="search"]').val('');

        // Reset selection state
        selectedIds = [];
        $('#selectAll').prop('checked', false);
        $('.select-row').prop('checked', false);
        updateSelectAllCheckbox();

        refreshSummary();
        table.ajax.reload();
    }

    // 📦 filter by card
    function search(type) {
        $('input[name="search"]').val(type);
        table.ajax.reload();
    }

    // 📂 Open file in new window
    function openFile(url) {
        window.open(url, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
    }

    // Selection handling similar to specimen index
    $(document).on('change', '.select-row', function(e) {
        e.stopPropagation();
        const id = $(this).val();
        if ($(this).is(':checked')) {
            if (!selectedIds.includes(id)) selectedIds.push(id);
        } else {
            selectedIds = selectedIds.filter(item => item !== id);
        }
        updateSelectAllCheckbox();
    });

    $('#selectAll').on('click', function(e) {
        e.stopPropagation();
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
        updateSelectAllCheckbox();
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

    // Bulk send handler
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

        $.ajax({
            url: `{{ route('satusehat.diagnostic-report.bulk-send') }}`,
            type: "POST",
            data: {
                _token: $('meta[name="csrf-token"]').attr("content"),
                selected_ids: selectedIds
            },
            success: function(response) {
                Swal.close();
                
                if (response.status === 200) {
                    $.toast({
                        heading: "Berhasil!",
                        text: response.message,
                        position: "top-right",
                        icon: "success",
                        hideAfter: 7000
                    });
                    
                    // Show additional info about background processing
                    setTimeout(() => {
                        $.toast({
                            heading: "Info",
                            text: "Data sedang diproses di background. Refresh halaman dalam beberapa menit untuk melihat hasil.",
                            position: "top-right",
                            icon: "info",
                            hideAfter: 5000
                        });
                    }, 2000);
                    
                    // Clear selections and reload table
                    selectedIds = [];
                    $('#selectAll').prop('checked', false);
                    $('.select-row').prop('checked', false);
                    table.ajax.reload();
                } else {
                    $.toast({
                        heading: "Error!",
                        text: response.message,
                        position: "top-right",
                        icon: "error",
                        hideAfter: 5000
                    });
                }
            },
            error: function(xhr) {
                Swal.close();
                let errorMessage = "Terjadi kesalahan saat mengirim data";
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                $.toast({
                    heading: "Error!",
                    text: errorMessage,
                    position: "top-right",
                    icon: "error",
                    hideAfter: 5000
                });
            }
        });
    }

    // 🗑️ Confirm delete document
    function confirmDelete(docId) {
        if (!docId) return;

        swal({
            title: "Hapus Dokumen?",
            text: "Yakin ingin menghapus dokumen ini? Tindakan ini tidak dapat dibatalkan.",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dd6b55",
            confirmButtonText: "Ya, hapus!",
            cancelButtonText: "Batal",
            closeOnConfirm: false
        }, function() {
            deleteDocument(docId);
        });
    }

    function sendSatuSehat(id) {
        Swal.fire({
            title: "Konfirmasi Pengiriman",
            text: `Kirim data laporan pemeriksaan ke SatuSehat?`,
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Ya, kirim!",
            cancelButtonText: "Batal",
        }).then(async (conf) => {
            if (conf.value || conf.isConfirmed) {
                await ajaxPostJson(
                    `{{ route('satusehat.diagnostic-report.send-satu-sehat', '') }}/${id}`, {
                        _token: '{{ csrf_token() }}'
                    },
                    "input_success"
                );
            }
        });
    }

    function reSendSatuSehat(param) {
        Swal.fire({
            title: "Konfirmasi Pengiriman Ulang",
            text: `Kirim ulang data resume medis?`,
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Ya, kirim!",
            cancelButtonText: "Batal",
        }).then(async (conf) => {
            if (conf.value || conf.isConfirmed) {
                await ajaxPostJson(
                    `{{ route('satusehat.diagnostic-report.resend-satu-sehat', '') }}/${param}`, {
                        _token: '{{ csrf_token() }}'
                    },
                    "input_success"
                );
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