<!-- Modal Resume Medis Detail -->
<div class="modal fade" id="modalCarePlan" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Resume Medis Pasien</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Data Pasien -->
                    <div class="col-md-6">
                        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                            <div class="card-header bg-info text-white">
                                <h6 class="m-0 text-white"><i class="fas fa-user text-white"></i> Data Pasien</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">Nama Pasien</th>
                                        <td id="nama_pasien">-</td>
                                    </tr>
                                    <tr>
                                        <th>No. RM</th>
                                        <td id="no_rm">-</td>
                                    </tr>
                                    <tr>
                                        <th>No. Peserta</th>
                                        <td id="no_peserta">-</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Data Kunjungan -->
                    <div class="col-md-6">
                        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                            <div class="card-header bg-primary text-white">
                                <h6 class="m-0 text-white"><i class="fas fa-hospital text-white"></i> Data Kunjungan</h6>
                            </div>

                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">No. Karcis</th>
                                        <td id="no_karcis">-</td>
                                    </tr>
                                    <tr>
                                        <th>Dokter</th>
                                        <td id="dokter">-</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resume Medis -->
                <div class="ribbon-wrapper card shadow-lg border-0 rounded-4 overflow-hidden mt-4">
                    <div class="card-header bg-success text-white">
                        <h6 class="m-0 text-white"><i class="fas fa-notes-medical text-white"></i> Resume Medis</h6>
                    </div>

                    <div class="ribbon ribbon-info" id="integrasi_care_plan">
                        <i class="fas fa-info-circle text-white"></i>
                        Data Care Plan Belum Integrasi
                    </div>
                    <div class="ribbon ribbon-success" id="success_care_plan" style="display:none">
                        <i class="fas fa-check-circle text-white"></i>
                        Data Care Plan Sudah Integrasi
                    </div>
                    <div class="ribbon ribbon-danger" id="failed_care_plan" style="display:none">
                        <i class="fas fa-times-circle text-white"></i>
                        Data Care Plan Gagal Integrasi
                    </div>

                    <div class="card-body ribbon-content">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <h6 class="font-weight-bold">Rencana Perawatan</h6>
                                <p id="plan" class="border p-2 bg-light">-</p>
                            </div>

                            <div class="col-md-6 mb-3">
                                <h6 class="font-weight-bold">Diagnosa</h6>
                                <p id="diagnosa" class="border p-2 bg-light">-</p>
                            </div>
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
