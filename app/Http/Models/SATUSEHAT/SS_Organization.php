<?php
namespace App\Models\SATUSEHAT;

use Illuminate\Database\Eloquent\Model;

class SS_Organization extends Model{
    protected $connection = "dbsatusehat";
    protected $table = "RIRJ_SATUSEHAT_ORGANIZATION";

    // protected $fillable = [];
    public $timestamps = false;
}
