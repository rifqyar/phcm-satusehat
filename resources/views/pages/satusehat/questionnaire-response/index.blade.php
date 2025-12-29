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

    /* Fix modal z-index issue */
    .modal {
        z-index: 1050 !important;
    }

    .modal-backdrop {
        z-index: 1040 !important;
    }

    .modal-dialog {
        z-index: 1051 !important;
        margin-top: 50px;
    }

    .modal-content {
        position: relative;
        background-color: #fff;
        border: 1px solid rgba(0,0,0,.2);
        border-radius: 0.3rem;
        outline: 0;
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
            <li class="breadcrumb-item active">Respon Kuesioner</li>
        </ol>
    </div>
    <div class="col-md-7 col-4 align-self-center">
        <div class="d-flex m-t-10 justify-content-end">
            <h6>Selamat Datang <p><b>{{ Session::get('user') }}</b></p>
            </h6>
        </div>
    </div>
</div>

<div class="card" id="data-section">
    <div class="card-body">
        <h4 class="card-title">Respon Kuesioner</h4>
        <form action="javascript:void(0)" id="search-data" class="m-t-40">
            <input type="hidden" name="search" value="{{ request('search') }}">

            <div class="row justify-content-center">
                <!-- Card summary -->
                <div class="col-4">
                    <div class="card card-inverse card-primary card-mapping" onclick="search('all')">
                        <div class="card-body">
                            <div class="row align-items-center ml-1">
                                <!-- <i class="fas fa-question- text-white" style="font-size: 48px"></i> -->
                                <div class="ml-3">
                                    <span id="total_all" class="text-white" style="font-size: 24px">
                                        {{ $mergedAll ?? 0 }}
                                    </span>
                                    <h4 class="text-white">Semua Data Kunjungan<br></h4>
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
                                    <span id="total_sent" class="text-white" style="font-size: 24px">
                                        {{ $mergedIntegrated ?? 0 }}
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
                                    <span id="total_unsent" class="text-white" style="font-size: 24px">
                                        {{ $unmapped ?? 0 }}
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
            <!-- <button type="button" class="btn btn-warning btn-rounded" onclick="bulkSend()" id="bulk-send-btn" disabled>
                <i class="mdi mdi-send-outline"></i> Kirim Terpilih ke SatuSehat
            </button> -->
        </div>

        <!-- 🧾 Tabel Data -->
        <div class="table-responsive">
            <table id="questionnaireResponseTable" class="data-table table table-striped table-bordered" style="width:100%">
                <thead>
                        <tr>
                            <th></th>
                            <th>NO</th>
                            <th>Karcis</th>
                            <th>Perawatan</th>
                            <th>Status</th>
                            <th>Tgl. Masuk</th>
                            <th>No. Peserta</th>
                            <th>No. RM</th>
                            <th>Nama</th>
                            <th>Dokter</th>
                            <th>Debitur</th>
                            <th>Ruangan</th>
                            <th>Status Integrasi</th>
                            <th></th>
                        </tr>
                    </thead>
            </table>
        </div>
    </div>
</div>

@include('modals.modal_questionnaire')
@endsection


@push('after-script')
<script src="{{ asset('assets/plugins/moment/moment.js') }}"></script>
<script src="{{ asset('assets/plugins/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js') }}">
</script>
<script>
    var table
    $(function() {
        // Initialize modal on page load
        $('#questionnaireModal').modal({
            show: false
        });

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
        // Reset date inputs to today
        const today = moment().format('YYYY-MM-DD');
        $('#start_date').val(today);
        $('#end_date').val(today);
        
        // Reset search type
        $('input[name="search"]').val('');
        
        // Remove validation classes
        $("#search-data").removeClass("was-validated");
        
        // Reload table
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
            serverSide: true,
            scrollX: false,
            ajax: {
                url: `{{ route('satusehat.questionnaire-response.datatable') }}`,
                method: "POST",
                data: function(data) {
                    data._token = `${$('meta[name="csrf-token"]').attr("content")}`;
                    data.cari = $('input[name="search"]').val();
                    data.tgl_awal = $('input[name="tgl_awal"]').val();
                    data.tgl_akhir = $('input[name="tgl_akhir"]').val();
                },
                dataSrc: function(json) {
                    $('#total_all').text(json.total_semua)
                    $('#total_sent').text(json.total_sudah_integrasi)
                    $('#total_unsent').text(json.total_belum_integrasi)
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
                }, {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false,
                    responsivePriority: 1
                },
                {
                    data: 'ID_TRANSAKSI',
                    name: 'ID_TRANSAKSI',
                    responsivePriority: 2
                },
                {
                    data: 'JENIS_PERAWATAN',
                    name: 'JENIS_PERAWATAN',
                    responsivePriority: -1
                },
                {
                    data: 'STATUS_SELESAI',
                    name: 'STATUS_SELESAI',
                    responsivePriority: -1
                },
                {
                    data: 'TANGGAL',
                    name: 'TANGGAL',
                    responsivePriority: 7
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
                },
                {
                    data: 'DEBITUR',
                    name: 'DEBITUR',
                },
                {
                    data: 'LOKASI',
                    name: 'LOKASI',
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
                [1, 'asc']
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

    // Handler for "Isi Respon Kuesioner"
    window.tambahRespon = function(id) {
        // Show loading
        Swal.fire({
            title: 'Memuat...',
            text: 'Mengambil data kuesioner',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading()
            }
        });

        // Fetch questions from backend
        $.ajax({
            url: '{{ route("satusehat.questionnaire-response.questions") }}',
            type: 'GET',
            data: { visit_id: id },
            success: function(response) {
                Swal.close();
                
                // Populate modal with structured questions
                let questionsHtml = '';
                let questionNumber = 1;
                
                response.sections.forEach(function(section, sectionIndex) {
                    questionsHtml += `
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                ${section.linkId}. ${section.title}
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tbody>
                    `;
                    
                    section.questions.forEach(function(question) {
                        const isBoolean = question.type === 'valueBoolean';
                        const yesLabel = isBoolean ? 'Ya (Ada)' : 'Sesuai';
                        const noLabel = isBoolean ? 'Tidak (Tidak Ada)' : 'Tidak Sesuai';
                        const yesValue = isBoolean ? 'true' : 'OV000052';
                        const noValue = isBoolean ? 'false' : 'OV000053';
                        
                        questionsHtml += `
                            <tr>
                                <td style="width: 70%">
                                    <strong>${question.linkId}.</strong> ${question.text}
                                </td>
                                <td style="width: 30%">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" 
                                               name="question_${question.linkId}" 
                                               id="yes_${question.linkId}" 
                                               value="${yesValue}"
                                               data-type="${question.type}"
                                               checked>
                                        <label class="form-check-label" for="yes_${question.linkId}">
                                            ${yesLabel}
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" 
                                               name="question_${question.linkId}" 
                                               id="no_${question.linkId}" 
                                               value="${noValue}"
                                               data-type="${question.type}">
                                        <label class="form-check-label" for="no_${question.linkId}">
                                            ${noLabel}
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    
                    questionsHtml += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                });
                
                $('#questionsContainer').html(questionsHtml);
                $('#questionnaireModal').data('visitId', id);
                
                // Ensure modal is shown properly
                setTimeout(function() {
                    $('#questionnaireModal').modal({
                        backdrop: 'static',
                        keyboard: false,
                        show: true
                    });
                }, 100);
            },
            error: function(xhr) {
                Swal.close();
                Swal.fire({
                    title: 'Error!',
                    text: 'Gagal memuat data kuesioner',
                    icon: 'error'
                });
            }
        });
    }

    // Save questionnaire response
    window.saveResponse = function() {
        const visitId = $('#questionnaireModal').data('visitId');
        const responses = {};
        
        // Collect all radio button values
        $('#questionnaireForm input[type="radio"]:checked').each(function() {
            const name = $(this).attr('name');
            const questionId = name.replace('question_', '');
            responses[questionId] = $(this).val();
        });

        // Basic validation - ensure all questions are answered
        const totalQuestions = $('#questionnaireForm input[type="radio"]').length / 2; // Divide by 2 because each question has 2 radio buttons
        const answeredQuestions = Object.keys(responses).length;
        
        if (answeredQuestions < totalQuestions) {
            Swal.fire({
                title: 'Peringatan!',
                text: 'Harap jawab semua pertanyaan',
                icon: 'warning'
            });
            return;
        }

        // Show loading
        Swal.fire({
            title: 'Menyimpan...',
            text: 'Sedang menyimpan respon',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading()
            }
        });

        // Send responses to backend (placeholder)
        setTimeout(() => {
            Swal.close();
            $('#questionnaireModal').modal('hide');
            
            Swal.fire({
                title: 'Berhasil!',
                text: 'Respon kuesioner berhasil disimpan',
                icon: 'success'
            }).then(() => {
                table.ajax.reload();
            });
        }, 1500);
    }

    function search(type) {
        $('input[name="search"]').val(type)
        table.ajax.reload()
    }

    function sendSatuSehat(param) {
        Swal.fire({
            title: "Konfirmasi Pengiriman",
            text: `Kirim data kunjungan Pasien?`,
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Ya, kirim!",
            cancelButtonText: "Batal",
        }).then(async (conf) => {
            if (conf.value || conf.isConfirmed) {
                await ajaxGetJson(
                    `{{ route('satusehat.questionnaire-response.send', '') }}/${btoa(param)}`,
                    "input_success",
                    ""
                );
            }
        });
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
        //             `{{ route('satusehat.questionnaire-response.send', '') }}/${btoa(param)}`,
        //             "input_success",
        //             ""
        //         );
        //     } else {
        //         return false;
        //     }
        // });
    }

    function resendSatuSehat(param) {
        Swal.fire({
            title: "Konfirmasi Pengiriman Ulang",
            text: `Kirim Ulang Data Kunjungan Pasien?`,
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Ya, kirim!",
            cancelButtonText: "Batal",
        }).then(async (conf) => {
            if (conf.value || conf.isConfirmed) {
                await ajaxGetJson(
                    `{{ route('satusehat.questionnaire-response.resend', '') }}/${btoa(param)}`,
                    "input_success",
                    ""
                );
            }
        });
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
        //             `{{ route('satusehat.questionnaire-response.send', '') }}/${btoa(param)}`,
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