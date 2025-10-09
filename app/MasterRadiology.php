<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MasterRadiology extends Model
{
    protected $table = 'SATUSEHAT_M_SERVICEREQUEST_CODE';
    protected $connection = 'sqlsrv';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'code', 'codesystem', 'display', 'NM_TIND', 'CATEGORY'
    ];
}
