@extends('layouts.app')

@push('before-style')
    <link rel="stylesheet" href="{{ asset('assets/css/icons/font-awesome/css/fontawesome-all.min.css') }}" />
    <style>
        .card-mapping { border-radius: 15px; cursor: pointer; }
        .card-mapping:hover { box-shadow: 0 14px 18px rgba(0,0,0,0.3); transform: translateY(-5px); }
        table.table th:first-child, table.table td:first-child { width: 50px !important; text-align: center; }
        input[type="checkbox"] { appearance: auto !important; visibility: visible !important; }
    </style>
@endpush

@section('content')
<div class="row page-titles">
    <div class="col-md-5 col-8 align-self-center">
        <h3 class="text-themecolor">Medication Statement</h3>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Medication Statement</li>
        </ol>
    </div>
    <div class="col-md-7 col-4 align-self-center text-right">
        <h6>Selamat Datang <b>{{ Session::get('user') }}</b></h6>
    </div>
</div>

<div class="card">
    <div class="card-body">

        <h4 class="card-title mb-3">Riwayat Obat Pasien (Medication Statement)</h4>

        {{-- Summary Cards --}}
        <div class="row mb-4">

            <div class="col-md-4">
                <div class="card card-inverse card-primary card-mapping" onclick="search('all')">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-users text-white" style="font-size: 40px"></i>
                        <div class="ml-3">
                            <span data-count="all" style="font-size: 26px" class="text-white">0</span>
                            <h6 class="text-white">Total Pasien</h6>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-inverse card-success card-mapping" onclick="search('integrated')">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-paper-plane text-white" style="font-size: 40px"></i>
                        <div class="ml-3">
                            <span data-count="sent" style="font-size: 26px" class="text-white">0</span>
                            <h6 class="text-white">Sudah Terkirim</h6>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-inverse card-danger card-mapping" onclick="search('not_integrated')">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-times-circle text-white" style="font-size: 40px"></i>
                        <div class="ml-3">
                            <span data-count="unsent" style="font-size: 26px" class="text-white">0</span>
                            <h6 class="text-white">Belum Terkirim</h6>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <button type="button" id="btnKirimDipilih" class="btn btn-success btn-sm mb-2">
            <i class="fas fa-paper-plane"></i> Kirim Dipilih
        </button>

        <button type="button" id="btnRefresh" class="btn btn-secondary btn-sm mb-2 ml-2">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>

        {{-- DataTable --}}
        <div class="table-responsive">
            <table id="medicationTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th><input type="checkbox" id="checkAll"></th>
                        <th>Pasien</th>
                        <th>Tgl Lahir</th>
                        <th>JK</th>
                        <th>Alamat</th>
                        <th>Status Integrasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>
</div>
@endsection


@push('after-script')
<script>
let table;

$(document).ready(function() {

    table = $('#medicationTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('satusehat.medstatement.datatabel') }}',
            type: 'POST',
            data: function(d) {
                    d._token = '{{ csrf_token() }}';
                }

        },
        columns: [
            { data: null, className:'text-center',
              render: (d,t,r,m)=> m.row + m.settings._iDisplayStart + 1 },

            { data: 'id', render: id => `<input type="checkbox" class="checkbox-item" value="${id}">` },

            { data: null, render: r => `<b>${r.nama ?? '-'}</b><br><small>NIK: ${r.nik ?? '-'}</small>` },

            { data:'tglLahir', render: v => v ? v.substring(0,10) : '-' },

            { data:'sex', render: v => (!v?'-': (v=='M'?'L':'P')) },

            { data:'alamat', render: v => (!v?'-': (v.length>35?v.substr(0,35)+'…':v)) },

            { data:null, render:r=>{
                if(r.tgl_mapping)
                    return `<span class="badge badge-success">Terkirim</span>`;
                return `<span class="badge badge-danger">Belum</span>`;
            }},

            { data:null, render:r=>`
                <button class="btn btn-sm btn-primary w-100 mb-1"
                        onclick="kirimSatu('${r.id}', this)">
                    <i class="fas fa-paper-plane"></i> Kirim
                </button>

                <button class="btn btn-sm btn-info w-100"
                        onclick='lihatDetail(${JSON.stringify(r).replace(/'/g,"\\'")})'>
                    <i class="fas fa-eye"></i> Detail
                </button>
            `}
        ]
    });

    table.on('xhr.dt', (e, s, json)=>{
        $('span[data-count="all"]').text(json.summary?.all ?? 0);
        $('span[data-count="sent"]').text(json.summary?.sent ?? 0);
        $('span[data-count="unsent"]').text(json.summary?.unsent ?? 0);
    });

    $("#btnRefresh").click(()=> table.ajax.reload());
    $("#checkAll").change(()=> $('.checkbox-item').prop('checked', $("#checkAll").is(':checked')));

});


// =============== DETAIL (Medication Statement) =======================
function lihatDetail(row) {

    const obatHistory = [
        { nama:"Amlodipine 10mg", frek:"1x sehari", mulai:"2021-01-14", status:"Sedang digunakan" },
        { nama:"Metformin 500mg", frek:"2x sehari", mulai:"2020-09-30", status:"Sudah berhenti" },
        { nama:"Atorvastatin 20mg", frek:"1x malam", mulai:"2022-05-11", status:"Sedang digunakan" }
    ];

    let riwayat = `
        <h4 class="mt-3 mb-2">Riwayat Obat</h4>
        <table class="table table-sm table-bordered">
            <thead><tr>
                <th>Nama Obat</th><th>Frekuensi</th><th>Mulai</th><th>Status</th>
            </tr></thead>
            <tbody>
    `;

    obatHistory.forEach(o=>{
        riwayat += `<tr>
            <td>${o.nama}</td>
            <td>${o.frek}</td>
            <td>${o.mulai}</td>
            <td>${o.status}</td>
        </tr>`;
    });

    riwayat += `</tbody></table>`;

    Swal.fire({
        title: "Detail Medication Statement",
        width: 650,
        html: `
            <table class="table table-sm table-bordered">
                <tr><th>Nama</th><td>${row.nama}</td></tr>
                <tr><th>NIK</th><td>${row.nik ?? '-'}</td></tr>
                <tr><th>Tgl Lahir</th><td>${row.tglLahir ?? '-'}</td></tr>
                <tr><th>JK</th><td>${row.sex}</td></tr>
                <tr><th>Alamat</th><td>${row.alamat ?? '-'}</td></tr>
                <tr><th>Status Integrasi</th><td>${row.tgl_mapping?'Terkirim':'Belum'}</td></tr>
            </table>
            <hr>
            ${riwayat}
        `,
        confirmButtonText: "Tutup"
    });
}


// ============ Kirim Medication Statement (Mock) =====================
function kirimSatu(id, btn=null){

    Swal.fire({
        icon:'info',
        title:'Mengirim...',
        text:'Mengirim MedicationStatement ke SATUSEHAT...',
        showConfirmButton:false,
        allowOutsideClick:false,
    });

    setTimeout(()=>{

        Swal.fire({
            icon:'success',
            title:'Berhasil',
            text:`MedicationStatement pasien ID ${id} berhasil dikirim!`
        });

        table.ajax.reload(null,false);

    }, 900);
}

function kirimSatusehat(id){ return kirimSatu(id); }

</script>
@endpush
