<?php
namespace App\Models\SATUSEHAT;

use Illuminate\Database\Eloquent\Model;

class SS_ICD10 extends Model{
    protected $connection = "dbsatusehat";
    protected $table = "RIRJ_SATUSEHAT_ICD10";

    // protected $fillable = [];
    public $timestamps = false;
}
