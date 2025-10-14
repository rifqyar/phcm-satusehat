<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogTransaksi extends Model{
    protected $connection = 'dbsirs';
    protected $table = "SATUSEHAT_LOG_TRANSACTION";
}
