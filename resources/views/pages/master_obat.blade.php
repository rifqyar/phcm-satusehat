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
            <div class="row mb-2">
                <!-- Total -->
                <div class="col-md-4 mb-3">
                    <a href="{{ route('master_obat', ['status' => 'all', 'search' => request('search')]) }}"
                        class="text-decoration-none">
                        <div class="card shadow-sm border-left-secondary card-stat clickable">
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
                    </a>
                </div>

                <!-- Sudah Dimapping -->
                <div class="col-md-4 mb-3">
                    <a href="{{ route('master_obat', ['status' => 'mapped', 'search' => request('search')]) }}"
                        class="text-decoration-none">
                        <div class="card shadow-sm border-left-success card-stat clickable">
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
                    </a>
                </div>

                <!-- Belum Dimapping -->
                <div class="col-md-4 mb-3">
                    <a href="{{ route('master_obat', ['status' => 'unmapped', 'search' => request('search')]) }}"
                        class="text-decoration-none">
                        <div class="card shadow-sm border-left-warning card-stat clickable">
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
                    </a>
                </div>
            </div>



            <!-- 🔍 Search & Filter -->
            <div class="mb-2">
                <h5 class="mb-2"><i class="fas fa-filter text-info"></i> Pencarian & Filter Data</h5>
                <p class="text-muted small mb-3">
                    Gunakan kolom di bawah ini untuk mencari dan memfilter data obat.
                </p>

                <form method="GET" action="{{ route('master_obat') }}" class="mb-3">
                    <div class="form-row align-items-end">
                        <!-- 🔤 Kata Kunci -->
                        <div class="col-md-4 mb-2">
                            <label for="search" class="font-weight-bold small text-muted">Kata Kunci</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Cari nama atau kode obat..." value="{{ request('search') }}">
                        </div>

                        <!-- 🔖 Status Mapping -->
                        <div class="col-md-3 mb-2">
                            <label for="status" class="font-weight-bold small text-muted">Status Mapping</label>
                            <select name="status" id="status" class="form-control">
                                <option value="all" {{ $status == 'all' ? 'selected' : '' }}>Semua</option>
                                <option value="mapped" {{ $status == 'mapped' ? 'selected' : '' }}>Sudah Dimapping</option>
                                <option value="unmapped" {{ $status == 'unmapped' ? 'selected' : '' }}>Belum Dimapping
                                </option>
                            </select>
                        </div>

                        <!-- 🔘 Tombol Filter -->
                        <div class="col-md-2 mb-2">
                            <label class="font-weight-bold small text-muted d-block">&nbsp;</label>
                            <button class="btn btn-info btn-block" type="submit">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            a



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
                            <th>JENIS</th>
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
                                <td>{{ $obat->IS_COMPOUND ? 'Compound' : 'Non-compound' }}</td>
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
                console.log('FormData:', formData);
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