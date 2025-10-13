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
@endpush

@section('content')
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Daftar Kunjungan Pasien</h4>

            <div class="card">
                <div class="card-body">
                    <div class="card-title">
                        <h4>Form Filter</h4>
                    </div>

                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <div class="row justify-content-center">
                        <div class="col-4">
                            <div class="card card-inverse card-primary card-mapping" onclick="search('all')">
                                <div class="card-body">
                                    <div class="card-title">
                                        <div class="row align-items-center ml-1">
                                            <i class="fas fa-info-circle" style="font-size: 48px"></i>
                                            <div class="ml-3">
                                                <span style="font-size: 24px">{{ $totalTindakan }}</span>
                                                <h4 class="text-white">Semua Data Kunjungan</h4>
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
                                                <span style="font-size: 24px">{{ $totalMapping }}</span>
                                                <h4 class="text-white">Data Sudah Terkirim</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card card-inverse card-warning card-mapping" onclick="search('unmapped')">
                                <div class="card-body">
                                    <div class="card-title">
                                        <div class="row align-items-center ml-1">
                                            <i class="fas fa-unlink" style="font-size: 48px"></i>
                                            <div class="ml-3">
                                                <span style="font-size: 24px">{{ $totalUnmapped }}</span>
                                                <h4 class="text-white">Data Belum Terkirim</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
                        <h4>Data Kunjungan Pasien</h4>
                    </div>
                    <!-- 🧾 Tabel Data -->
                    <div class="table-responsive">
                        <table class="display nowrap table data-table">
                            <thead>
                                <tr>
                                    <th>NO</th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th>STATUS MAPPING</th>
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
    <script>
        var table
        $(function() {
            getAllData()
        })

        function getAllData() {
            table = $('.data-table').DataTable({
                responsive: true,
                processing: true,
                serverSide: false,
                ajax: {
                    url: `{{ route('satusehat.encounter.datatable') }}`,
                    method: "POST",
                    data: function(data) {
                        data._token = `${$('meta[name="csrf-token"]').attr("content")}`;
                        data.cari = $('input[name="search"]').val();
                    },
                },
                scrollX: true,
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'NAMA_GRUP',
                        name: 'NAMA_GRUP'
                    },
                    {
                        data: 'NAMA_TINDAKAN',
                        name: 'NAMA_TINDAKAN'
                    },
                    {
                        data: 'SPECIMEN',
                        name: 'SPECIMEN',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status_mapping',
                        name: 'status_mapping',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                order: [
                    [0, 'asc']
                ],
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ],
                pageLength: 10,
            })
        }

        function search(type) {
            if (type === 'mapped') {
                $('input[name="search"]').val('mapped')
            } else if (type === 'unmapped') {
                $('input[name="search"]').val('unmapped')
            } else {
                $('input[name="search"]').val('')
            }
            table.ajax.reload()
        }
    </script>
@endpush
