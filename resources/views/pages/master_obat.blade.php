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
            <h4 class="card-title">Daftar Master Obat</h4>

            <!-- 🔍 Search Form -->
            <form method="GET" action="{{ route('master_obat') }}" class="mb-3">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama atau kode obat..."
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
                            <th>KODE BARANG CENTRA</th>
                            <th>NAMA BARANG</th>
                            <th>KODE KFA</th>
                            <th>NAMA KFA</th>
                            <th>DESKRIPSI</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $index => $obat)
                            <tr>
                                <!-- Nomor urut sesuai pagination -->
                                <td>{{ $data->firstItem() + $index }}</td>
                                <td>{{ $obat->KDBRG_CENTRA }}</td>
                                <td>{{ $obat->NAMABRG }}</td>
                                <td>
                                    {{ $obat->KD_BRG_KFA && trim($obat->KD_BRG_KFA) !== '' ? $obat->KD_BRG_KFA : 'Data Belum di-mapping' }}
                                </td>
                                <td>{{ $obat->NAMABRG_KFA }}</td>
                                <td>{{ $obat->DESCRIPTION }}</td>
                                <td class="text-center">
                                    @if (empty($obat->KD_BRG_KFA) || trim($obat->KD_BRG_KFA) === '')
                                        <button type="button" class="btn btn-sm btn-success" data-toggle="modal"
                                            data-target="#modalMapping" data-id="{{ $obat->ID }}">
                                            <i class="fas fa-link"></i> Mapping
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-sm btn-warning" data-toggle="modal"
                                            data-target="#modalMapping" data-id="{{ $obat->ID }}">
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
    @include('modals.modal_mapping_obat')
    @push('after-script')
        <script>
            $(document).ready(function () {

                // Event saat modal dibuka
                $('#modalMapping').on('show.bs.modal', function (event) {
                    const button = $(event.relatedTarget); // tombol yang diklik
                    const id = button.data('id');

                    // Reset form dulu
                    $('#formMappingObat')[0].reset();

                    // Simpan ID ke input hidden
                    $('#id_obat').val(id);

                    // Fetch data via POST
                    $.ajax({
                        url: "{{ route('master_obat.show') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: id
                        },
                        success: function (data) {
                            // Isi form di modal
                            $('#no').val(data.ID);
                            $('#kode_barang').val(data.KDBRG_CENTRA);
                            $('#nama_barang').val(data.NAMABRG);
                            $('#kode_kfa').val(data.KD_BRG_KFA);
                            $('#nama_kfa').val(data.NAMABRG_KFA);
                            $('#deskripsi').val(data.DESCRIPTION);
                        },
                        error: function (xhr) {
                            console.error(xhr.responseText);
                            alert('Gagal mengambil data obat.');
                        }
                    });
                });

            });
        </script>
    @endpush

@endsection