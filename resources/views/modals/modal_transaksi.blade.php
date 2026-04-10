<!-- Modal Resume Medis Detail -->
<div class="modal fade" id="modal_transaksi">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Transaksi Satusehat</h5>
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
                                <h6 class="m-0 text-white"><i class="fas fa-hospital text-white"></i> Data Kunjungan
                                </h6>
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

                <div class="ribbon-wrapper card shadow-lg border-0 rounded-4 overflow-hidden mt-4">
                    <div class="ribbon ribbon-info">
                        <i class="fas fa-info-circle text-white"></i>
                        Kiriman Satusehat
                    </div>

                    <div class="card-body ribbon-content">
                        <div class="row justify-content-center align-items-center">
                            @foreach ($satuSehatMenu as $item)
                                <div class="col-md-4">
                                    <div class="card btn-kiriman-satusehat" id="{{$item['id']}}" style="border-radius: 60px !important;">
                                        <a class="btn btn-rounded shadow" href="{{ $item['url'] != '#' && Route::has($item['url']) ? route($item['url']) : '#' }}">
                                            <i class="fas text-white mr-3"></i>
                                            {{ $item['title'] }}
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="ribbon-wrapper card shadow-lg border-0 rounded-4 overflow-hidden mt-4" id="ribbon-log">
                    <div class="ribbon ribbon-info">
                        <i class="fas fa-info-circle text-white"></i>
                        Logging Kiriman Satusehat
                    </div>

                    <div class="card-body ribbon-content" id="card-log">
                        <div class="form-group">
                            <label for="service">Pilih Service Satusehat</label>
                            <select name="service" id="service" class="form-control" data-placeholder="Harap pilih Service">
                                <option></option>
                                @foreach ($listService as $service)
                                    <option value="{{$service->service_name}}">{{$service->service_name}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="log-container"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
