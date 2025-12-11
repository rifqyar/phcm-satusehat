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
@push('after-style')
    <link href="{{ asset('assets/plugins/select2/dist/css/select2.min.css') }}" rel="stylesheet" />
    <style>
        .ui-autocomplete-loading {
            background: white url("/assets/images/animated_loading.gif") right center no-repeat;
            background-repeat: no-repeat;
            background-position: center right calc(.375em + .1875rem);
            padding-right: calc(1.5em + 0.75rem);
        }

        /* Biar select2 di dalam modal dan form-group tetap rapi */
        .select2-container {
            width: 100% !important;
        }

        /* Biar area input Select2 multiple bisa diketik lebar penuh */
        .select2-container--classic .select2-selection--multiple .select2-search--inline .select2-search__field {
            width: 100% !important;
        }

        /* Sedikit perbaikan tampilan biar selaras dengan form Bootstrap */
        .select2-container--classic .select2-selection--multiple {
            border: 1px solid #ced4da;
            min-height: calc(1.5em + .75rem + 2px);
            padding: .375rem .75rem;
            border-radius: .25rem;
        }
    </style>
@endpush

@section('content')
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Daftar Master Laboratory</h4>

            <!-- 📊 Statistik -->
            <div class="row">
                <!-- Total -->
                <div class="col-md-4">
                    <a href="{{ route('master_laboratory', ['mapped_filter' => 'all', 'search' => request('search')]) }}"
                        class="text-decoration-none">
                        <div class="card shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="mr-3">
                                    <i
                                        class="fas fa-flask fa-2x {{ request('mapped_filter') == '' || request('mapped_filter') == 'all' ? 'text-primary' : 'text-secondary' }}"></i>
                                </div>
                                <div>
                                    <h6
                                        class="{{ request('mapped_filter') == '' || request('mapped_filter') == 'all' ? 'text-primary' : 'text-secondary' }} mb-0">
                                        Total Tindakan Laboratory</h6>
                                    <h4 class="font-weight-bold mb-0">{{ number_format($total_all) }}</h4>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Sudah Dimapping -->
                <div class="col-md-4">
                    <a href="{{ route('master_laboratory', ['mapped_filter' => 'mapped', 'search' => request('search')]) }}"
                        class="text-decoration-none">
                        <div class="card shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="mr-3">
                                    <i
                                        class="fas fa-link fa-2x {{ request('mapped_filter') == 'mapped' ? 'text-success' : 'text-secondary' }}"></i>
                                </div>
                                <div>
                                    <h6
                                        class="{{ request('mapped_filter') == 'mapped' ? 'text-success' : 'text-secondary' }} mb-0">
                                        Sudah Mapping</h6>
                                    <h4 class="font-weight-bold mb-0">{{ number_format($total_mapped) }}</h4>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Belum Dimapping -->
                <div class="col-md-4">
                    <a href="{{ route('master_laboratory', ['mapped_filter' => 'unmapped', 'search' => request('search')]) }}"
                        class="text-decoration-none">
                        <div class="card shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="mr-3">
                                    <i
                                        class="fas fa-unlink fa-2x {{ request('mapped_filter') == 'unmapped' ? 'text-warning' : 'text-secondary' }}"></i>
                                </div>
                                <div>
                                    <h6
                                        class="{{ request('mapped_filter') == 'unmapped' ? 'text-warning' : 'text-secondary' }} mb-0">
                                        Belum Mapping</h6>
                                    <h4 class="font-weight-bold mb-0">{{ number_format($total_unmapped) }}</h4>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- 🔍 Search Form -->
            <form method="GET" action="{{ route('master_laboratory') }}" class="mb-3">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="input-group">
                            <select name="mapped_filter" class="form-control">
                                <option value="all"
                                    {{ request('mapped_filter') == '' || request('mapped_filter') == 'all' ? 'selected' : '' }}>
                                    Semua</option>
                                <option value="mapped" {{ request('mapped_filter') == 'mapped' ? 'selected' : '' }}>Sudah
                                    Mapping</option>
                                <option value="unmapped" {{ request('mapped_filter') == 'unmapped' ? 'selected' : '' }}>
                                    Belum Mapping</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control"
                                placeholder="Cari nama atau kode tindakan laboratory..." value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-info" type="submit">Filter</button>
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
                            <th>KODE TINDAKAN (ICD9-CM)</th>
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
                                <td>{{ $lab->ICD9 . ' - ' . $lab->ICD9_TEXT }}</td>
                                <td class="text-center">
                                    @if (empty($lab->SATUSEHAT_CODE) || trim($lab->SATUSEHAT_CODE) === '')
                                        <button type="button" class="btn btn-sm btn-warning" data-toggle="modal"
                                            data-target="#modalMapping" data-id="{{ $lab->ID_TINDAKAN }}">
                                            <i class="fas fa-link"></i> Mapping Baru
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-sm btn-success" data-toggle="modal"
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
@endsection
@push('after-script')
    <script src="{{ asset('assets/plugins/select2/dist/js/select2.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            $('#modalMapping').on('show.bs.modal', function(event) {
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
                    success: function(data) {
                        $('#no').val(data.ID_TINDAKAN);
                        $('#nama_grup').val(data.NAMA_GRUP);
                        $('#nama_tindakan').val(data.NAMA_TINDAKAN);
                        $('#satusehat_code').val(data.SATUSEHAT_CODE);
                        $('#satusehat_display').val(data.SATUSEHAT_DISPLAY);
                        $('#icd9_display').val(data.ICD9 + ' - ' + data.ICD9_TEXT);
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        alert('Gagal mengambil data tindakan laboratory.');
                    }
                });

                triggerSelect2Icd9();
            });

        });

        function triggerSelect2Icd9() {
            $('.icd9').select2({
                width: '100%',
                theme: "classic",
                placeholder: 'Cari kode ICD-9...',
                minimumInputLength: 2,
                dropdownParent: $('#modalMapping'),
                ajax: {
                    url: `{{ route('satusehat.procedure.geticd9') }}`,
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return {
                            search: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.map(function(value) {
                                return {
                                    id: value.KODE_SUB,
                                    text: value.DIAGNOSA,
                                };
                            })
                        };
                    },
                    cache: true
                }
            });
        }
    </script>
    <script>
        $('#btnSaveMapping').on('click', function() {
            let formData = $('#formMappingLaboratory').serializeArray();
            let selected = $('.icd9').select2('data')[0];

            if (selected.text != '') {
                formData.push({
                    name: 'icd9_text',
                    value: selected ? selected.text : ''
                });
            } else {
                let icd9 = $('input[name="icd9_display"]').val().split(' - ');
                formData.push({
                    name: 'icd9',
                    value: icd9[0]
                });
                formData.push({
                    name: 'icd9_text',
                    value: icd9[1]
                });
            }

            $.ajax({
                url: "{{ route('master_laboratory.save_loinc') }}",
                type: "POST",
                data: formData,
                success: function(res) {
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
                error: function(xhr) {
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
