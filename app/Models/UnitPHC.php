<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitPHC extends Model{
    protected $connection = 'dbsirs';
    protected $table = "RIRJ_MKODE_UNIT";

    // protected $fillable = [];
    public $timestamps = false;
}
