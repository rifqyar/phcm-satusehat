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
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $index => $rad)
                            <tr>
                                <!-- Nomor urut sesuai pagination -->
                                <td>{{ $data->firstItem() + $index }}</td>
                                <td>{{ $rad->NAMA_GRUP }}</td>
                                <td>{{ $rad->NAMA_TINDAKAN }}</td>
                                <td>
                                    <div 
                                        x-data="loincHandler('{{ $rad->SATUSEHAT_CODE }}', '{{ $rad->SATUSEHAT_DISPLAY }}', '{{ $rad->ID_TINDAKAN }}', '{{ $rad->NAMA_TINDAKAN }}')"
                                        class="position-relative"
                                        x-cloak
                                    >
                                        <!-- Display mode -->
                                        <template x-if="!edit">
                                            <div>
                                                <span x-text="code ? code : ''"></span>
                                                <button class="btn btn-sm btn-link p-0" @click="startEdit()">
                                                    <i class="fas" :class="code ? 'fa-edit' : 'fa-plus'"></i>
                                                </button>
                                            </div>
                                        </template>

                                        <!-- Edit mode -->
                                        <template x-if="edit">
                                            <div class="flex gap-1">
                                                <input type="text" x-model="searchQuery" placeholder="Search LOINC..."
                                                    class="form-control form-control-sm"
                                                    @input.debounce.500ms="searchLoinc(searchQuery)">
                                                <button class="btn btn-light btn-sm" @click="save()">✅</button>
                                                <button class="btn btn-light btn-sm" @click="cancelEdit()">❌</button>
                                            </div>
                                        </template>

                                        <!-- Dropdown -->
                                        <div x-show="results.length > 0" class="dropdown-menu show mt-1" style="max-height:200px; overflow-y:auto;">
                                            <template x-for="item in results" :key="item.LOINC_NUM">
                                                <button type="button" class="dropdown-item"
                                                    @click="selectItem(item)">
                                                    <strong x-text="item.LOINC_NUM"></strong> - 
                                                    <span x-text="item.LONG_COMMON_NAME"></span>
                                                </button>
                                            </template>
                                        </div>

                                    </div>
                                </td>
                                <td>
                                    <span x-text="false">{{ $rad->SATUSEHAT_DISPLAY }}</span>
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
                    if (!query) { this.results = []; return; }
                    try {
                        const res = await axios.get("{{ route('master_radiology.search_loinc') }}", {
                            params: { query }
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
                        const res = await axios.post("{{ route('master_radiology.save_loinc') }}", {
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



@endsection