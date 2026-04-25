<?php

namespace App\Models;

use App\Enums\TipoTransferenciaExterna;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'id_transaccion',
    'banco_externo',
    'cuenta_externa',
    'codigo_confirmacion',
    'tipo',
    'estado',
    'fecha_envio',
    'fecha_confirmacion',
])]
class TransferenciaExterna extends Model
{
    use HasFactory;

    protected $table = 'transferencias_externas';

    protected function casts(): array
    {
        return [
            'tipo'               => TipoTransferenciaExterna::class,
            'fecha_envio'        => 'datetime',
            'fecha_confirmacion' => 'datetime',
        ];
    }

    public function transaccion()
    {
        return $this->belongsTo(Transaccion::class, 'id_transaccion');
    }
}
