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
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"
                            placeholder="Tambahkan deskripsi..."></textarea>
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

                <!-- Hasil KFA -->
                <div class="table-responsive" id="tableKfaWrapper" style="display:none;">
                    <table class="table table-bordered table-striped">
                        <thead class="table-info">
                            <tr>
                                <th>Kode KFA</th>
                                <th>Nama Produk</th>
                                <th>Update Terakhir</th>
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
    const tableWrapper = document.getElementById('tableKfaWrapper');
    const tbody = document.getElementById('tbodyKfa');
    const formCariKfa = document.getElementById('formCariKfa');
    const inputKeyword = document.getElementById('keyword_kfa');

    // 🔄 Spinner saat loading
    function showLoading() {
        tableWrapper.style.display = 'block';
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4">
                    <div class="spinner-border text-info" role="status" style="width: 2rem; height: 2rem;">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <div class="mt-2 text-secondary">Memuat data KFA...</div>
                </td>
            </tr>
        `;
    }

    // 🔍 Event: cari produk KFA
    formCariKfa.addEventListener('submit', function (e) {
        e.preventDefault();

        const tipe = document.getElementById('tipe_pencarian').value; // keyword / template_code
        const keyword = inputKeyword.value.trim();

        if (!keyword) {
            alert('Masukkan keyword atau code untuk mencari data KFA.');
            return;
        }

        showLoading();

        const queryParam = `${tipe}=${encodeURIComponent(keyword)}`;

        fetch(`/satusehat/kfa-search?${queryParam}`)
            .then(response => response.json())
            .then(data => {
                tbody.innerHTML = '';
                const items = Array.isArray(data) ? data : [];

                if (items.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-center">Tidak ada data ditemukan</td></tr>`;
                    tableWrapper.style.display = 'block';
                    return;
                }

                // Isi tabel hasil pencarian
                items.forEach(item => {
                    const row = `
                        <tr>
                            <td>${item.kfa_code || '-'}</td>
                            <td>${item.display_name || item.name || '-'}</td>
                            <td>${item.updated_at || '-'}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-success btnPilihKfa" 
                                    data-kfa="${item.kfa_code}" 
                                    data-nama="${item.display_name || item.name}">
                                    Pilih
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.insertAdjacentHTML('beforeend', row);
                });

                tableWrapper.style.display = 'block';
            })
            .catch(err => {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-3">Terjadi kesalahan saat memuat data KFA.</td></tr>`;
                tableWrapper.style.display = 'block';
            });
    });

    // ⏎ Enter di input = submit form
    inputKeyword.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            formCariKfa.dispatchEvent(new Event('submit'));
        }
    });

    // 🟩 Event: pilih hasil KFA
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('btnPilihKfa')) {
            const kode = e.target.dataset.kfa;
            const nama = e.target.dataset.nama;
            document.getElementById('kode_kfa').value = kode;
            document.getElementById('nama_kfa').value = nama;
            tableWrapper.style.display = 'none';
        }
    });
</script>
