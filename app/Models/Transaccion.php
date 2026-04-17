<?php

namespace App\Models;

use App\Enums\EstadoTransaccion;
use App\Enums\Moneda;
use App\Enums\TipoTransaccion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'id_cuenta_origen',
    'id_cuenta_destino',
    'monto',
    'moneda',
    'monto_convertido',
    'referencia',
    'tipo_transaccion',
    'estado',
    'es_externa',
    'banco_externo',
    'fecha_transaccion',
    'hora_transaccion',
])]
class Transaccion extends Model
{
    use HasFactory;

    protected $table = 'transacciones';

    protected function casts(): array
    {
        return [
            'tipo_transaccion'  => TipoTransaccion::class,
            'estado'            => EstadoTransaccion::class,
            'moneda'            => Moneda::class,
            'monto'             => 'decimal:2',
            'monto_convertido'  => 'decimal:2',
            'es_externa'        => 'boolean',
            'fecha_transaccion' => 'datetime',
            'hora_transaccion'  => 'datetime',
        ];
    }

    public function cuentaOrigen()
    {
        return $this->belongsTo(Cuentas::class, 'id_cuenta_origen');
    }

    public function cuentaDestino()
    {
        return $this->belongsTo(Cuentas::class, 'id_cuenta_destino');
    }

    public function transferenciaExterna()
    {
        return $this->hasOne(TransferenciaExterna::class, 'id_transaccion');
    }
}
