@extends('layouts.app')

@push('before-style')
    <!-- Existing CSS links -->
    <!-- ... -->
    <link href="{{ asset('assets/plugins/chartist-js/dist/chartist.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/chartist-js/dist/chartist-init.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.css') }}"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
        integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

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
                <li class="breadcrumb-item active">Service Request</li>
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
            <h4 class="card-title">Daftar Service Request Pasien</h4>

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
                                                    <span style="font-size: 24px" id="total_all_combined">0</span>
                                                    <h4 class="text-white">Semua Data Service Request </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card card-inverse card-info card-mapping" onclick="search('rad')">
                                    <div class="card-body">
                                        <div class="card-title">
                                            <div class="row align-items-center ml-1">
                                                <i class="fas fa-radiation" style="font-size: 48px"></i>
                                                <div class="ml-3">
                                                    <span style="font-size: 24px" id="total_all_rad">0</span>
                                                    <h4 class="text-white">Radiology 
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card card-inverse card-warning card-mapping" onclick="search('lab')">
                                    <div class="card-body">
                                        <div class="card-title">
                                            <div class="row align-items-center ml-1">
                                                <i class="fas fa-flask" style="font-size: 48px"></i>
                                                <div class="ml-3">
                                                    <span style="font-size: 24px" id="total_all_lab">0</span>
                                                    <h4 class="text-white">Laboratory </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card card-inverse card-success card-mapping" onclick="search('mapped')">
                                    <div class="card-body">
                                        <div class="card-title">
                                            <div class="row align-items-center ml-1">
                                                <i class="fas fa-link" style="font-size: 48px"></i>
                                                <div class="ml-3">
                                                    <span style="font-size: 24px" id="total_mapped_combined">0</span>
                                                    <h4 class="text-white">Data Sudah Mapping </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card card-inverse card-danger card-mapping" onclick="search('unmapped')">
                                    <div class="card-body">
                                        <div class="card-title">
                                            <div class="row align-items-center ml-1">
                                                <i class="fas fa-unlink" style="font-size: 48px"></i>
                                                <div class="ml-3">
                                                    <span style="font-size: 24px" id="total_unmapped_combined">0</span>
                                                    <h4 class="text-white">Data Belum Mapping </h4>
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

            <div class="card">
                <div class="card-body">
                    @if (session('success'))
                        @section('pages-js')
                            <script>
                                $(function() {
                                    $.toast({
                                        heading: 'Success!',
                                        text: `{{ session('success') }}`,
                                        position: 'top-right',
                                        icon: 'success',
                                        hideAfter: 3000,
                                        stack: 6
                                    });
                                })
                            </script>
                        @endsection
                    @endif

                    @if (session('error'))
                        @section('pages-js')
                            <script>
                                $(function() {
                                    $.toast({
                                        heading: 'Failed!',
                                        text: `{{ session('error') }}`,
                                        position: 'top-right',
                                        loaderBg: '#ff6849',
                                        icon: 'error',
                                        hideAfter: 3000,
                                        stack: 6
                                    });
                                })
                            </script>
                        @endsection
                    @endif

                    <div class="card-title d-flex justify-content-between align-items-center">
                        <h4>Data Service Request</h4>
                        <button type="button" class="btn btn-warning btn-rounded" onclick="bulkSend()" id="bulk-send-btn">
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
                                    <th>Jenis Penunjang Medis</th>
                                    <th>Tanggal Masuk</th>
                                    <th>Karcis Asal</th>
                                    <th>Karcis Rujukan</th>
                                    <th>Nama Pasien</th>
                                    <th>Dokter</th>
                                    <th>No. Peserta</th>
                                    <th>No. RM</th>
                                    <th>Tindakan</th>
                                    <th>Status Integrasi</th>
                                    <th>Status Mapping</th>
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
@endsection


