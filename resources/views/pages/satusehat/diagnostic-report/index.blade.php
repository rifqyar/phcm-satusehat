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
            <table id="diagnosticTable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th style="width:150px">
                            <input type="checkbox" id="selectAll" value="selected-all"
                                class="chk-col-purple" />
                            <label for="selectAll"
                                style="margin-bottom: 0px !important; line-height: 25px !important; font-weight: 500">
                                Select All </label>
                        </th>
                        <th>Pasien</th>
                        <th>Kategori</th>
                        <th>File</th>
                        <th>Item Lab</th>
                        <th>Diupload Oleh</th>
                        <th>Tanggal Upload</th>
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
            processing: true,
            serverSide: true,
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
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                },
                {
                    data: 'checkbox',
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    responsivePriority: 1
                },
                {
                    data: 'pasien',
                    name: 'c.NAMA'
                },
                {
                    data: 'kategori',
                    name: 'b.nama_kategori'
                },
                {
                    data: 'file',
                    name: 'a.file_name',
                    orderable: false
                },
                {
                    data: 'item_lab',
                    name: 'm.NM_TIND',
                    orderable: false
                },
                {
                    data: 'diupload_oleh',
                    name: 'a.usr_crt'
                },
                {
                    data: 'tanggal_upload',
                    name: 'a.crt_dt',
                    type: 'date'
                },
                {
                    data: 'status_integrasi',
                    name: 'status_integrasi',

                },
                {
                    data: 'aksi',
                    name: 'aksi',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [
                [7, 'desc'] // Order by tanggal_upload column (index 7) descending
            ]
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

    // Bulk send handler
    function bulkSend() {
        if (selectedIds.length === 0) {
            swal('Peringatan', 'Pilih setidaknya satu dokumen untuk dikirim.', 'warning');
            return;
        }

        swal({
            title: 'Kirim Dokumen Terpilih?',
            text: `Yakin ingin mengirim ${selectedIds.length} dokumen terpilih ke SatuSehat?`,
            type: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, kirim',
            cancelButtonText: 'Batal'
        }, function(confirmed) {
            if (!confirmed) return;

            // Show loading
            swal({
                title: 'Mengirim...',
                text: 'Sedang memproses pengiriman data.',
                type: 'info',
                showConfirmButton: false,
                allowOutsideClick: false
            });

            // Call backend endpoint
            $.ajax({
                url: '{{ route("satusehat.diagnostic-report.bulk-send") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ids: selectedIds
                },
                success: function(response) {
                    swal({
                        title: 'Berhasil!',
                        text: response.message || 'Data berhasil dikirim.',
                        type: 'success'
                    }, function() {
                        // Reset selection
                        selectedIds = [];
                        $('#selectAll').prop('checked', false);
                        $('.select-row').prop('checked', false);
                        updateSelectAllCheckbox();

                        // Reload table
                        table.ajax.reload();
                    });
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    swal({
                        title: 'Error!',
                        text: response?.message || 'Terjadi kesalahan saat mengirim data.',
                        type: 'error'
                    });
                }
            });
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
</script>
@endpush