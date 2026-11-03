<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCuenta extends Model
{
    protected $table = 'tipo_cuenta';

    protected $primaryKey = 'tipo_cuenta_id';

    public $timestamps = false;
}