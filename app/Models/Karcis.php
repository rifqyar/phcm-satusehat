<?php
namespace App\Models;

use App\Models\Dokter;
use App\Models\Klinik;
use App\Models\Debitur;
use App\Models\KarcisBayar;
use Illuminate\Database\Eloquent\Model;

class Karcis extends Model{
    protected $connection = 'dbsirs';
    protected $table = "RJ_KARCIS";

    // protected $fillable = [];
    public $timestamps = false;
    public function ermkunjung()    {
        return $this->hasOne(ERM_Kunjungan::class,'KARCIS','KARCIS')->whereRaw('ISNULL(AKTIF,0) = 1');
    }

    public function inap(){
        return $this->hasOne(PasienInap::class,'noreg','NOREG');
    }
}
