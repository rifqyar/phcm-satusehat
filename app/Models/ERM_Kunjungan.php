<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ERM_Kunjungan extends Model{
    protected $connection = 'ermklinik';
    protected $table = "ERM_NOMOR_KUNJUNG";

    // protected $fillable = [];

    // public $timestamps = false;
}
