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

        [x-cloak] {
            display: none !important;
        }

        .loinc-results {
            position: absolute;
            z-index: 3000;
            left: 0;
            right: 0;
            max-height: 240px;
            overflow-y: auto;
        }

        .loinc-item {
            cursor: pointer;
            text-align: left;
            width: 100%;
        }

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
            <h4 class="card-title">Daftar Mapping Specimen</h4>

            <div class="card">
                <div class="card-body">
                    <div class="card-title">
                        <h4>Form Pencarian</h4>
                    </div>
                    <!-- 🔍 Search Form -->
                    <form method="GET" action="{{ route('master_specimen.index') }}" class="mb-3">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control"
                                placeholder="Cari nama atau kode tindakan lab..." value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-info" type="submit">Cari</button>
                            </div>
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
                        <h4>Data Tindakan & Specimen</h4>
                    </div>
                    <!-- 🧾 Tabel Data -->
                    <div class="table-responsive">
                        <table class="display nowrap table data-table">
                            <thead>
                                <tr>
                                    <th>NO</th>
                                    <th>NAMA GROUP TINDAKAN</th>
                                    <th>NAMA TINDAKAN</th>
                                    <th>SPECIMEN</th>
                                    <th>STATUS MAPPING</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tindakanData as $index => $lab)
                                    <tr>
                                        <!-- Nomor urut sesuai pagination -->
                                        <td>{{ $tindakanData->firstItem() + $index }}</td>
                                        <td>{{ $lab->NAMA_GRUP }}</td>
                                        <td>{{ $lab->NAMA_TINDAKAN }}</td>
                                        <td>
                                            @foreach (json_decode($lab->SPECIMEN) as $specimen)
                                                <div>
                                                    <strong>{{ $specimen->code }}</strong> - {{ $specimen->display }}
                                                </div>
                                            @endforeach
                                        </td>
                                        <td>
                                            @if (count(json_decode($lab->SPECIMEN)) > 0)
                                                <span class="badge badge-pill badge-success p-2">Sudah Mapping</span>
                                            @else
                                                <span class="badge badge-pill badge-secondary p-2">Belum Termapping</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if (count(json_decode($lab->SPECIMEN)) > 0)
                                                <a href="{{ route('master_specimen.edit', $lab->ID_TINDAKAN) }}?search={{ $lab->NAMA_TINDAKAN }}"
                                                    class="badge badge-pill badge-warning p-2 w-100">Edit Specimen</a>
                                            @else
                                                <a href="{{ route('master_specimen.create') }}?search={{ $lab->NAMA_TINDAKAN }}"
                                                    class="badge badge-pill badge-primary p-2 w-100">Tambah Specimen</a>
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
                        {{ $tindakanData->appends(['search' => request('search')])->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('after-script')
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script>
        axios.defaults.headers.common['X-CSRF-TOKEN'] = '{{ csrf_token() }}';

        document.addEventListener('alpine:init', () => {
            Alpine.data('loincHandler', (initialCode, initialDisplay, id, nm_tind) => ({
                edit: false,
                code: initialCode,
                display: initialDisplay,
                draftCode: '',
                draftDisplay: '',
                searchQuery: '',
                results: [],

                startEdit() {
                    this.draftCode = this.code;
                    this.draftDisplay = this.display;
                    this.edit = true;
                },

                cancelEdit() {
                    this.edit = false;
                    this.results = [];
                },

                async searchLoinc(query) {
                    if (!query) {
                        this.results = [];
                        return;
                    }
                    try {
                        const res = await axios.get(
                            "{{ route('master_radiology.search_loinc') }}", {
                                params: {
                                    query
                                }
                            });
                        this.results = (res.data.Results || []).slice(0, 10);
                    } catch (e) {
                        console.error("LOINC API error:", e);
                        this.results = [];
                    }
                },

                selectItem(item) {
                    this.draftCode = item.LOINC_NUM;
                    this.draftDisplay = item.LONG_COMMON_NAME;
                    this.searchQuery = item.LOINC_NUM;
                    this.results = [];
                },

                async save() {
                    try {
                        const res = await axios.post(
                            "{{ route('master_radiology.save_loinc') }}", {
                                id,
                                nm_tind,
                                code: this.draftCode,
                                display: this.draftDisplay,
                                _token: "{{ csrf_token() }}"
                            });

                        if (res.data.success) {
                            this.code = this.draftCode;
                            this.display = this.draftDisplay;
                            this.edit = false;

                            window.location.reload();
                        } else {
                            alert("Failed to save LOINC");
                        }
                    } catch (e) {
                        alert("Failed to save LOINC");
                    }
                }
            }))

        })
    </script>
@endpush
