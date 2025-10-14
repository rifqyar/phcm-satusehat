<?php
namespace App\Models\SATUSEHAT;

use Illuminate\Database\Eloquent\Model;

class SS_Pasien extends Model{
    protected $connection = "dbsatusehat";
    protected $table = "RIRJ_SATUSEHAT_PASIEN";

    // protected $fillable = [];

    public $timestamps = false;
    public function mapping_pasien()    {
        return $this->hasMany(SS_Pasien_Mapping::class,'nik','nik');
    }
}
