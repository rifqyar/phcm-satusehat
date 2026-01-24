<?php

namespace App\Models\SATUSEHAT;

use Illuminate\Database\Eloquent\Model;

class SATUSEHAT_CARE_PLAN extends Model
{
    protected $connection = "dbsatusehat";
    protected $table = "RIRJ_SATUSEHAT_CAREPLAN";

    protected $guarded = [];
    public $timestamps = false;
}
