<h6 class="mb-2">Data Pasien & Transaksi</h6>
<table class="table table-sm table-bordered">
<tr><th>Nama</th><td>{{ $header['nama'] }}</td></tr>
<tr><th>NIK</th><td>{{ $header['nik'] }}</td></tr>
<tr><th>JK</th><td>{{ $header['sex'] }}</td></tr>
<tr><th>Alamat</th><td>{{ $header['alamat'] }}</td></tr>
<tr><th>ID Trans</th><td>{{ $header['id_trans'] }}</td></tr>
<tr><th>Karcis</th><td>{{ $header['karcis'] }}</td></tr>
<tr><th>Tanggal</th><td>{{ $header['tgl'] }}</td></tr>
</table>

<h6 class="mt-3">Daftar Obat</h6>
<table class="table table-sm table-striped table-bordered">
<thead>
<tr>
    <th>Obat</th>
    <th>KFA</th>
    <th>Aturan Pakai</th>
    <th>Status Integrasi Statement</th>
    <th>Waktu Kirim</th>
</tr>
</thead>
<tbody>
@foreach($items as $i)
<tr>
    <td>{{ $i->NAMABRG }}</td>
    <td>{{ $i->KD_BRG_KFA }}</td>
    <td>{{ $i->ATURAN_PAKAI }}</td>
    <td>
        @if(!empty($i->IDENTIFIER_VALUE))
            <span class="badge badge-success">Integrasi</span>
        @else
            <span class="badge badge-danger">Belum Integrasi</span>
        @endif
    </td>
    <td>{{ $i->WK_KIRIM_SATUSEHAT ?? '-' }}</td>
</tr>
@endforeach
</tbody>
</table>
