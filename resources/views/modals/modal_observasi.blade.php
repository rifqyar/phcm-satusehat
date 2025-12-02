<!-- Modal Mapping Obat -->
<div class="modal fade" id="modalObservasi" tabindex="-1" aria-labelledby="modalObservasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-info text-white">
                <h5 class="modal-title text-white" id="modalMappingLabel">Detail Tindakan Pasien</h5>
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

                <div class="card" id="pemeriksaan_fisik">
                    <div class="ribbon-wrapper card shadow-lg border-0 rounded-4 overflow-hidden">
                        <div
                            class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                            <h4 class="mb-0 text-white">
                                🩺 Detail TTV Pasien
                            </h4>
                            <span class="badge bg-light text-primary fs-6" id="TANGGAL">
                                <i class="fas fa-calendar mr-1"></i> 31 Oktober 2025
                            </span>
                        </div>

                        <div class="ribbon ribbon-success" id="success_anamnese" style="display:none">
                            <i class="fas fa-check-circle text-white"></i>
                            Hasil TTV Sudah Integrasi
                        </div>

                        <div class="ribbon ribbon-danger" id="failed_anamnese">
                            <i class="fas fa-times-circle text-white"></i>
                            Hasil TTV Belum Integrasi
                        </div>

                        <div class="ribbon-content card-body bg-light p-4">
                            <!-- Anamnese -->
                            <div class="mb-4">
                                <h6 class="text-uppercase text-secondary font-weight-bold mb-2">
                                    <i class="fas fa-notes-medical mr-2 text-primary"></i> Anamnese
                                    Pasien
                                </h6>
                                <div class="p-3 bg-white rounded border">
                                    <p class="mb-0 text-muted" id="ANAMNESE">
                                        (data anamnese diisi otomatis dari JSON)
                                    </p>
                                </div>
                            </div>

                            <!-- Pemeriksaan Fisik -->
                            <div class="row">
                                <div class="col-md-4 mb-4">
                                    <div class="bg-white border rounded p-3 h-100 shadow-sm d-flex align-items-center">
                                        <div class="icon-circle bg-primary text-white rounded-circle p-3 mr-3">
                                            <i class="fas fa-tachometer-alt fa-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-bold mb-0 text-secondary">
                                                Tekanan Darah
                                            </h6>
                                            <p class="mb-0 h5 font-weight-semibold text-dark" id="TD">-</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-4">
                                    <div class="bg-white border rounded p-3 h-100 shadow-sm d-flex align-items-center">
                                        <div class="icon-circle bg-danger text-white rounded-circle p-3 mr-3">
                                            <i class="fas fa-heartbeat fa-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-bold mb-0 text-secondary">
                                                Denyut Jantung
                                            </h6>
                                            <p class="mb-0 h5 font-weight-semibold text-dark" id="DJ">-</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-4">
                                    <div class="bg-white border rounded p-3 h-100 shadow-sm d-flex align-items-center">
                                        <div class="icon-circle bg-success text-white rounded-circle p-3 mr-3">
                                            <i class="fas fa-angle-up fa-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-bold mb-0 text-secondary">
                                                Tinggi Badan
                                            </h6>
                                            <p class="mb-0 h5 font-weight-semibold text-dark" id="TB">-</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-4">
                                    <div class="bg-white border rounded p-3 h-100 shadow-sm d-flex align-items-center">
                                        <div class="icon-circle bg-warning text-white rounded-circle p-3 mr-3">
                                            <i class="fas fa-male fa-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-bold mb-0 text-secondary">
                                                Berat Badan
                                            </h6>
                                            <p class="mb-0 h5 font-weight-semibold text-dark" id="BB">-</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-4">
                                    <div class="bg-white border rounded p-3 h-100 shadow-sm d-flex align-items-center">
                                        <div class="icon-circle bg-info text-white rounded-circle p-3 mr-3">
                                            <i class="fas fa-calculator fa-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-bold mb-0 text-secondary">
                                                IMT (Indeks Massa Tubuh)
                                            </h6>
                                            <p class="mb-0 h5 font-weight-semibold text-dark" id="IMT">-</p>
                                        </div>
                                    </div>
                                </div>
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

