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
            <li class="breadcrumb-item active">Resume Medis</li>
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
        <h4 class="card-title">Resume Medis Pasien</h4>

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
                                                <span style="font-size: 24px" id="total_rj">0</span>
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
                                                <span style="font-size: 24px" id="total_ri">0</span>
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
                                                <span style="font-size: 24px" id="total_all">0</span>
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
                                                <span style="font-size: 24px" id="total_integrasi">0</span>
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
                                                <span style="font-size: 24px" id="total_belum_integrasi">0</span>
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
                        <h4>Data Resume Medis</h4>
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
                                <th>NO</th>
                                <th>
                                    <input type="checkbox" id="selectAll" value="selected-all"
                                        class="chk-col-purple" />
                                    <label for="selectAll"
                                        style="margin-bottom: 0px !important; line-height: 25px !important; font-weight: 500">
                                        Select All </label>
                                </th>
                                <th>Karcis</th>
                                <th>Perawatan</th>
                                {{-- <th>Status</th> --}}
                                <th>Tgl. Masuk</th>
                                <th>No. Peserta</th>
                                <th>No. RM</th>
                                <th>Nama</th>
                                <th>Dokter</th>
                                <th>Status Integrasi</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@include('modals.modal_resume_medis')
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

    });

    function resetSearch() {
        // Reset all form inputs
        $('input[name="search"]').val('');
        $('input[name="tgl_awal"]').val('');
        $('input[name="tgl_akhir"]').val('');
        $("#search-data").removeClass("was-validated");

        // Clear selections
        selectedIds = [];
        $('#selectAll').prop('checked', false);
        $('.select-row').prop('checked', false);

        if (typeof table !== 'undefined' && table) {
            table.ajax.reload();
        }

        // Update button state
        updateSelectAllCheckbox();

        $.toast?.({
            heading: "Pencarian direset",
            text: "Filter pencarian dikosongkan.",
            position: "top-right",
            icon: "info",
            textColor: "white",
            hideAfter: 2000,
        });
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
                url: `{{ route('satusehat.resume-medis.datatable') }}`,
                method: "POST",
                data: function(data) {
                    data._token = `${$('meta[name="csrf-token"]').attr("content")}`;
                    data.cari = $('input[name="search"]').val();
                    data.tgl_awal = $('input[name="tgl_awal"]').val();
                    data.tgl_akhir = $('input[name="tgl_akhir"]').val();
                },
                dataSrc: function(json) {
                    $('#total_all').text(json.total_semua)
                    $('#total_rj').text(json.rjAll)
                    $('#total_ri').text(json.ri)
                    $('#total_integrasi').text(json.total_sudah_integrasi)
                    $('#total_belum_integrasi').text(json.total_belum_integrasi)
                    return json.data
                }
            },
            columns: [{
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
                    responsivePriority: 1
                },
                {
                    data: 'checkbox',
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    responsivePriority: 1
                },

                // 4. Karcis
                {
                    data: 'ID_TRANSAKSI',
                    name: 'ID_TRANSAKSI',
                    responsivePriority: 2
                },

                // 5. Perawatan
                {
                    data: 'JENIS_PERAWATAN',
                    name: 'JENIS_PERAWATAN',
                    responsivePriority: 2
                },

                // 6. Status
                // {
                //     data: 'STATUS_SELESAI',
                //     name: 'STATUS_SELESAI',
                //     responsivePriority: 2
                // },

                // 7. Tgl. Masuk → ingin disembunyikan
                {
                    data: 'TANGGAL',
                    name: 'TANGGAL',
                    responsivePriority: 5
                },

                // 8. No Peserta → disembunyikan
                {
                    data: 'NO_PESERTA',
                    name: 'NO_PESERTA',
                    responsivePriority: 5
                },

                // 9. No RM
                {
                    data: 'KBUKU',
                    name: 'KBUKU',
                    responsivePriority: 4
                },

                // 10. Nama
                {
                    data: 'NAMA_PASIEN',
                    name: 'NAMA_PASIEN',
                    responsivePriority: 3
                },

                // 11. Dokter → disembunyikan
                {
                    data: 'DOKTER',
                    name: 'DOKTER',
                    responsivePriority: 5
                },

                // 12. Status Integrasi
                {
                    data: 'status_integrasi',
                    name: 'status_integrasi',
                    responsivePriority: 3
                },

                // 13. Action
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    responsivePriority: 1
                },
            ],
            order: [
                [1, 'asc']
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
            $(this).prop('checked', checked);

            if (checked) {
                if (!selectedIds.includes(id)) selectedIds.push(id);
            } else {
                selectedIds = selectedIds.filter(item => item !== id);
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
        selectedIds = [];
        updateSelectAllCheckbox();

        table.ajax.reload()
    }

    function lihatDetail(param) {
        paramSatuSehat = param;
        
        Swal.fire({
            title: 'Loading...',
            text: 'Sedang memuat data',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: `{{ route('satusehat.resume-medis.lihat-detail', '') }}/${btoa(param)}`,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                Swal.close();
                
                // Populate patient data
                $('#nama_pasien').text(response.dataPasien.NAMA || '-');
                $('#no_rm').text(response.dataPasien.KBUKU || '-');
                $('#no_peserta').text(response.dataPasien.NO_PESERTA || '-');
                $('#no_karcis').text(response.dataPasien.KARCIS || '-');
                $('#dokter').text(response.dataPasien.DOKTER || '-');

                // Populate allergy data
                let allergyHtml = '';
                if (response.dataAlergi && response.dataAlergi.length > 0) {
                    response.dataAlergi.forEach(function(allergy) {
                        allergyHtml += '<tr><td>' + (allergy.JENIS || '-') + '</td><td>' 
                            + (allergy.ALERGEN || '-') + '</td><td>' 
                            + (allergy.ID_ALERGEN_SS || '-') + '</td></tr>';
                    });
                } else {
                    allergyHtml = '<tr><td colspan="3" class="text-center text-muted">Tidak ada data alergi</td></tr>';
                }
                $('#tbodyAlergi').html(allergyHtml);

                // Populate resume medis data
                $('#keluhan').text(response.dataErm.KELUHAN || '-');
                $('#td').text(response.dataErm.TD || '-');
                $('#dj').text(response.dataErm.DJ || '-');
                $('#p').text(response.dataErm.P || '-');
                $('#suhu').text(response.dataErm.SUHU || '-');
                $('#tb').text(response.dataErm.TB || '-');
                $('#bb').text(response.dataErm.BB || '-');
                $('#IMT').text(response.dataErm.IMT || '-');
                $('#diagnosa').text(response.dataErm.DIAGNOSA || '-');
                $('#terapi').text(response.dataErm.TERAPI || '-');
                $('#tindakan').text(response.dataErm.TINDAKAN || '-');
                $('#anjuran').text(response.dataErm.ANJURAN || '-');

                // Show status based on integration status
                $('#integrasi_resume, #success_resume, #failed_resume').hide();
                
                if (response.dataPasien.STATUS_INTEGRATED == 0) {
                    $('#integrasi_resume').show();
                } else if (response.dataPasien.STATUS_INTEGRATED == 1) {
                    $('#success_resume').show();
                } else {
                    $('#failed_resume').show();
                }

                // Show modal
                $('#modalResumeMedis').modal('show');
            },
            error: function(xhr) {
                Swal.close();
                Swal.fire({
                    title: 'Error!',
                    text: 'Gagal memuat data resume medis',
                    icon: 'error'
                });
            }
        });
    }

    $('#btn-send-satusehat').on('click', function() {
        if (paramSatuSehat != '') {
            sendSatuSehat(paramSatuSehat)
        }
    })

    function sendSatuSehat(param) {
        Swal.fire({
            title: "Konfirmasi Pengiriman",
            text: `Kirim data resume medis ke SatuSehat?`,
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Ya, kirim!",
            cancelButtonText: "Batal",
        }).then(async (conf) => {
            if (conf.value || conf.isConfirmed) {
                await ajaxGetJson(
                    `{{ route('satusehat.resume-medis.send', '') }}/${btoa(param)}`,
                    "input_success",
                    ""
                );
            }
        });
    }

    function resendSatuSehat(param) {
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
                await ajaxGetJson(
                    `{{ route('satusehat.resume-medis.resend', '') }}/${btoa(param)}`,
                    "input_success",
                    ""
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

        $.ajax({
            url: `{{ route('satusehat.resume-medis.bulk-send') }}`,
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