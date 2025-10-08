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
            <h4 class="card-title">Daftar Master Laboratory</h4>

            <!-- 🔍 Search Form -->
            <form method="GET" action="{{ route('master_laboratory') }}" class="mb-3">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="input-group">
                            <select name="mapped_filter" class="form-control">
                                <option value="">Semua</option>
                                <option value="mapped" {{ request('mapped_filter') == 'mapped' ? 'selected' : '' }}>Sudah Mapping</option>
                                <option value="unmapped" {{ request('mapped_filter') == 'unmapped' ? 'selected' : '' }}>Belum Mapping</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama atau kode radiology..."
                                value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-info" type="submit">Cari</button>
                            </div>
                        </div>
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
                        @forelse ($data as $index => $lab)
                            <tr>
                                <!-- Nomor urut sesuai pagination -->
                                <td>{{ $data->firstItem() + $index }}</td>
                                <td>{{ $lab->NAMA_GRUP }}</td>
                                <td>{{ $lab->NAMA_TINDAKAN }}</td>
                                <td>{{ $lab->SATUSEHAT_CODE }}</td>
                                <td>{{ $lab->SATUSEHAT_DISPLAY }}</td>
                                <td class="text-center">
                                    @if (empty($lab->SATUSEHAT_CODE) || trim($lab->SATUSEHAT_CODE) === '')
                                        <button type="button" class="btn btn-sm btn-success" data-toggle="modal"
                                            data-target="#modalMapping" data-id="{{ $lab->ID_TINDAKAN }}">
                                            <i class="fas fa-link"></i> Mapping
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-sm btn-warning" data-toggle="modal"
                                            data-target="#modalMapping" data-id="{{ $lab->ID_TINDAKAN }}">
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
    @include('modals.modal_mapping_laboratory')
    @push('after-script')
        <script>
            $(document).ready(function () {
                $('#modalMapping').on('show.bs.modal', function (event) {
                    const button = $(event.relatedTarget);
                    const id = button.data('id');

                    $('#formMappingLaboratory')[0].reset();

                    $('#id_tindakan').val(id);

                    $.ajax({
                        url: "{{ route('master_laboratory.show') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: id
                        },
                        success: function (data) {
                            $('#no').val(data.ID_TINDAKAN);
                            $('#nama_grup').val(data.NAMA_GRUP);
                            $('#nama_tindakan').val(data.NAMA_TINDAKAN);
                            $('#satusehat_code').val(data.SATUSEHAT_CODE);
                            $('#satusehat_display').val(data.SATUSEHAT_DISPLAY);
                        },
                        error: function (xhr) {
                            console.error(xhr.responseText);
                            alert('Gagal mengambil data tindakan laboratory.');
                        }
                    });
                });

            });
        </script>
        <script>
            $('#btnSaveMapping').on('click', function () {
                let formData = $('#formMappingLaboratory').serialize();
                console.log(formData);

                $.ajax({
                    url: "{{ route('master_laboratory.save_loinc') }}",
                    type: "POST",
                    data: formData,
                    success: function (res) {
                        if (res.success) {
                            $.toast({
                                heading: 'Sukses',
                                text: 'Mapping data tindakan radiology berhasil disimpan.',
                                position: 'top-right',
                                loaderBg: '#51A351',
                                icon: 'success',
                                hideAfter: 1500
                            });

                            $('#modalMapping').modal('hide');

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