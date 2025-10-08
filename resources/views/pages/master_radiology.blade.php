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
        .table-responsive { overflow: visible; }

        [x-cloak] { display: none !important; }

        .loinc-results {
            position: absolute;
            z-index: 3000;
            left: 0;
            right: 0;
            max-height: 240px;
            overflow-y: auto;
        }

        .loinc-item { cursor: pointer; text-align: left; width: 100%; }
        
        @media (max-width: 640px) {
            .ct-label.ct-horizontal.ct-end {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Daftar Master Radiology</h4>

            <!-- 🔍 Search Form -->
            <form method="GET" action="{{ route('master_radiology') }}" class="mb-3">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama atau kode radiology..."
                        value="{{ request('search') }}">
                    <div class="input-group-append">
                        <button class="btn btn-info" type="submit">Cari</button>
                    </div>
                </div>
            </form>

            <!-- 🧾 Tabel Data -->
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>NAMA GROUP</th>
                            <th>NAMA TINDAKAN</th>
                            <th>CODE LOINC</th>
                            <th>NAMA LOINC</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $index => $rad)
                            <tr>
                                <!-- Nomor urut sesuai pagination -->
                                <td>{{ $data->firstItem() + $index }}</td>
                                <td>{{ $rad->NAMA_GRUP }}</td>
                                <td>{{ $rad->NAMA_TINDAKAN }}</td>
                                <td>{{ $rad->SATUSEHAT_CODE }}</td>
                                <td>{{ $rad->SATUSEHAT_DISPLAY }}</td>
                                <td class="text-center">
                                    @if (empty($rad->SATUSEHAT_CODE) || trim($rad->SATUSEHAT_CODE) === '')
                                        <button type="button" class="btn btn-sm btn-success" data-toggle="modal"
                                            data-target="#modalMapping" data-id="{{ $rad->ID_TINDAKAN }}">
                                            <i class="fas fa-link"></i> Mapping
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-sm btn-warning" data-toggle="modal"
                                            data-target="#modalMapping" data-id="{{ $rad->ID_TINDAKAN }}">
                                            <i class="fas fa-sync-alt"></i> Mapping Ulang
                                        </button>
                                    @endif

                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Data tidak ditemukan</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 📄 Pagination -->
            <div class="d-flex justify-content-center mt-3">
                {{ $data->appends(['search' => request('search')])->links() }}
            </div>
        </div>
    </div>
    @include('modals.modal_mapping_radiology')
    @push('after-script')
        <script>
            $(document).ready(function () {

                // Event saat modal dibuka
                $('#modalMapping').on('show.bs.modal', function (event) {
                    const button = $(event.relatedTarget); // tombol yang diklik
                    const id = button.data('id');

                    // Reset form dulu
                    $('#formMappingRadiology')[0].reset();

                    // Simpan ID ke input hidden
                    $('#id_tindakan').val(id);

                    // Fetch data via POST
                    $.ajax({
                        url: "{{ route('master_radiology.show') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: id
                        },
                        success: function (data) {
                            // Isi form di modal
                            $('#no').val(data.ID_TINDAKAN);
                            $('#nama_grup').val(data.NAMA_GRUP);
                            $('#nama_tindakan').val(data.NAMA_TINDAKAN);
                            $('#satusehat_code').val(data.SATUSEHAT_CODE);
                            $('#satusehat_display').val(data.SATUSEHAT_DISPLAY);
                        },
                        error: function (xhr) {
                            console.error(xhr.responseText);
                            alert('Gagal mengambil data tindakan radiology.');
                        }
                    });
                });

            });
        </script>
    @endpush


@endsection