<!-- Modal Mapping Obat -->
<div class="modal fade" id="modalProcedure" tabindex="-1" aria-labelledby="modalProcedureLabel" aria-hidden="true">
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

                <div class="card">
                    <div class="card-body p-b-0">
                        <h4 class="card-title">Detail Tindakan Pasien</h4>
                    </div>
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs customtab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#pemeriksaan_fisik" role="tab">
                                <i class="fas fa-check-circle text-success" id="integrasi_anamnese"
                                    style="display: none"></i>
                                <span>Pemeriksaan Fisik</span>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button"
                                aria-haspopup="true" aria-expanded="false">
                                <span>Penunjang Medis</span>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" id="lab-tab" href="#lab" role="tab" data-toggle="tab"
                                    aria-controls="lab">
                                    <i class="fas fa-check-circle text-success" id="integrasi_lab"
                                        style="display: none"></i>
                                    Laboratorium
                                </a>
                                <a class="dropdown-item" id="rad-tab" href="#rad" role="tab" data-toggle="tab"
                                    aria-controls="rad">
                                    <i class="fas fa-check-circle text-success" id="integrasi_rad"
                                        style="display: none"></i>
                                    Radiologi
                                </a>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tindakan_op" role="tab">
                                <i class="fas fa-check-circle text-success" id="integrasi_op" style="display: none"></i>
                                <span>Tindakan OP</span>
                            </a>
                        </li>
                    </ul>
                    <!-- Tab panes -->
                    <div class="tab-content">
                        <div class="tab-pane active" id="pemeriksaan_fisik" role="tabpanel">
                            <div class="p-20">
                                <div class="ribbon-wrapper card shadow-lg border-0 rounded-4 overflow-hidden">
                                    <div
                                        class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                                        <h4 class="mb-0 text-white">
                                            🩺 Detail Pemeriksaan Fisik Pasien
                                        </h4>
                                        <span class="badge bg-light text-primary fs-6" id="TANGGAL">
                                            <i class="fas fa-calendar mr-1"></i> 31 Oktober 2025
                                        </span>
                                    </div>

                                    <div class="ribbon ribbon-success" id="success_anamnese" style="display:none">
                                        <i class="fas fa-check-circle text-white"></i>
                                        Tindakan Pemeriksaan Fisik Sudah Integrasi
                                    </div>

                                    <div class="ribbon ribbon-danger" id="failed_anamnese">
                                        <i class="fas fa-times-circle text-white"></i>
                                        Tindakan Pemeriksaan Fisik Belum Integrasi
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
                                                <div
                                                    class="bg-white border rounded p-3 h-100 shadow-sm d-flex align-items-center">
                                                    <div
                                                        class="icon-circle bg-primary text-white rounded-circle p-3 mr-3">
                                                        <i class="fas fa-tachometer-alt fa-lg"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="font-weight-bold mb-0 text-secondary">
                                                            Tekanan Darah
                                                        </h6>
                                                        <p class="mb-0 h5 font-weight-semibold text-dark"
                                                            id="TD">-</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-4">
                                                <div
                                                    class="bg-white border rounded p-3 h-100 shadow-sm d-flex align-items-center">
                                                    <div
                                                        class="icon-circle bg-danger text-white rounded-circle p-3 mr-3">
                                                        <i class="fas fa-heartbeat fa-lg"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="font-weight-bold mb-0 text-secondary">
                                                            Denyut Jantung
                                                        </h6>
                                                        <p class="mb-0 h5 font-weight-semibold text-dark"
                                                            id="DJ">-</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-4">
                                                <div
                                                    class="bg-white border rounded p-3 h-100 shadow-sm d-flex align-items-center">
                                                    <div
                                                        class="icon-circle bg-success text-white rounded-circle p-3 mr-3">
                                                        <i class="fas fa-angle-up fa-lg"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="font-weight-bold mb-0 text-secondary">
                                                            Tinggi Badan
                                                        </h6>
                                                        <p class="mb-0 h5 font-weight-semibold text-dark"
                                                            id="TB">-</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-4">
                                                <div
                                                    class="bg-white border rounded p-3 h-100 shadow-sm d-flex align-items-center">
                                                    <div
                                                        class="icon-circle bg-warning text-white rounded-circle p-3 mr-3">
                                                        <i class="fas fa-male fa-lg"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="font-weight-bold mb-0 text-secondary">
                                                            Berat Badan
                                                        </h6>
                                                        <p class="mb-0 h5 font-weight-semibold text-dark"
                                                            id="BB">-</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-4">
                                                <div
                                                    class="bg-white border rounded p-3 h-100 shadow-sm d-flex align-items-center">
                                                    <div
                                                        class="icon-circle bg-info text-white rounded-circle p-3 mr-3">
                                                        <i class="fas fa-calculator fa-lg"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="font-weight-bold mb-0 text-secondary">
                                                            IMT (Indeks Massa Tubuh)
                                                        </h6>
                                                        <p class="mb-0 h5 font-weight-semibold text-dark"
                                                            id="IMT">-</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group" id="curr-icd9-pemeriksaanfisik" style="display: none">
                                            <label>Kode ICD 9-CM saat ini</label>
                                            <input type="text" disabled class="form-control">
                                        </div>
                                        <div class="form-group" id="form-icd-pemeriksaanfisik">
                                            <label for="icd9-pemeriksaanfisik">Kode ICD 9-CM <small
                                                    class="text-danger">*</small></label>
                                            <input type="text" class="form-control" placeholder="Cari Kode ICD 9"
                                                name="icd9-pemeriksaanfisik" id="icd9-pemeriksaanfisik" required>
                                            <input type="hidden" name="kd_icd_pm" id="kd_icd_pm">
                                            <input type="hidden" name="sub_kd_icd_pm" id="sub_kd_icd_pm">
                                        </div>

                                        <div class="form-group" id="btn-simpan-pemeriksaanfisik">
                                            <button class="btn btn-info" onclick="saveICD('pemeriksaanfisik')">Simpan Kode ICD 9</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="tab-pane" id="rad" role="tabpanel">
                            <div class="p-20">
                                <div class="ribbon-wrapper card shadow-lg border-0 rounded-4 overflow-hidden">
                                    <div
                                        class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                                        <h4 class="mb-0 text-white">
                                            🔬 Detail Tindakan Radiologi
                                        </h4>
                                        <span class="badge bg-light text-primary fs-6" id="TANGGAL_RAD">
                                            <i class="fa fa-calendar mr-1"></i>
                                        </span>
                                    </div>

                                    <div class="ribbon ribbon-success" id="success_rad" style="display:none">
                                        <i class="fas fa-check-circle text-white"></i>
                                        Tindakan Radiologi Sudah Integrasi
                                    </div>

                                    <div class="ribbon ribbon-danger" id="failed_rad">
                                        <i class="fas fa-times-circle text-white"></i>
                                        Tindakan Radiologi Belum Integrasi
                                    </div>

                                    <div class="ribbon-content card-body bg-light p-4">
                                        <!-- Daftar Tindakan Lab -->
                                        <div class="mb-4">
                                            <h6 class="text-uppercase text-secondary font-weight-bold mb-3">
                                                <i class="fa fa-flask mr-2 text-danger"></i> Daftar Tindakan Radiologi
                                            </h6>

                                            <div class="table-responsive">
                                                <table class="table table-hover table-bordered bg-white rounded">
                                                    <thead class="thead-light">
                                                        <tr class="text-center">
                                                            <th style="width: 5%;">No</th>
                                                            <th style="width: 25%;">Kode Pemeriksaan</th>
                                                            <th>Nama Pemeriksaan</th>
                                                            <th>Kode Tindakan (ICD9-CM)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tabel_tindakan_rad">
                                                        <tr>
                                                            <td colspan="6" class="text-center text-muted">Data
                                                                tindakan radiologi belum tersedia.</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Kode ICD 9 -->
                                        {{-- <div class="form-group" id="curr-icd9-rad" style="display: none">
                                            <label>Kode ICD 9-CM saat ini</label>
                                            <input type="text" disabled class="form-control">
                                        </div>

                                        <div class="form-group mt-4" id="form-icd-rad">
                                            <label for="icd9-rad">Kode ICD 9-CM <small
                                                    class="text-danger">*</small></label>
                                            <select name="icd9-rad" id="icd9-rad" class="form-control" multiple
                                                style="width: 100%">
                                                <option value=""></option>
                                            </select>
                                        </div> --}}

                                        <div class="form-group" id="btn-simpan-rad">
                                            <button class="btn btn-info" onclick="saveICD('rad')">Simpan Kode ICD 9</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="lab" role="tabpanel">
                            <div class="p-20">
                                <div class="ribbon-wrapper card shadow-lg border-0 rounded-4 overflow-hidden">
                                    <div
                                        class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                                        <h4 class="mb-0 text-white">
                                            🔬 Detail Tindakan Laboratorium
                                        </h4>
                                        <span class="badge bg-light text-primary fs-6" id="TANGGAL_LAB">
                                            <i class="fa fa-calendar mr-1"></i>
                                        </span>
                                    </div>

                                    <div class="ribbon ribbon-success" id="success_lab" style="display:none">
                                        <i class="fas fa-check-circle text-white"></i>
                                        Tindakan Laboratorium Sudah Integrasi
                                    </div>

                                    <div class="ribbon ribbon-danger" id="failed_lab">
                                        <i class="fas fa-times-circle text-white"></i>
                                        Tindakan Laboratorium Belum Integrasi
                                    </div>

                                    <div class="ribbon-content card-body bg-light p-4">
                                        <!-- Daftar Tindakan Lab -->
                                        <div class="mb-4">
                                            <h6 class="text-uppercase text-secondary font-weight-bold mb-3">
                                                <i class="fa fa-flask mr-2 text-danger"></i> Daftar Tindakan
                                                Laboratorium
                                            </h6>

                                            <div class="table-responsive">
                                                <table class="table table-hover table-bordered bg-white rounded">
                                                    <thead class="thead-light">
                                                        <tr class="text-center">
                                                            <th style="width: 5%;">No</th>
                                                            <th style="width: 25%;">Kode Pemeriksaan</th>
                                                            <th>Nama Pemeriksaan</th>
                                                            <th>Kode Tindakan (ICD9-CM)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tabel_tindakan_lab">
                                                        <tr>
                                                            <td colspan="6" class="text-center text-muted">Data
                                                                tindakan lab belum tersedia.</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Kode ICD 9 -->
                                        {{-- <div class="form-group" id="curr-icd9-lab" style="display: none">
                                            <label>Kode ICD 9-CM saat ini</label>
                                            <input type="text" disabled class="form-control">
                                        </div>

                                        <div class="form-group mt-4" id="form-icd-lab">
                                            <label for="icd9-lab">Kode ICD 9-CM <small
                                                    class="text-danger">*</small></label>
                                            <select name="icd9-lab" id="icd9-lab" class="form-control" multiple
                                                style="width: 100%">
                                                <option value=""></option>
                                            </select>
                                        </div> --}}

                                        <div class="form-group" id="btn-simpan-lab">
                                            <button class="btn btn-info" onclick="saveICD('lab')">Simpan Kode ICD 9</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="tindakan_op" role="tabpanel">
                            <div class="p-20">
                                <div class="ribbon-wrapper card shadow-lg border-0 rounded-4 overflow-hidden">
                                    <div
                                        class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                                        <h4 class="mb-0 text-white">
                                            🏥 Detail Tindakan Operasi Pasien
                                        </h4>
                                        <span class="badge bg-light text-primary fs-6" id="TANGGAL_OPERASI">
                                            <i class="fa fa-calendar mr-1"></i> -
                                        </span>
                                    </div>

                                    <div class="ribbon ribbon-success" id="success_op" style="display:none">
                                        <i class="fas fa-check-circle text-white"></i>
                                        Tindakan Operasi Sudah Integrasi
                                    </div>

                                    <div class="ribbon ribbon-danger" id="failed_op">
                                        <i class="fas fa-times-circle text-white"></i>
                                        Tindakan Operasi Belum Integrasi
                                    </div>

                                    <div class="ribbon-content card-body bg-light p-4">
                                        <!-- Detail Operasi -->
                                        <div class="mb-4">
                                            <!-- Laporan Operasi -->
                                            <div class="mb-4">
                                                <h6 class="text-uppercase text-secondary font-weight-bold mb-2">
                                                    <i class="fas fa-notes-medical mr-2 text-primary"></i>
                                                    Laporan Operasi
                                                </h6>
                                                <div class="p-3 bg-white rounded border">
                                                    <p class="mb-0 text-muted" id="laporan_operasi">
                                                        Tidak ada tindakan OP
                                                    </p>
                                                </div>
                                            </div>

                                            <h6 class="text-uppercase text-secondary font-weight-bold mb-3">
                                                <i class="fa fa-stethoscope mr-2 text-danger"></i> Detail Tindakan
                                                Operasi
                                            </h6>
                                            <div class="table-responsive">
                                                <table class="table table-hover table-bordered bg-white rounded">
                                                    <thead class="thead-light">
                                                        <tr class="text-center">
                                                            <th style="width: 5%;">No</th>
                                                            <th>Nama Tindakan Operasi</th>
                                                            <th>Diagnosa Pre Op</th>
                                                            <th>Diagnosa Post Op</th>
                                                            <th style="width: 20%;">Dokter Operator</th>
                                                            <th style="width: 20%;">Perawat</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tabel_tindakan_operasi">
                                                        <tr>
                                                            <td colspan="6" class="text-center text-muted">Data
                                                                tindakan operasi belum tersedia.</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Kode ICD 9 -->
                                        <div class="form-group" id="curr-icd9-operasi" style="display: none">
                                            <label>Kode ICD 9-CM saat ini</label>
                                            <input type="text" disabled class="form-control">
                                        </div>

                                        <div class="form-group mt-4" id="form-icd-operasi">
                                            <label for="icd9-operasi">Kode ICD 9-CM <small
                                                    class="text-danger">*</small></label>
                                            <select name="icd9-operasi" id="icd9-operasi" class="form-control"
                                                multiple style="width: 100%">
                                                <option value=""></option>
                                            </select>
                                        </div>

                                        <div class="form-group" id="btn-simpan-op">
                                            <button class="btn btn-info" onclick="saveICD('operasi')">Simpan Kode ICD 9</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <a href="javascript:void(0)" class="btn btn-primary" id="btn-send-satusehat"><i
                        class="fas fa-link mr-2"></i>Kirim Satu Sehat</a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
