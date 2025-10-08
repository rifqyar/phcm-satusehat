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
                            <input type="text" class="form-control" id="kode_kfa" name="kode_kfa"
                                placeholder="Masukkan kode KFA">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama KFA</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="nama_kfa" name="nama_kfa"
                                    placeholder="Masukkan nama KFA">
                                <button type="button" class="btn btn-info" id="btnCariKfa">
                                    <i class="fa fa-search"></i> Cari
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"
                            placeholder="Tambahkan deskripsi..."></textarea>
                    </div>
                </form>

                <hr>

                <!-- Hasil KFA -->
                <div class="table-responsive" id="tableKfaWrapper" style="display:none;">
                    <table class="table table-bordered table-striped">
                        <thead class="table-info">
                            <tr>
                                <th>Kode KFA</th>
                                <th>Nama Produk</th>
                                <th>Nama Dagang</th>
                                <th>Produsen</th>
                                <th>Form</th>
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
    document.getElementById('btnCariKfa').addEventListener('click', function () {
        const keyword = document.getElementById('nama_kfa').value.trim();
        if (!keyword) {
            alert('Masukkan keyword untuk mencari data KFA.');
            return;
        }

        fetch(`/satusehat/kfa-search?keyword=${encodeURIComponent(keyword)}`)
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('tbodyKfa');
                tbody.innerHTML = '';
                const items = data?.items?.data || [];

                if (items.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center">Tidak ada data ditemukan</td></tr>`;
                    document.getElementById('tableKfaWrapper').style.display = 'block';
                    return;
                }

                items.forEach(item => {
                    const row = `
                    <tr>
                        <td>${item.kfa_code || '-'}</td>
                        <td>${item.name || '-'}</td>
                        <td>${item.nama_dagang || '-'}</td>
                        <td>${item.manufacturer || '-'}</td>
                        <td>${item.dosage_form?.name || '-'}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-success btnPilihKfa" 
                                data-kfa="${item.kfa_code}" 
                                data-nama="${item.name}">
                                Pilih
                            </button>
                        </td>
                    </tr>
                `;
                    tbody.insertAdjacentHTML('beforeend', row);
                });

                document.getElementById('tableKfaWrapper').style.display = 'block';
            })
            .catch(err => {
                console.error(err);
                alert('Terjadi kesalahan saat memuat data KFA.');
            });
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('btnPilihKfa')) {
            const kode = e.target.dataset.kfa;
            const nama = e.target.dataset.nama;
            document.getElementById('kode_kfa').value = kode;
            document.getElementById('nama_kfa').value = nama;
            document.getElementById('tableKfaWrapper').style.display = 'none';
        }
    });

</script>