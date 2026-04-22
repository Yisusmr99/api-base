<?php

namespace App\Models;

use App\Enums\EstadoTicket;
use App\Enums\TipoMovimientoTicket;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'id_ticket',
    'id_usuario',
    'fecha_movimiento',
    'tipo_movimiento',
    'descripcion',
    'estado_anterior',
    'estado_nuevo',
])]
class HistorialTicket extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'historial_ticket';

    protected $primaryKey = 'id_historial';

    const CREATED_AT = 'fecha_movimiento';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'fecha_movimiento' => 'datetime',
            'tipo_movimiento' => TipoMovimientoTicket::class,
            'estado_anterior' => EstadoTicket::class,
            'estado_nuevo' => EstadoTicket::class,
        ];
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'id_ticket');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