<div class="modal fade" id="modalObservasiRanap" tabindex="-1" aria-labelledby="modalObservasiRanapLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-info text-white">
                <h5 class="modal-title text-white" id="modalMappingLabel">Detail Pemeriksaan Fisik Pasien</h5>
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
                                    <span class="font-weight-bold text-white" id="nama_pasien_ri">-</span>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="d-flex justify-content-between border-bottom pb-2">
                                    <span class="font-weight-semibold text-white">No. RM Pasien</span>
                                    <span class="font-weight-bold text-white" id="no_rm_ri">-</span>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="d-flex justify-content-between border-bottom pb-2">
                                    <span class="font-weight-semibold text-white">No. Peserta</span>
                                    <span class="font-weight-bold text-white" id="no_peserta_ri">-</span>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="d-flex justify-content-between border-bottom pb-2">
                                    <span class="font-weight-semibold text-white">No. Reg</span>
                                    <span class="font-weight-bold text-white" id="no_karcis_ri">-</span>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="d-flex justify-content-between border-bottom pb-2">
                                    <span class="font-weight-semibold text-white">Dokter yang Menangani</span>
                                    <span class="font-weight-bold text-white" id="dokter_ri">-</span>
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <h6 class="text-white font-weight-semibold mb-2">
                                    <i class="fa fa-stethoscope mr-1"></i> Data Diagnosa
                                </h6>
                                <div class="bg-secondary rounded p-2" id="data_diagnosa_ri">
                                    <em>Tidak ada data</em>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="card" id="pemeriksaan_fisik_ri">
                    <div class="ribbon-wrapper card shadow-lg border-0 rounded-4 overflow-hidden">
                        <div
                            class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                            <h4 class="mb-0 text-white">
                                🩺 Detail Pemeriksaan Fisik Pasien
                            </h4>
                            <span class="badge bg-light text-primary fs-6" id="TANGGAL_ri">
                                <i class="fas fa-calendar mr-1"></i> 31 Oktober 2025
                            </span>
                        </div>

                        <div class="ribbon ribbon-success" id="success_anamnese_ri" style="display:none">
                            <i class="fas fa-check-circle text-white"></i>
                            Hasil TTV Sudah Integrasi
                        </div>

                        <div class="ribbon ribbon-danger" id="failed_anamnese_ri">
                            <i class="fas fa-times-circle text-white"></i>
                            Hasil TTV Belum Integrasi
                        </div>

                        <div class="ribbon-content card-body bg-light p-4">
                            <!-- Pemeriksaan Fisik -->
                            <div class="row">
                                <div class="col-md-4 mb-4">
                                    <div class="bg-white border rounded p-3 h-100 shadow-sm d-flex align-items-center">
                                        <div class="icon-circle bg-primary text-white rounded-circle p-3 mr-3">
                                            <i class="fas fa-tachometer-alt fa-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-bold mb-0 text-secondary">
                                                Tekanan Darah
                                            </h6>
                                            <p class="mb-0 h5 font-weight-semibold text-dark" id="TD_ri">-</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-4">
                                    <div class="bg-white border rounded p-3 h-100 shadow-sm d-flex align-items-center">
                                        <div class="icon-circle bg-danger text-white rounded-circle p-3 mr-3">
                                            <i class="fas fa-heartbeat fa-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-bold mb-0 text-secondary">
                                                Denyut Jantung
                                            </h6>
                                            <p class="mb-0 h5 font-weight-semibold text-dark" id="NADI_ri">-</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-4">
                                    <div class="bg-white border rounded p-3 h-100 shadow-sm d-flex align-items-center">
                                        <div class="icon-circle bg-danger text-white rounded-circle p-3 mr-3">
                                            <i class="fa fa-leaf fa-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-bold mb-0 text-secondary">Pernafasan</h6>
                                            <p class="mb-0 h5 font-weight-semibold text-dark" id="P_ri">-</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-4">
                                    <div class="bg-white border rounded p-3 h-100 shadow-sm d-flex align-items-center">
                                        <div class="icon-circle bg-danger text-white rounded-circle p-3 mr-3">
                                            <i class="fa fa-thermometer fa-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-bold mb-0 text-secondary">Suhu</h6>
                                            <p class="mb-0 h5 font-weight-semibold text-dark" id="SUHU_ri">-</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-4">
                                    <div class="bg-white border rounded p-3 h-100 shadow-sm d-flex align-items-center">
                                        <div class="icon-circle bg-success text-white rounded-circle p-3 mr-3">
                                            <i class="fas fa-angle-up fa-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-bold mb-0 text-secondary">
                                                Tinggi Badan
                                            </h6>
                                            <p class="mb-0 h5 font-weight-semibold text-dark" id="TB_ri">-</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-4">
                                    <div class="bg-white border rounded p-3 h-100 shadow-sm d-flex align-items-center">
                                        <div class="icon-circle bg-warning text-white rounded-circle p-3 mr-3">
                                            <i class="fas fa-male fa-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-bold mb-0 text-secondary">
                                                Berat Badan
                                            </h6>
                                            <p class="mb-0 h5 font-weight-semibold text-dark" id="BB_ri">-</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-4">
                                    <div class="bg-white border rounded p-3 h-100 shadow-sm d-flex align-items-center">
                                        <div class="icon-circle bg-info text-white rounded-circle p-3 mr-3">
                                            <i class="fas fa-calculator fa-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-bold mb-0 text-secondary">
                                                IMT (Indeks Massa Tubuh)
                                            </h6>
                                            <p class="mb-0 h5 font-weight-semibold text-dark" id="IMT_ri">-</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <table class="table table-bordered table-striped">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 50px;">No</th>
                                        <th>Pemeriksaan</th>
                                        <th style="width: 200px;">Hasil</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <!-- 1. Kepala -->
                                    <tr>
                                        <td>1</td>
                                        <td>Kepala</td>
                                        <td id="KEPALA_ri">-</td>
                                    </tr>

                                    <!-- 2. Muka -->
                                    <tr>
                                        <td>2</td>
                                        <td>Muka</td>
                                        <td id="MUKA_ri">-</td>
                                    </tr>

                                    <!-- 3. Rambut -->
                                    <tr>
                                        <td>3</td>
                                        <td>Rambut</td>
                                        <td id="RAMBUT_ri">-</td>
                                    </tr>

                                    <!-- 4. Mata -->
                                    <tr>
                                        <td>4</td>
                                        <td>Mata</td>
                                        <td id="MATA_ri">-</td>
                                    </tr>

                                    <!-- 5. Telinga -->
                                    <tr>
                                        <td>5</td>
                                        <td>Telinga</td>
                                        <td id="TELINGA_ri">-</td>
                                    </tr>

                                    <!-- 6. Hidung -->
                                    <tr>
                                        <td>6</td>
                                        <td>Hidung</td>
                                        <td id="HIDUNG_ri">-</td>
                                    </tr>

                                    <!-- 7. Gigi -->
                                    <tr>
                                        <td>7</td>
                                        <td>Gigi</td>
                                        <td id="GIGI_ri">-</td>
                                    </tr>

                                    <!-- 8. Lidah -->
                                    <tr>
                                        <td>8</td>
                                        <td>Lidah</td>
                                        <td id="LIDAH_ri">-</td>
                                    </tr>

                                    <!-- 9. Tenggorokan -->
                                    <tr>
                                        <td>9</td>
                                        <td>Tenggorokan</td>
                                        <td id="TENGGOROKAN_ri">-</td>
                                    </tr>

                                    <!-- 10. Leher -->
                                    <tr>
                                        <td>10</td>
                                        <td>Leher</td>
                                        <td id="LEHER_ri">-</td>
                                    </tr>

                                    <!-- 11. Dada -->
                                    <tr>
                                        <td>11</td>
                                        <td>Dada</td>
                                        <td id="DADA_ri">-</td>
                                    </tr>

                                </tbody>
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
