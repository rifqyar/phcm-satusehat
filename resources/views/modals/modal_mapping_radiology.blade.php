<!-- Modal Mapping Obat -->
<div class="modal fade" id="modalMapping" tabindex="-1" aria-labelledby="modalMappingLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalMappingLabel">Mapping Data Tindakan Radiology</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>

            </div>

            <div class="modal-body">
                <form id="formMappingRadiology">
                    @csrf
                    <input type="hidden" name="id" id="id_tindakan">

                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="form-label">No</label>
                            <input type="text" class="form-control" id="no" readonly>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Nama Group Tindakan</label>
                            <input type="text" class="form-control" id="nama_grup" readonly>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Nama Tindakan</label>
                            <input type="text" class="form-control" id="nama_tindakan" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kode LOINC</label>
                            <input type="text" class="form-control" id="satusehat_code" name="satusehat_code"
                                placeholder="Masukkan kode LOINC">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama LOINC</label>
                            <input type="text" class="form-control" id="satusehat_display" name="satusehat_display"
                                placeholder="Masukkan nama LOINC">
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="submit" form="formMappingRadiology" class="btn btn-success">Simpan Mapping</button>
            </div>

        </div>
    </div>
</div>