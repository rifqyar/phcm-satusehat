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
                                                    <span style="font-size: 24px">{{ number_format($total_all_combined) }}</span>
                                                    <h4 class="text-white">Semua Data Service Request <br> (1 bulan terakhir)</h4>
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
                                                    <span style="font-size: 24px">{{ number_format($total_all_rad) }}</span>
                                                    <h4 class="text-white">Radiology <br> (1 bulan terakhir)
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
                                                    <span style="font-size: 24px">{{ number_format($total_all_lab) }}</span>
                                                    <h4 class="text-white">Laboratory <br> (1 bulan terakhir)</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card card-inverse card-info card-mapping" onclick="search('mapped')">
                                    <div class="card-body">
                                        <div class="card-title">
                                            <div class="row align-items-center ml-1">
                                                <i class="fas fa-link" style="font-size: 48px"></i>
                                                <div class="ml-3">
                                                    <span style="font-size: 24px">{{ number_format($total_mapped_combined) }}</span>
                                                    <h4 class="text-white">Data Sudah Mapping <br> (1 bulan terakhir)</h4>
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
                                                    <span style="font-size: 24px">{{ number_format($total_unmapped_combined) }}</span>
                                                    <h4 class="text-white">Data Belum Mapping <br> (1 bulan terakhir)</h4>
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

                    <div class="card-title">
                        <h4>Data Service Request</h4>
                    </div>
                    <!-- 🧾 Tabel Data -->
                    <div class="table-responsive">
                        <table class="display nowrap table data-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>NO</th>
                                    <th>Jenis Penunjang Medis</th>
                                    <th>Tanggal Masuk</th>
                                    <th>Nama Pasien</th>
                                    <th>Dokter</th>
                                    <th>No. Peserta</th>
                                    <th>No. RM</th>
                                    <th>Tindakan</th>
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
@endsection


@push('after-script')
    <script src="{{ asset('assets/plugins/moment/moment.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js') }}">
    </script>
    <script>
        var table
        $(function() {
            $("#start_date").bootstrapMaterialDatePicker({
                weekStart: 0,
                time: false
            });
            $("#end_date").bootstrapMaterialDatePicker({
                weekStart: 0,
                time: false
            });

            getAllData()

            $("#search-data").on("submit", function(e) {
                if (this.checkValidity()) {
                    e.preventDefault();
                    const section = $("#data-section");
                    if (section.length) {
                        $("html, body").animate({ scrollTop: section.offset().top }, 1250);
                    }
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

            if (typeof table !== 'undefined' && table) {
                table.ajax.reload();
            }

            $.toast?.({
                heading: "Pencarian direset",
                text: "Filter pencarian telah dikosongkan.",
                position: "top-right",
                icon: "info",
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
                        data: 'KLINIK_TUJUAN',
                        name: 'KLINIK_TUJUAN',
                        responsivePriority: 3
                    },
                    {
                        data: 'TANGGAL_ENTRI',
                        name: 'TANGGAL_ENTRI',
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
            })
        }

        $('.data-table').on('click', 'button, a', function(e) {
            e.stopPropagation();
        });

        function search(type) {
            $('input[name="search"]').val(type)
            table.ajax.reload()
        }

        function sendSatuSehat(param) {
            function formatNowForInput() {
                const d = new Date();
                const pad = (n) => n.toString().padStart(2, '0');
                return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
            }

            Swal.fire({
                title: "Masukkan Tanggal & Jam Datang",
                html: `
                    <input type="datetime-local" id="jam_datang" class="swal2-input" value="${formatNowForInput()}" style="width:100%;box-sizing:border-box;">
                    <div id="jam_err" style="color:#f27474;font-size:0.95rem;margin-top:6px;display:block;"></div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: "Lanjut",
                cancelButtonText: "Batal",
                preConfirm: () => {
                    const jamDatangEl = document.getElementById('jam_datang');
                    const jamErrEl = document.getElementById('jam_err');

                    if (!jamDatangEl.value) {
                        jamErrEl.innerHTML = "Tanggal & jam datang wajib diisi!";
                        return false;
                    }
                    jamErrEl.innerHTML = "";
                    return jamDatangEl.value;
                },
                onOpen: () => {
                    const el = document.getElementById('jam_datang');
                    if (el) el.focus();
                }
            }).then((timeResult) => {
                if (!timeResult.isConfirmed && !timeResult.value) return;

                const datetimeLocal = timeResult.value;
                const jamDatangIso = datetimeLocal + ':00+07:00';

                Swal.fire({
                    title: "Konfirmasi Pengiriman",
                    text: `Kirim data kunjungan dengan jam datang ${jamDatangIso}?`,
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Ya, kirim!",
                    cancelButtonText: "Batal",
                }).then(async (conf) => {
                    if (conf.value || conf.isConfirmed) {
                        await ajaxGetJson(
                            `{{ route('satusehat.service-request.send', '') }}/${btoa(param)}?jam_datang=${encodeURIComponent(jamDatangIso)}`,
                            "input_success",
                            ""
                        );
                    }
                });
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
                            "<h5>Berhasil input Request Receiving,<br> Mengembalikan Anda ke halaman sebelumnya...</h5>";
                    } else {
                        text = "<h5>Berhasil input Request Receiving</h5>";
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
                    }
                },
            });
        }
    </script>
@endpush
