<!-- Modal Mapping Obat -->
<div class="modal fade" id="modalMapping" tabindex="-1" aria-labelledby="modalMappingLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-info text-white">
                <h5 class="modal-title text-white" id="modalMappingLabel">Mapping Data Tindakan Radiology</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>

            </div>

            <div class="modal-body">
                <form id="formMappingRadiology" action="{{ route('master_radiology.save_loinc') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id_tindakan" id="id_tindakan">

                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="form-label">ID</label>
                            <input type="text" class="form-control" id="no" readonly>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Nama Group Tindakan</label>
                            <input type="text" class="form-control" id="nama_grup" readonly>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Nama Tindakan</label>
                            <input type="text" class="form-control" id="nama_tindakan" name="nama_tindakan" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kode LOINC</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="satusehat_code" name="satusehat_code" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama LOINC</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="satusehat_display" name="satusehat_display" readonly>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <form id="formSearchLoinc">
                            <div class="row align-items-end">
                                <div class="col-md-10">
                                    <input type="text" class="form-control" id="search_loinc" name="keyword" 
                                        placeholder="Search LOINC..." required>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-info w-100" id="btnCariLoincCode">
                                        <i class="fa fa-search"></i> Cari
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <hr>

                <!-- Hasil LOINC -->
                <div class="table-responsive" id="tableLoincWrapper" style="display:none;">
                    <table class="table table-bordered table-striped">
                        <thead class="table-info">
                            <tr>
                                <th>Kode LOINC</th>
                                <th>Nama LOINC</th>
                                <th>Class</th>
                                <th>Tags</th>
                                <th>Detail</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyLoinc"></tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="submit" id="btnSaveMapping" class="btn btn-success">Simpan Mapping</button>
            </div>

        </div>
    </div>
</div>
<script>
    const formSearchLoinc = document.getElementById('formSearchLoinc');

    function showLoading() {
        document.getElementById('tableLoincWrapper').style.display = 'block';
        document.getElementById('tbodyLoinc').innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4">
                    <div class="spinner-border text-info" role="status" style="width: 2rem; height: 2rem;">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <div class="mt-2 text-secondary">Memuat data LOINC...</div>
                </td>
            </tr>
        `;
    }

    function getDataLoinc(query) {
        fetch(`/master-radiology/loinc-search?query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            const items = data?.Results || [];
            const tbody = document.getElementById('tbodyLoinc');
            tbody.innerHTML = '';

            if (items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center">Tidak ada data LOINC ditemukan</td></tr>`;
                document.getElementById('tableLoincWrapper').style.display = 'block';
                return;
            }
            
            items.forEach(item => {
                const row = `
                    <tr>
                        <td>${item.LOINC_NUM || '-'}</td>
                        <td>${item.LONG_COMMON_NAME || '-'}</td>
                        <td>${item.CLASS || '-'}</td>
                        <td>${(item.Tags || []).join(', ') || '-'}</td>
                        <td><a href="${item.Link || '#'}" target="_blank">Link</a></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-success btnPilihLoinc" 
                                data-code="${item.LOINC_NUM}" 
                                data-display="${item.LONG_COMMON_NAME}">
                                Pilih
                            </button>
                        </td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('beforeend', row);
            });
            document.getElementById('tableLoincWrapper').style.display = 'block';
        })
    }

    formSearchLoinc.addEventListener('submit', function (e) {
        e.preventDefault();

        const query = document.getElementById('search_loinc').value.trim();
        if (!query) {
            alert('Masukkan code atau nama LOINC untuk mencari data LOINC.');
            return;
        }

        showLoading();

        try {
            getDataLoinc(query);
        } catch (error) {
            console.error(error);
            alert('Terjadi error saat memuat data LOINC.');
            return;
        }
        
    });

    // document.getElementById('btnCariLoincDisplay').addEventListener('click', async function () {
    //     const query = document.getElementById('satusehat_display').value.trim();
    //     if (!query) {
    //         alert('Masukkan keyword LOINC untuk mencari data LOINC.');
    //         return;
    //     }

    //     try {
    //         await getDataLoinc(query);
    //     } catch (error) {
    //         console.error(error);
    //         alert('Terjadi error saat memuat data LOINC.');
    //         return;
    //     }
        
    // });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('btnPilihLoinc')) {
            const kode = e.target.dataset.code;
            const nama = e.target.dataset.display;
            document.getElementById('satusehat_code').value = kode;
            document.getElementById('satusehat_display').value = nama;
            document.getElementById('tableLoincWrapper').style.display = 'none';
            document.getElementById('search_loinc').value = '';
        }
    });

</script>