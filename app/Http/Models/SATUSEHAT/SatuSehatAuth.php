<?php
namespace App\Models\SATUSEHAT;

use Illuminate\Database\Eloquent\Model;

class SatuSehatAuth extends Model{
    protected $connection = "dbsatusehat";
    protected $table = "RIRJ_SATUSEHAT_AUTH";
    // protected $fillable = [];

    public $timestamps = false;
}
