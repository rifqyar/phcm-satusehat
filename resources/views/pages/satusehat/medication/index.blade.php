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

        /* ✅ Pastikan kolom pertama untuk checkbox terlihat */
        table.table th:first-child,
        table.table td:first-child {
            width: 50px !important;
            text-align: center;
            vertical-align: middle;
        }

        /* ✅ Override styling Bootstrap yang kadang menyembunyikan checkbox */
        input[type="checkbox"],
        .form-check-input {
            appearance: auto !important;
            -webkit-appearance: checkbox !important;
            -moz-appearance: checkbox !important;
            opacity: 1 !important;
            position: static !important;
            visibility: visible !important;
        }

        /* ✅ Sedikit gaya tambahan biar lebih enak dilihat */
        .form-check-input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #0d6efd;
            /* Bootstrap blue */
        }
    </style>
@endpush

@section('content')
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Daftar Master Obat</h4>

            <!-- 📊 Statistik -->
            <!-- 🔹 Baris pertama: Total, Mapped, Unmapped -->
            <div class="row mb-3">
                <!-- Total -->
                <div class="col-md-4 mb-3">
                    <a href="{{ route('satusehat.medication.index', ['status' => 'all', 'search' => request('search')]) }}"
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
                    <a href="{{ route('satusehat.medication.index', ['status' => 'mapped', 'search' => request('search')]) }}"
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
                    <a href="{{ route('satusehat.medication.index', ['status' => 'unmapped', 'search' => request('search')]) }}"
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

            <!-- 🔹 Baris kedua: Unsent, Sent -->
            <div class="row mb-2">
                <!-- Belum Dikirim -->
                <div class="col-md-6 mb-3">
                    <a href="{{ route('satusehat.medication.index', ['status' => 'unsent', 'search' => request('search')]) }}"
                        class="text-decoration-none">
                        <div class="card shadow-sm border-left-info card-stat clickable">
                            <div class="card-body d-flex align-items-center">
                                <div class="mr-3">
                                    <i class="fas fa-paper-plane fa-2x text-info"></i>
                                </div>
                                <div>
                                    <h6 class="text-info mb-0">Belum Dikirim</h6>
                                    <h4 class="font-weight-bold mb-0">{{ number_format($total_unsent) }}</h4>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Sudah Dikirim -->
                <div class="col-md-6 mb-3">
                    <a href="{{ route('satusehat.medication.index', ['status' => 'sent', 'search' => request('search')]) }}"
                        class="text-decoration-none">
                        <div class="card shadow-sm border-left-primary card-stat clickable">
                            <div class="card-body d-flex align-items-center">
                                <div class="mr-3">
                                    <i class="fas fa-check-circle fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="text-primary mb-0">Sudah Dikirim</h6>
                                    <h4 class="font-weight-bold mb-0">{{ number_format($total_sent) }}</h4>
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

                <form method="GET" action="{{ route('satusehat.medication.index') }}" class="mb-3">
                    <div class="form-row align-items-end">
                        <!-- 🔤 Kata Kunci -->
                        <div class="col-md-4 mb-2">
                            <label for="search" class="font-weight-bold small text-muted">Kata Kunci</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Cari nama atau kode obat..." value="{{ request('search') }}">
                        </div>

                        <!-- 🔖 Status Mapping -->
                        <div class="col-md-3 mb-2">
                            <label for="status" class="font-weight-bold small text-muted">Status Data</label>
                            <select name="status" id="status" class="form-control">
                                <option value="all" {{ $status == 'all' ? 'selected' : '' }}>Semua</option>
                                <option value="mapped" {{ $status == 'mapped' ? 'selected' : '' }}>Sudah Dimapping</option>
                                <option value="unmapped" {{ $status == 'unmapped' ? 'selected' : '' }}>Belum Dimapping
                                </option>
                                <option value="unsent" {{ $status == 'unsent' ? 'selected' : '' }}>Belum Dikirim</option>
                                <option value="sent" {{ $status == 'sent' ? 'selected' : '' }}>Sudah Dikirim</option>
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

            <div class="mb-2">
                <button type="button" id="btnKirimDipilih" class="btn btn-success btn-sm">
                    <i class="fas fa-paper-plane"></i> Kirim Dipilih
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th>NO</th>
                            <th>KODE BARANG CENTRA</th>
                            <th>NAMA BARANG</th>
                            <th>KODE KFA</th>
                            <th>NAMA KFA</th>
                            <th>JENIS</th>
                            <th>FHIR ID</th>
                            <th>DESKRIPSI</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $index => $obat)
                            <tr>
                                <td class="text-center">
                                    <div class="form-check d-flex justify-content-center">
                                        <input class="form-check-input checkbox-item"
                                            type="checkbox"
                                            value="{{ $obat->KDBRG_CENTRA }}"
                                            data-kode="{{ $obat->KDBRG_CENTRA }}"
                                            data-kfa="{{ $obat->KD_BRG_KFA }}">
                                    </div>
                                </td>
                                <td>{{ $data->firstItem() + $index }}</td>
                                <td>{{ $obat->KDBRG_CENTRA }}</td>
                                <td>{{ $obat->NAMABRG }}</td>
                                <td>
                                    {{ $obat->KD_BRG_KFA && trim($obat->KD_BRG_KFA) !== '' ? $obat->KD_BRG_KFA : 'Data Belum di-mapping' }}
                                </td>
                                <td>{{ $obat->NAMABRG_KFA }}</td>
                                <td>{{ $obat->IS_COMPOUND ? 'Compound' : 'Non-compound' }}</td>
                                <td>
                                    @if (!empty($obat->FHIR_ID))
                                        <span class="badge badge-success">{{ $obat->FHIR_ID }}</span>
                                    @else
                                        <span class="text-muted">Belum dikirim</span>
                                    @endif
                                </td>
                                <td>{{ $obat->DESCRIPTION }}</td>
                                <td class="text-center">
                                    @if (empty($obat->FHIR_ID) && !empty($obat->KD_BRG_KFA))
                                        <button type="button" class="btn btn-sm btn-primary btn-send-medication"
                                            data-kode="{{ $obat->KDBRG_CENTRA }}">
                                            <i class="fas fa-paper-plane"></i> Kirim Data
                                        </button>
                                    @elseif (!empty($obat->FHIR_ID))
                                        <button type="button" class="btn btn-sm btn-info btn-send-medication"
                                            data-kode="{{ $obat->KDBRG_CENTRA }}">
                                            <i class="fas fa-redo"></i> Kirim Ulang
                                        </button>
                                    @else
                                        <span class="text-muted small">Mapping KFA diperlukan</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">Data tidak ditemukan</td>
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
                // Event kirim Medication
                $(document).on('click', '.btn-send-medication', function () {
                    const kode = $(this).data('kode');

                    if (!kode) {
                        $.toast({
                            heading: 'Error',
                            text: 'Kode barang tidak ditemukan.',
                            position: 'top-right',
                            loaderBg: '#FF5733',
                            icon: 'error',
                            hideAfter: 4000
                        });
                        return;
                    }

                    (async () => {
                        const result = await Swal.fire({
                            title: 'Kirim Data Medication?',
                            text: `Kirim data Medication ke SATUSEHAT?\nKode Barang: ${kode}`,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Kirim',
                            cancelButtonText: 'Batal',
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33'
                        });

                        if (!result.isConfirmed && !result.value) return;

                        console.log('Konfirmasi diterima, lanjut kirim AJAX untuk:', kode);

                        $.toast({
                            heading: 'Mengirim...',
                            text: 'Data sedang dikirim ke SATUSEHAT.',
                            position: 'top-right',
                            loaderBg: '#5bc0de',
                            icon: 'info',
                            hideAfter: 3000
                        });

                        $.ajax({
                            url: "{{ route('kfa.getmedicationid') }}",
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                kode_barang: kode
                            },
                            success: function (res) {
                                if (res.status === 'success') {
                                    $.toast({
                                        heading: 'Sukses',
                                        text: `${res.message}<br><small>UUID: ${res.uuid}</small>`,
                                        position: 'top-right',
                                        loaderBg: '#51A351',
                                        icon: 'success',
                                        hideAfter: 4000
                                    });
                                    setTimeout(() => { location.reload(); }, 2000);
                                } else {
                                    $.toast({
                                        heading: 'Gagal',
                                        text: res.message || 'Gagal mengirim data ke SATUSEHAT.',
                                        position: 'top-right',
                                        loaderBg: '#FF5733',
                                        icon: 'error',
                                        hideAfter: 4000
                                    });
                                }
                            },
                            error: function (xhr) {
                                const errMsg = xhr.responseJSON?.message || 'Terjadi kesalahan koneksi.';
                                $.toast({
                                    heading: 'Error',
                                    text: errMsg,
                                    position: 'top-right',
                                    loaderBg: '#FF5733',
                                    icon: 'error',
                                    hideAfter: 4000
                                });
                                console.error(xhr.responseText);
                            }
                        });
                    })();


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
                                text: 'Mapping data obat berhasil disimpan.',
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
                                text: res.message || 'Gagal menyimpan data mapping obat.',
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
                            text: 'Terjadi kesalahan saat menyimpan data mapping obat.',
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

        {{-- checkbox buat kirim --}}
        <script>
            $(document).ready(function () {

                // ✅ Pilih semua checkbox
                $('#checkAll').on('change', function () {
                    $('.checkbox-item').prop('checked', $(this).is(':checked'));
                });

                // ✅ Tombol Kirim Dipilih
                $('#btnKirimDipilih').on('click', function () {
                    const selected = $('.checkbox-item:checked').map(function () {
                        return {
                            kode: $(this).data('kode'),
                            kfa: $(this).data('kfa')
                        };
                    }).get();

                    // Cek apakah ada data terpilih
                    if (selected.length === 0) {
                        $.toast({
                            heading: 'Peringatan',
                            text: 'Tidak ada data yang dipilih.',
                            position: 'top-right',
                            loaderBg: '#FF5733',
                            icon: 'warning',
                            hideAfter: 3000
                        });
                        return;
                    }

                    // SweetAlert2 v7.x syntax
                    swal({
                        title: 'Kirim Data Terpilih?',
                        text: 'Akan mengirim ' + selected.length + ' data ke SATUSEHAT.',
                        type: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Kirim',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33'
                    }).then(function (result) {
                        if (!result.value) return; // batal

                        // 🔍 Validasi KFA kosong
                        const invalidItems = selected.filter(function (item) {
                            const kfaVal = (item.kfa || '').toString().trim();
                            return kfaVal === '';
                        });

                        if (invalidItems.length > 0) {
                            swal({
                                title: 'Data Belum Lengkap!',
                                text: invalidItems.length + ' dari ' + selected.length +
                                    ' data belum memiliki kode KFA.\nSilakan mapping terlebih dahulu.',
                                type: 'warning',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#f0ad4e'
                            });
                            return;
                        }

                        // ✅ Semua siap dikirim
                        console.log('Data siap dikirim:', selected);

                        let successCount = 0;
                        let failCount = 0;

                        $.toast({
                            heading: 'Mengirim...',
                            text: 'Mengirim ' + selected.length + ' data ke SATUSEHAT...',
                            position: 'top-right',
                            loaderBg: '#5bc0de',
                            icon: 'info',
                            hideAfter: 3000
                        });

                        // 🚀 Kirim data satu per satu (pakai recursive loop, gak pakai async/await langsung)
                        function sendNext(index) {
                            if (index >= selected.length) {
                                // Selesai semua
                                swal({
                                    title: 'Proses Selesai',
                                    text: 'Sukses: ' + successCount + ' | Gagal: ' + failCount +
                                        ' dari total ' + selected.length + ' data.',
                                    type: (failCount === 0) ? 'success' : 'warning',
                                    confirmButtonText: 'OK'
                                }).then(function () {
                                    location.reload();
                                });
                                return;
                            }

                            const item = selected[index];
                            console.log('🔹 Mengirim:', item.kode, 'KFA:', item.kfa);

                            $.ajax({
                                url: "{{ route('kfa.getmedicationid') }}",
                                type: "POST",
                                data: {
                                    _token: "{{ csrf_token() }}",
                                    kode_barang: item.kode
                                },
                                success: function (res) {
                                    if (res.status === 'success') {
                                        successCount++;
                                        console.log('✅ Sukses:', item.kode, res.uuid);

                                        $.toast({
                                            heading: 'Sukses',
                                            text: 'Obat ' + item.kode + ' berhasil dikirim. <br><small>UUID: ' + res.uuid + '</small>',
                                            position: 'top-right',
                                            loaderBg: '#51A351',
                                            icon: 'success',
                                            hideAfter: 2500
                                        });
                                    } else {
                                        failCount++;
                                        console.warn('⚠️ Gagal:', item.kode, res.message);

                                        $.toast({
                                            heading: 'Gagal',
                                            text: 'Obat ' + item.kode + ': ' + (res.message || 'Gagal kirim.'),
                                            position: 'top-right',
                                            loaderBg: '#FF5733',
                                            icon: 'error',
                                            hideAfter: 3000
                                        });
                                    }

                                    // lanjut ke item berikut
                                    setTimeout(function () {
                                        sendNext(index + 1);
                                    }, 500);
                                },
                                error: function (xhr) {
                                    failCount++;
                                    console.error('❌ Error:', item.kode, xhr.responseText);

                                    $.toast({
                                        heading: 'Error',
                                        text: 'Obat ' + item.kode + ' gagal dikirim. <br><small>' +
                                            (xhr.responseJSON?.message || 'Terjadi kesalahan koneksi.') + '</small>',
                                        position: 'top-right',
                                        loaderBg: '#FF5733',
                                        icon: 'error',
                                        hideAfter: 3500
                                    });

                                    // lanjut ke item berikut meski error
                                    setTimeout(function () {
                                        sendNext(index + 1);
                                    }, 500);
                                }
                            });
                        }

                        // Mulai dari item pertama
                        sendNext(0);
                    });
                });
            });
        </script>



    @endpush


@endsection 