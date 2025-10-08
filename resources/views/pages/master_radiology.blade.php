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
                                    <div x-data="loincHandler('{{ $rad->SATUSEHAT_CODE }}', '{{ $rad->SATUSEHAT_DISPLAY }}', '{{ $rad->ID_TINDAKAN }}', '{{ $rad->NAMA_TINDAKAN }}')" class="flex items-center gap-2">
                                        <!-- Display mode -->
                                        <template x-if="!edit">
                                            <div>
                                                <span x-text="code ? code : '-'"></span>
                                                <button class="btn btn-sm btn-link p-0" @click="edit = true">
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
                                                <button class="btn btn-success btn-sm" @click="save()">✅</button>
                                                <button class="btn btn-secondary btn-sm" @click="edit=false">❌</button>
                                            </div>
                                        </template>

                                        <!-- Dropdown results -->
                                        <div x-show="results.length > 0" class="dropdown-menu show mt-1">
                                            <template x-for="item in results" :key="item.LOINC_NUM">
                                                <button class="dropdown-item" 
                                                        @click="code=item.LOINC_NUM; display=item.LONG_COMMON_NAME; results=[]">
                                                    <strong x-text="item.LOINC_NUM"></strong> - <span x-text="item.LONG_COMMON_NAME"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $rad->SATUSEHAT_DISPLAY }}</td>
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
        document.addEventListener('alpine:init', () => {
            Alpine.data('loincHandler', (initialCode, initialDisplay, id, nm_tind) => ({
                edit: false,
                code: initialCode,
                display: initialDisplay,
                searchQuery: '',
                results: [],
                async searchLoinc(query) {
                    if (!query) { this.results = []; return; }
                    try {
                        const res = await axios.post(
                            `https://loinc.regenstrief.org/searchapi/loincs?query=${encodeURIComponent(query)}`, 
                            {
                                auth: {
                                    username: "rifqyar",
                                    password: "Rif1912Qy!"
                                }
                            }
                        );
                        this.results = (res.data.Results || []).slice(0, 10); // limit to 10
                    } catch (e) {
                        console.error("LOINC API error:", e);
                        this.results = [];
                    }
                },
                async save() {
                    try {
                        await axios.post("{{ route('master_radiology.save_loinc') }}", {
                            id, 
                            nm_tind, 
                            code: this.code, 
                            display: this.display,
                            _token: "{{ csrf_token() }}"
                        });
                        window.location.reload(); 
                    } catch (e) {
                        alert("Failed to save LOINC");
                    }
                }
            }))
        })
    </script>


@endsection