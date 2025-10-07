<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MasterObat extends Model
{
    protected $table = 'M_TRANS_KFA';
    protected $connection = 'sqlsrv';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'KDBRG_CENTRA', 'NAMABRG', 'KD_BRG_KFA', 'NAMABRG_KFA', 'DESCRIPTION'
    ];
}
