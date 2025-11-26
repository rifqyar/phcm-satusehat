<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasienInap extends Model
{
    protected $connection = 'dbsirs';
    protected $table = "RI_MASTERPX";

    protected $fillable = [];
    public $timestamps = false;
}
