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

                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $index => $obat)
                            <tr>
                                <!-- Nomor urut sesuai pagination -->
                                <td>{{ $data->firstItem() + $index }}</td>
                                <td>{{ $obat->KDBRG_CENTRA }}</td>
                                <td>{{ $obat->NAMABRG }}</td>
                                <td>{{ $obat->KD_BRG_KFA }}</td>
                                <td>{{ $obat->NAMABRG_KFA }}</td>
                                <td>{{ $obat->DESCRIPTION }}</td>
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


@endsection