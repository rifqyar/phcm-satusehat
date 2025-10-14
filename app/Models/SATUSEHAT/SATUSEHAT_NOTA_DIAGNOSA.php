<?php
namespace App\Models\SATUSEHAT;

use Illuminate\Database\Eloquent\Model;
use App\Models\SATUSEHAT\SATUSEHAT_NOTA;

class SATUSEHAT_NOTA_DIAGNOSA extends Model{
    protected $connection = "dbsatusehat";
    protected $table = "RJ_SATUSEHAT_DIAGNOSA";

    // protected $fillable = [];
    public $timestamps = false;
    public function satusehatNota() {
        return $this->belongsTo(SATUSEHAT_NOTA::class,'nota','nota');
    }
}
