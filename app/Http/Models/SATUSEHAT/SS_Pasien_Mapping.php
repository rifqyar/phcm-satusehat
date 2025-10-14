<?php
namespace App\Models\SATUSEHAT;

use App\Models\PASIEN;
use Illuminate\Database\Eloquent\Model;

class SS_Pasien_Mapping extends Model{
    protected $connection = "dbsatusehat";
    protected $table = "RIRJ_SATUSEHAT_PASIEN_MAPPING";

    // protected $fillable = [];
    public $timestamps = false;
    public function masterpasien()  {
        return $this->hasOne(PASIEN::class,'KBUKU','kbuku');
    }
}
