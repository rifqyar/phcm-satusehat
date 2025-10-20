<!-- Modal Mapping Obat -->
<div class="modal fade" id="modalAlergi" tabindex="-1" aria-labelledby="modalAlergiLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-info text-white">
                <h5 class="modal-title text-white" id="modalMappingLabel">Detail Alergi Pasien</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>

            </div>

            <div class="modal-body">
                <div class="card bg-info text-white shadow border-0 rounded">
                    <div class="card-body">
                        <div class="card-title mb-4 border-bottom pb-2">
                            <h5 class="text-white font-weight-bold mb-0">
                                <i class="fa fa-user-circle mr-2"></i> Detail Pasien
                            </h5>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="d-flex justify-content-between border-bottom pb-2">
                                    <span class="font-weight-semibold text-white">Nama Pasien</span>
                                    <span class="font-weight-bold text-white" id="nama_pasien">-</span>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="d-flex justify-content-between border-bottom pb-2">
                                    <span class="font-weight-semibold text-white">No. RM Pasien</span>
                                    <span class="font-weight-bold text-white" id="no_rm">-</span>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="d-flex justify-content-between border-bottom pb-2">
                                    <span class="font-weight-semibold text-white">No. Peserta</span>
                                    <span class="font-weight-bold text-white" id="no_peserta">-</span>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="d-flex justify-content-between border-bottom pb-2">
                                    <span class="font-weight-semibold text-white">No. Karcis</span>
                                    <span class="font-weight-bold text-white" id="no_karcis">-</span>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="d-flex justify-content-between border-bottom pb-2">
                                    <span class="font-weight-semibold text-white">Dokter yang Menangani</span>
                                    <span class="font-weight-bold text-white" id="dokter">-</span>
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <h6 class="text-white font-weight-semibold mb-2">
                                    <i class="fa fa-stethoscope mr-1"></i> Data Diagnosa
                                </h6>
                                <div class="bg-secondary rounded p-2" id="data_diagnosa">
                                    <em>Tidak ada data</em>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="card">
                    <div class="card-body">
                        <div class="card-title">
                            <h5 class="text-info">Detail Alergi</h5>
                        </div>

                        <div class="table-responsive" id="tableAlergi">
                            <table class="table table-bordered table-striped">
                                <thead class="table-info">
                                    <tr>
                                        <th>Jenis Alergi</th>
                                        <th>Nama Alergen</th>
                                        <th>Kode Alergen Satu Sehat</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyAlergi"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
