<?php
namespace App\Models\SATUSEHAT;

use Illuminate\Database\Eloquent\Model;
use App\Models\SATUSEHAT\SATUSEHAT_NOTA_DIAGNOSA;

class SATUSEHAT_NOTA extends Model{
    protected $connection = "dbsatusehat";
    protected $table = "RJ_SATUSEHAT_NOTA";

    // protected $fillable = [];
    public $timestamps = false;
    public function diagnosaNota()  {
        return $this->hasMany(SATUSEHAT_NOTA_DIAGNOSA::class,'nota','nota');
    }
}
