<?php

namespace App\Models\SATUSEHAT;

use Illuminate\Database\Eloquent\Model;

class SATUSEHAT_PROCEDURE extends Model
{
    protected $connection = "dbsatusehat";
    protected $table = "RJ_SATUSEHAT_PROCEDURE";

    // protected $fillable = [];
    public $timestamps = false;
    public function encounter()
    {
        return $this->hasOne(SATUSEHAT_NOTA::class, 'id_satusehat_encounter', 'id_satusehat_encounter');
    }
}
