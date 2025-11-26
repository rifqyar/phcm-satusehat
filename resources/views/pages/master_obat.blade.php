@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Master Obat</h4>

            <!-- 📊 Statistik -->
            <div class="row mb-2">
                <!-- Total -->
                <div class="col-md-4 mb-3">
                    <a href="javascript:void(0)" data-status="all" class="text-decoration-none card-status"
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
                    <a href="javascript:void(0)" data-status="mapped" class="text-decoration-none card-status""
                                class=" text-decoration-none">
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
                    <a href="javascript:void(0)" data-status="unmapped" class="text-decoration-none card-status"
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

            <div class="mb-3">
                <select id="status" class="form-control w-25">
                    <option value="all">Semua</option>
                    <option value="mapped">Mapped</option>
                    <option value="unmapped">Unmapped</option>
                </select>
            </div>

            <div class="table-responsive">
                <table id="obatTable" class="table table-striped table-bordered w-100">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kode Centra</th>
                            <th>Nama Obat</th>
                            <th>Kode KFA</th>
                            <th>Nama KFA</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
                @include('modals.modal_mapping_obat')
            </div>
        </div>
    </div>
@endsection

@push('after-script')
    <script>
        $(document).ready(function () {
            var searchDelay = null;

            var table = $('#obatTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('master_obat.datatable') }}",
                    type: "POST",
                    data: function (d) {
                        d._token = "{{ csrf_token() }}";
                        d.status = $('#status').val();
                        d.kode = "{{ $kode ?? '' }}";
                    }
                },
                columns: [
                    { data: 'ID', name: 'ID' },
                    { data: 'KDBRG_CENTRA', name: 'KDBRG_CENTRA' },
                    { data: 'NAMABRG', name: 'NAMABRG' },
                    { data: 'KD_BRG_KFA', name: 'KD_BRG_KFA' },
                    { data: 'NAMABRG_KFA', name: 'NAMABRG_KFA' },
                    { data: 'status_mapping', orderable: false, searchable: false },
                    { data: 'action', orderable: false, searchable: false }
                ],
                order: [[2, 'asc']]
            });

            // 🔍 delay 0.5 detik saat search
            $('#obatTable_filter input')
                .unbind()
                .bind('input', function () {
                    clearTimeout(searchDelay);
                    searchDelay = setTimeout(() => {
                        table.search(this.value).draw();
                    }, 500);
                });

            // 🔄 filter dropdown status
            $('#status').on('change', function () {
                table.ajax.reload();
            });

            // 🧩 Klik card = ubah dropdown & reload tabel
            $('.card-status').on('click', function (e) {
                e.preventDefault();
                var newStatus = $(this).data('status');
                $('#status').val(newStatus);
                table.ajax.reload();

                $('.card-status .card').removeClass('border border-primary');
                $(this).find('.card').addClass('border border-primary shadow-lg');
            });

            // 🔗 Delegasi event untuk tombol Mapping
            $('#obatTable').on('click', '.btnMappingObat', function () {
                const btn = $(this);
                const modal = $('#modalMapping');

                modal.find('#id_obat').val(btn.data('id') || '');
                modal.find('#kode_barang').val(btn.data('kode') || '');
                modal.find('#nama_barang').val(btn.data('nama') || '');
                modal.find('#kode_kfa').val(btn.data('kfa') || '');
                modal.find('#nama_kfa').val(btn.data('namakfa') || '');
                modal.find('#jenis_obat').val(btn.data('jenis') || '');
                modal.find('#is_compound').val(btn.data('is-compound') || 0);
                modal.find('#deskripsi').val(btn.data('deskripsi') || '');
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
@endpush