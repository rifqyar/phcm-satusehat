<?php

namespace App\Models\SATUSEHAT;

use Illuminate\Database\Eloquent\Model;

class SATUSEHAT_CLINICALIMPRESSION extends Model
{
    protected $connection = "dbsatusehat";
    protected $table = "RIRJ_SATUSEHAT_CLINICALIMPRESSION";

    protected $guarded = [];
    public $timestamps = false;
}
