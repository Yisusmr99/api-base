<?php

namespace App\Models;

use App\Enums\Moneda;
use App\Enums\TipoCuenta;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['id_cliente', 'numero_cuenta', 'saldo', 'saldo_disponible', 'tipo_cuenta', 'fecha_apertura', 'fecha_cierre', 'moneda', 'estado'])]
class Cuentas extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cuentas';

    protected function casts(): array
    {
        return [
            'tipo_cuenta' => TipoCuenta::class,
            'moneda' => Moneda::class,
            'fecha_apertura' => 'datetime',
            'fecha_cierre' => 'datetime',
            'saldo' => 'decimal:2',
            'saldo_disponible' => 'decimal:2',
            'estado' => 'boolean',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }
}
