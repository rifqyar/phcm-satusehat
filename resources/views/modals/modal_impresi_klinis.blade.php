<!-- Modal Resume Medis Detail -->
<div class="modal fade" id="modalResumeMedis" tabindex="-1" role="dialog">
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

                    <div class="ribbon ribbon-info" id="integrasi_prognosis">
                        <i class="fas fa-info-circle text-white"></i>
                        Data Prognosis Belum Integrasi
                    </div>
                    <div class="ribbon ribbon-success" id="success_prognosis" style="display:none">
                        <i class="fas fa-check-circle text-white"></i>
                        Data Prognosis Sudah Integrasi
                    </div>
                    <div class="ribbon ribbon-danger" id="failed_prognosis" style="display:none">
                        <i class="fas fa-times-circle text-white"></i>
                        Data Prognosis Gagal Integrasi
                    </div>

                    <div class="card-body ribbon-content">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <h6 class="font-weight-bold">Keluhan Utama</h6>
                                <p id="keluhan" class="border p-2 bg-light">-</p>
                            </div>

                            <div class="col-md-6 mb-3">
                                <h6 class="font-weight-bold">Diagnosa</h6>
                                <p id="diagnosa" class="border p-2 bg-light">-</p>
                            </div>

                            <div class="col-md-6 mb-3">
                                <h6 class="font-weight-bold">Prognosis</h6>
                                <select style="width: 100%;" id="prognosis" name="prognosis" class="form-control">
                                    <option value="170968001" selected>Prognosis good / Baik</option>
                                    <option value="65872000">Fair prognosis / Cukup Baik</option>
                                    <option value="67334001">Guarded prognosis / Cenderung Tidak Baik</option>
                                    <option value="170969009">Prognosis bad / Buruk</option>
                                </select>
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
