<!-- Modal Mapping Obat -->
<div class="modal fade" id="modalMapping" tabindex="-1" aria-labelledby="modalMappingLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
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
                            <input type="text" class="form-control" id="nama_kfa" name="nama_kfa"
                                placeholder="Masukkan nama KFA">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"
                            placeholder="Tambahkan deskripsi..."></textarea>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="submit" form="formMappingObat" class="btn btn-success">Simpan Mapping</button>
            </div>

        </div>
    </div>
</div>