@push('after-script')
    <script src="{{ asset('assets/plugins/moment/moment.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js') }}">
    </script>
    <script>
        var table
        let selectedIds = [];
        $(function() {
            const today = moment().format('YYYY-MM-DD');
            const sevenDaysAgo = moment().subtract(7, 'days').format('YYYY-MM-DD');
            $("#start_date").bootstrapMaterialDatePicker({
                weekStart: 0,
                time: false
            });
            $("#end_date").bootstrapMaterialDatePicker({
                weekStart: 0,
                time: false
            });
            $('#start_date').val(sevenDaysAgo);
            $('#end_date').val(today);

            getAllData();
            refreshSummary();
            
            // Initialize bulk send button state
            $('#bulk-send-btn').prop('disabled', true);

            $("#search-data").on("submit", function(e) {
                if (this.checkValidity()) {
                    e.preventDefault();
                    const section = $("#data-section");
                    if (section.length) {
                        $("html, body").animate({ scrollTop: section.offset().top }, 1250);
                    }
                    refreshSummary();
                    table.ajax.reload();
                }

                $(this).addClass("was-validated");
            });

            $('.data-table').on('click', 'button, a', function(e) {
                e.stopPropagation();
            });
        })

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
                refreshSummary()
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
                        target: 'tr'
                    }
                },
                processing: true,
                serverSide: false,
                ajax: {
                    url: `{{ route('satusehat.service-request.datatable') }}`,
                    method: "POST",
                    data: function(data) {
                        data._token = `${$('meta[name="csrf-token"]').attr("content")}`;
                        data.cari = $('input[name="search"]').val();
                        data.tgl_awal = $('input[name="tgl_awal"]').val();
                        data.tgl_akhir = $('input[name="tgl_akhir"]').val();
                    },
                },
                columns: [
                    {
                        className: 'dtr-control',
                        orderable: false,
                        searchable: false,
                        data: null,
                        defaultContent: ''
                    }, 
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: true,
                        searchable: false
                    },
                    {
                        data: 'checkbox',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        responsivePriority: 1
                    },
                    {
                        data: 'KLINIK_TUJUAN',
                        name: 'KLINIK_TUJUAN',
                        responsivePriority: 3
                    },
                    {
                        data: 'TANGGAL_ENTRI',
                        name: 'TANGGAL_ENTRI',
                    },
                    {
                        data: 'KARCIS_ASAL',
                        name: 'KARCIS_ASAL',
                    },
                    {
                        data: 'KARCIS_RUJUKAN',
                        name: 'KARCIS_RUJUKAN',
                    },
                    {
                        data: 'NAMA_PASIEN',
                        name: 'NAMA_PASIEN',
                        responsivePriority: 2
                    },
                    {
                        data: 'nmDok',
                        name: 'nmDok',
                    },
                    {
                        data: 'NO_PESERTA',
                        name: 'NO_PESERTA',
                    },
                    {
                        data: 'KBUKU',
                        name: 'KBUKU',
                    },
                    {
                        data: 'NM_TINDAKAN',
                        name: 'NM_TINDAKAN',
                    },
                    {
                        data: 'status_integrasi',
                        name: 'status_integrasi',
                        responsivePriority: 1
                    },
                    {
                        data: 'status_mapping',
                        name: 'status_mapping',
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
                    [1, 'asc']
                ],
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, 100, "All"]
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

        // Handle checkbox click to prevent row click propagation
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

        // Update status checkbox selectAll
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


        function refreshSummary() {
            $.ajax({
                url: `{{ route('satusehat.service-request.summary') }}`,
                type: "POST",
                data: {
                    _token: $('meta[name="csrf-token"]').attr("content"),
                    tgl_awal: $('input[name="tgl_awal"]').val(),
                    tgl_akhir: $('input[name="tgl_akhir"]').val(),
                },
                success: function(res) {
                    // Update the total counters on your page
                    $('#total_all_lab').text(res.total_all_lab);
                    $('#total_all_rad').text(res.total_all_rad);
                    $('#total_all_combined').text(res.total_all_combined);
                    $('#total_mapped_lab').text(res.total_mapped_lab);
                    $('#total_mapped_rad').text(res.total_mapped_rad);
                    $('#total_mapped_combined').text(res.total_mapped_combined);
                    $('#total_unmapped_lab').text(res.total_unmapped_lab);
                    $('#total_unmapped_rad').text(res.total_unmapped_rad);
                    $('#total_unmapped_combined').text(res.total_unmapped_combined);
                },
                error: function(err) {
                    console.error("Failed to update summary:", err);
                }
            });
        }

        $('.data-table').on('click', 'button, a', function(e) {
            e.stopPropagation();
        });

        function search(type) {
            $('input[name="search"]').val(type)
            table.ajax.reload()
        }

        function sendSatuSehat(param) {
            // function formatNowForInput() {
            //     const d = new Date();
            //     const pad = (n) => n.toString().padStart(2, '0');
            //     return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
            // }

            Swal.fire({
                    title: "Konfirmasi Pengiriman",
                    text: `Kirim data service request ke SatuSehat?`,
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Ya, kirim!",
                    cancelButtonText: "Batal",
                }).then(async (conf) => {
                    if (conf.value || conf.isConfirmed) {
                        await ajaxGetJson(
                            `{{ route('satusehat.service-request.send', '') }}/${btoa(param)}`,
                            "input_success",
                            ""
                        );
                    }
                });

            // Swal.fire({
            //     title: "Masukkan Tanggal & Jam Datang",
            //     html: `
            //         <input type="datetime-local" id="jam_datang" class="swal2-input" value="${formatNowForInput()}" style="width:100%;box-sizing:border-box;">
            //         <div id="jam_err" style="color:#f27474;font-size:0.95rem;margin-top:6px;display:block;"></div>
            //     `,
            //     focusConfirm: false,
            //     showCancelButton: true,
            //     confirmButtonText: "Lanjut",
            //     cancelButtonText: "Batal",
            //     preConfirm: () => {
            //         const jamDatangEl = document.getElementById('jam_datang');
            //         const jamErrEl = document.getElementById('jam_err');

            //         if (!jamDatangEl.value) {
            //             jamErrEl.innerHTML = "Tanggal & jam datang wajib diisi!";
            //             return false;
            //         }
            //         jamErrEl.innerHTML = "";
            //         return jamDatangEl.value;
            //     },
            //     onOpen: () => {
            //         const el = document.getElementById('jam_datang');
            //         if (el) el.focus();
            //     }
            // }).then((timeResult) => {
            //     if (!timeResult.isConfirmed && !timeResult.value) return;

            //     const datetimeLocal = timeResult.value;
            //     const jamDatangIso = datetimeLocal + ':00+07:00';

            //     Swal.fire({
            //         title: "Konfirmasi Pengiriman",
            //         text: `Kirim data kunjungan dengan jam datang ${jamDatangIso}?`,
            //         icon: "question",
            //         showCancelButton: true,
            //         confirmButtonColor: "#3085d6",
            //         cancelButtonColor: "#d33",
            //         confirmButtonText: "Ya, kirim!",
            //         cancelButtonText: "Batal",
            //     }).then(async (conf) => {
            //         if (conf.value || conf.isConfirmed) {
            //             await ajaxGetJson(
            //                 `{{ route('satusehat.service-request.send', '') }}/${btoa(param)}?jam_datang=${encodeURIComponent(jamDatangIso)}`,
            //                 "input_success",
            //                 ""
            //             );
            //         }
            //     });
            // });
            // Swal.fire({
            //     title: "Apakah anda yakin ingin mengirim data kunjungan ke Satu Sehat?",
            //     type: "question",
            //     showCancelButton: true,
            //     confirmButtonColor: "#3085d6",
            //     cancelButtonColor: "#d33",
            //     confirmButtonText: "Ya",
            // }).then(async (conf) => {
            //     if (conf.value == true) {
            //         await ajaxGetJson(
            //             `{{ route('satusehat.encounter.send', '') }}/${btoa(param)}`,
            //             "input_success",
            //             ""
            //         );
            //     } else {
            //         return false;
            //     }
            // });
        }

        function input_success(res) {
            if (res.status != 200) {
                input_error(res);
                return false;
            }

            table.ajax.reload();

            $.toast({
                heading: "Berhasil!",
                text: res.message,
                position: "top-right",
                icon: "success",
                hideAfter: 2500
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
                text: `Kirim ${selectedIds.length} data service request ke SatuSehat?`,
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Ya, kirim semua!",
                cancelButtonText: "Batal",
            }).then((result) => {
                if (result.value) {
                    // Show loading state
                    Swal.fire({
                        title: 'Mengirim Data...',
                        text: 'Mohon tunggu, sedang mengirim data ke SatuSehat',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: `{{ route('satusehat.service-request.bulk-send') }}`,
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
                                refreshSummary();
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
            });
        }
    </script>
@endpush
