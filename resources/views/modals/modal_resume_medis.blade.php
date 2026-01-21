<!-- Modal Resume Medis Detail -->
<div class="modal fade" id="modalResumeMedis" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">Detail Resume Medis Pasien</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Data Pasien -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info ">
                                <h6 class="m-0 text-white"><i class="fas fa-user"></i> Data Pasien</h6>
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
                        <div class="card">
                            <div class="card-header bg-primary ">
                                <h6 class="m-0 text-white"><i class="fas fa-hospital"></i> Data Kunjungan</h6>
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

                <!-- Data Alergi -->
                <div class="card">
                    <div class="card-header bg-info ">
                        <h6 class="m-0 text-white"><i class="fas fa-hospital"></i> Data Alergi</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" id="tableAlergi">
                            <table class="table table-sm">
                                <thead>
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

                <!-- Resume Medis -->
                <div class="card">
                    <div class="card-header bg-success">
                        <h6 class="m-0 text-white"><i class="fas fa-notes-medical"></i> Resume Medis</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <h6 class="font-weight-bold">Keluhan Utama</h6>
                                <p id="keluhan" class="border p-2 bg-light">-</p>
                            </div>

                            <div class="col-md-12 mb-3">
                                <h6 class="font-weight-bold">Tanda Vital</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Tekanan Darah</label>
                                        <p id="td" class="border p-2 bg-light">-</p>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Denyut Jantung</label>
                                        <p id="dj" class="border p-2 bg-light">-</p>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Pernapasan</label>
                                        <p id="p" class="border p-2 bg-light">-</p>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Suhu</label>
                                        <p id="suhu" class="border p-2 bg-light">-</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Tinggi Badan</label>
                                        <p id="tb" class="border p-2 bg-light">-</p>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Berat Badan</label>
                                        <p id="bb" class="border p-2 bg-light">-</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label>IMT</label>
                                        <p id="IMT" class="border p-2 bg-light">-</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <h6 class="font-weight-bold">Diagnosa</h6>
                                <p id="diagnosa" class="border p-2 bg-light">-</p>
                            </div>

                            <div class="col-md-12 mb-3">
                                <h6 class="font-weight-bold">Terapi / Pengobatan</h6>
                                <p id="terapi" class="border p-2 bg-light">-</p>
                            </div>

                            <div class="col-md-12 mb-3">
                                <h6 class="font-weight-bold">Tindakan</h6>
                                <p id="tindakan" class="border p-2 bg-light">-</p>
                            </div>

                            <div class="col-md-12 mb-3">
                                <h6 class="font-weight-bold">Anjuran</h6>
                                <p id="anjuran" class="border p-2 bg-light">-</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Integrasi -->
                <div class="card">
                    <div class="card-body text-center">
                        <div id="integrasi_resume" class="alert alert-info" style="display:none;">
                            <i class="fas fa-info-circle"></i> Belum Terintegrasi dengan SatuSehat
                        </div>
                        <div id="success_resume" class="alert alert-success" style="display:none;">
                            <i class="fas fa-check-circle"></i> Sudah Terintegrasi dengan SatuSehat
                        </div>
                        <div id="failed_resume" class="alert alert-danger" style="display:none;">
                            <i class="fas fa-times-circle"></i> Gagal Integrasi dengan SatuSehat
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <a href="javascript:void(0)" class="btn btn-primary" id="btn-send-satusehat">
                    <i class="fas fa-link mr-2"></i>Kirim Satu Sehat
                </a>
            </div>
        </div>
    </div>
</div>
