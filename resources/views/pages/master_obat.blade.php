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

            <!-- 📊 Statistik -->
            <div class="row mb-4">
                <!-- Total -->
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm border-left-secondary">
                        <div class="card-body d-flex align-items-center">
                            <div class="mr-3">
                                <i class="fas fa-pills fa-2x text-secondary"></i>
                            </div>
                            <div>
                                <h6 class="text-secondary mb-0">Total Obat</h6>
                                <h4 class="font-weight-bold mb-0">{{ number_format($total_all) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sudah Dimapping -->
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm border-left-success">
                        <div class="card-body d-flex align-items-center">
                            <div class="mr-3">
                                <i class="fas fa-link fa-2x text-success"></i>
                            </div>
                            <div>
                                <h6 class="text-success mb-0">Sudah Dimapping</h6>
                                <h4 class="font-weight-bold mb-0">{{ number_format($total_mapped) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Belum Dimapping -->
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm border-left-warning">
                        <div class="card-body d-flex align-items-center">
                            <div class="mr-3">
                                <i class="fas fa-unlink fa-2x text-warning"></i>
                            </div>
                            <div>
                                <h6 class="text-warning mb-0">Belum Dimapping</h6>
                                <h4 class="font-weight-bold mb-0">{{ number_format($total_unmapped) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- 🔍 Search & Filter -->
            <form method="GET" action="{{ route('master_obat') }}" class="mb-3">
                <div class="form-row align-items-center">
                    <div class="col-md-4 mb-2">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama atau kode obat..."
                            value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <select name="status" class="form-control">
                            <option value="all" {{ $status == 'all' ? 'selected' : '' }}>Semua</option>
                            <option value="mapped" {{ $status == 'mapped' ? 'selected' : '' }}>Sudah Dimapping</option>
                            <option value="unmapped" {{ $status == 'unmapped' ? 'selected' : '' }}>Belum Dimapping</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button class="btn btn-info btn-block" type="submit">Filter</button>
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
        <script>
            $('#btnSaveMapping').on('click', function () {
                let formData = $('#formMappingObat').serialize();

                $.ajax({
                    url: "{{ route('master_obat.saveMapping') }}",
                    type: "POST",
                    data: formData,
                    success: function (res) {
                        if (res.success) {
                            $.toast({
                                heading: 'Sukses',
                                text: 'Mapping berhasil disimpan.',
                                position: 'top-right',
                                loaderBg: '#51A351',
                                icon: 'success',
                                hideAfter: 1500
                            });

                            $('#modalMapping').modal('hide');

                            // 🔄 reload halaman penuh setelah toast selesai
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            $.toast({
                                heading: 'Gagal',
                                text: res.message || 'Gagal menyimpan data.',
                                position: 'top-right',
                                loaderBg: '#FF5733',
                                icon: 'error',
                                hideAfter: 4000
                            });
                        }
                    },
                    error: function (xhr) {
                        $.toast({
                            heading: 'Error',
                            text: 'Terjadi kesalahan saat menyimpan data.',
                            position: 'top-right',
                            loaderBg: '#FF5733',
                            icon: 'error',
                            hideAfter: 4000
                        });
                        console.error(xhr.responseText);
                    }
                });
            });
        </script>


    @endpush

@endsection