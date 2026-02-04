<?php

namespace App\Models\SATUSEHAT;

use Illuminate\Database\Eloquent\Model;

class SATUSEHAT_EPISODEOFCARE extends Model
{
    protected $connection = "dbsatusehat";
    protected $table = "RIRJ_SATUSEHAT_EPISODEOFCARE";

    protected $guarded = [];
    public $timestamps = false;
}
