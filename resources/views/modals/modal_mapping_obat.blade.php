<!-- Modal Mapping Obat -->
<div class="modal fade" id="modalMapping" tabindex="-1" aria-labelledby="modalMappingLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalMappingLabel">Mapping Data Obat</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                {{-- FORM UTAMA MAPPING --}}
                <form id="formMappingObat">
                    @csrf
                    <input type="hidden" name="id" id="id_obat">

                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="form-label">No</label>
                            <input type="text" class="form-control" id="no" readonly>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Kode Barang Centra</label>
                            <input type="text" class="form-control" id="kode_barang" readonly>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Nama Barang</label>
                            <input type="text" class="form-control" id="nama_barang" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kode KFA</label>
                            <input type="text" class="form-control" id="kode_kfa" name="kode_kfa" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama KFA</label>
                            <input type="text" class="form-control" id="nama_kfa" name="nama_kfa" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jenis</label>
                            <input type="text" class="form-control" id="jenis_obat" name="jenis_obat" readonly>
                            <input type="hidden" class="form-control" id="is_compound" name="is_compound" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" placeholder="Tambahkan deskripsi..."></textarea>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_non_farmasi" name="is_non_farmasi"
                            value="1">
                        <label class="form-check-label" for="is_non_farmasi">
                            Tandai sebagai Non Farmasi
                        </label>
                    </div>
                </form>

                <hr>

                {{-- FORM PENCARIAN KFA --}}
                <form id="formCariKfa" class="mb-3">
                    <div class="row align-items-end">
                        <!-- 🔽 Pilihan tipe pencarian -->
                        <div class="col-md-3">
                            <label class="form-label">Tipe Pencarian</label>
                            <select class="form-select" id="tipe_pencarian">
                                <option value="keyword" selected>Medicine</option>
                                <option value="template_code">Code</option>
                            </select>
                        </div>

                        <!-- 🔍 Input keyword -->
                        <div class="col-md-5">
                            <label class="form-label">Cari Produk KFA</label>
                            <input type="text" class="form-control" id="keyword_kfa"
                                placeholder="Masukkan keyword produk...">
                        </div>

                        <!-- 🔘 Tombol cari -->
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-info w-100" id="btnCariKfa">
                                <i class="fa fa-search"></i> Cari KFA
                            </button>
                        </div>
                    </div>
                </form>

                <!-- 🔎 Quick Search Lokal -->
                <div id="quickSearchWrapper" class="mb-2" style="display:none;">
                    <input type="text" id="quickSearch" class="form-control"
                        placeholder="🔎 Filter cepat berdasarkan nama produk...">
                </div>

                <!-- Hasil KFA -->
                <div class="table-responsive" id="tableKfaWrapper" style="display:none;">
                    <table class="table table-bordered table-striped">
                        <thead class="table-info">
                            <tr>
                                <th>Kode KFA</th>
                                <th>Nama Produk</th>
                                <th>Update Terakhir</th>
                                <th>Jenis</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyKfa"></tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" id="btnSaveMapping" class="btn btn-primary">Simpan Mapping</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        /**
         * 🟢 Saat modal dibuka (show.bs.modal)
         * Ambil data dari tombol pemicu (data-*) dan isi form utama modal
         */
        $(document).on('show.bs.modal', '#modalMapping', function(event) {
            const button = $(event.relatedTarget); // tombol yang diklik
            const modal = $(this);

            // Ambil semua atribut data-* dari tombol
            const id = button.data('id');
            const no = button.data('no');
            const kode = button.data('kode');
            const nama = button.data('nama');
            const kfa = button.data('kfa');
            const namakfa = button.data('namakfa');
            const jenis = button.data('jenis');
            const isCompound = button.data('is-compound');
            const deskripsi = button.data('deskripsi');

            console.log('Modal dibuka untuk:', {
                id,
                kode,
                nama
            });

            // Isi field di dalam modal
            modal.find('#id_obat').val(id || '');
            modal.find('#no').val(no || '');
            modal.find('#kode_barang').val(kode || '');
            modal.find('#nama_barang').val(nama || '');
            modal.find('#kode_kfa').val(kfa || '');
            modal.find('#nama_kfa').val(namakfa || '');
            modal.find('#jenis_obat').val(jenis || '');
            modal.find('#is_compound').val(isCompound || 0);
            modal.find('#deskripsi').val(deskripsi || '');
            modal.find('#is_non_farmasi').prop('checked', kfa === '000');
            if (kfa === '000') {
                $('#keyword_kfa').prop('disabled', true);
                $('#tipe_pencarian').prop('disabled', true);
                $('#btnCariKfa').prop('disabled', true);
            } else {
                $('#keyword_kfa').prop('disabled', false);
                $('#tipe_pencarian').prop('disabled', false);
                $('#btnCariKfa').prop('disabled', false);
            }


            // Reset hasil pencarian KFA tiap kali modal baru dibuka
            $('#tbodyKfa').empty();
            $('#tableKfaWrapper').hide();
            $('#quickSearchWrapper').hide();
            $('#keyword_kfa').val('');
        });

        $('#is_non_farmasi').on('change', function() {
            if ($(this).is(':checked')) {
                $('#kode_kfa').val('000');
                $('#nama_kfa').val('Non Farmasi');
                $('#jenis_obat').val('-');
                $('#is_compound').val(0);

                // 🔒 disable pencarian KFA
                $('#keyword_kfa').prop('disabled', true);
                $('#tipe_pencarian').prop('disabled', true);
                $('#btnCariKfa').prop('disabled', true);
            } else {
                // 🔓 enable kembali
                $('#keyword_kfa').prop('disabled', false);
                $('#tipe_pencarian').prop('disabled', false);
                $('#btnCariKfa').prop('disabled', false);
            }
        });




        /**
         * 🟡 Event: submit form pencarian KFA
         * Mencegah reload, ambil data dari endpoint /satusehat/kfa-search
         */
        $(document).off('submit', '#formCariKfa').on('submit', '#formCariKfa', function(e) {
            e.preventDefault();

            const tipe = $('#tipe_pencarian').val();
            const keyword = $('#keyword_kfa').val().trim();
            const tbody = $('#tbodyKfa');
            const tableWrapper = $('#tableKfaWrapper');
            const quickSearchWrapper = $('#quickSearchWrapper');

            if (!keyword) {
                alert('Masukkan keyword atau code untuk mencari data KFA.');
                return;
            }

            // tampilkan spinner loading
            tableWrapper.show();
            quickSearchWrapper.hide();
            tbody.html(`
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <div class="spinner-border text-info" role="status"></div>
                        <div class="mt-2 text-secondary">Memuat data KFA...</div>
                    </td>
                </tr>
            `);

            const queryParam = `${tipe}=${encodeURIComponent(keyword)}`;

            // fetch data dari endpoint Laravel
            // fetch(`/satusehat/kfa-search?${queryParam}`)
            fetch(`{{ route('kfa.search', '')}}?${queryParam}`)
                .then(res => res.json())
                .then(data => {
                    tbody.empty();

                    if (!Array.isArray(data) || data.length === 0) {
                        tbody.html(
                            `<tr><td colspan="5" class="text-center text-muted py-3">Tidak ada data ditemukan</td></tr>`
                            );
                        tableWrapper.show();
                        return;
                    }

                    // render hasil pencarian
                    data.forEach(item => {
                        const row = `
                            <tr>
                                <td>${item.kfa_code || '-'}</td>
                                <td>${item.display_name || item.name || '-'}</td>
                                <td>${item.updated_at || '-'}</td>
                                <td>${item.is_compound === true ? 'Compound' : item.is_compound === false ? 'Non-compound' : '-'}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success btnPilihKfa"
                                        data-kfa="${item.kfa_code || ''}"
                                        data-nama="${item.display_name || item.name || ''}"
                                        data-jenis="${item.is_compound === true ? 'Compound' : item.is_compound === false ? 'Non-compound' : '-'}"
                                        data-is-compound="${item.is_compound ? 1 : 0}">
                                        Pilih
                                    </button>
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    });

                    quickSearchWrapper.show();
                })
                .catch(err => {
                    console.error('Error:', err);
                    tbody.html(
                        `<tr><td colspan="5" class="text-center text-danger py-3">Terjadi kesalahan saat memuat data KFA.</td></tr>`
                        );
                });
        });


        /**
         * 🔍 Filter cepat hasil KFA (client-side search)
         */
        $(document).off('input', '#quickSearch').on('input', '#quickSearch', function() {
            const query = $(this).val().toLowerCase();
            $('#tbodyKfa tr').each(function() {
                const nama = $(this).find('td:nth-child(2)').text().toLowerCase();
                $(this).toggle(nama.includes(query));
            });
        });


        /**
         * 🟢 Tombol "Pilih" pada hasil KFA
         * Mengisi form utama dengan data yang dipilih dari hasil pencarian
         */
        $(document).off('click', '.btnPilihKfa').on('click', '.btnPilihKfa', function() {
            const kode = $(this).data('kfa');
            const nama = $(this).data('nama');
            const jenis = $(this).data('jenis');
            const isCompound = $(this).data('is-compound');

            $('#kode_kfa').val(kode);
            $('#nama_kfa').val(nama);
            $('#jenis_obat').val(jenis);
            $('#is_compound').val(isCompound);

            // Sembunyikan tabel hasil
            $('#tableKfaWrapper').hide();
            $('#quickSearchWrapper').hide();
        });
    });
</script>
