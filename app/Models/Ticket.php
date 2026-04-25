<?php

namespace App\Models;

use App\Enums\EstadoTicket;
use App\Enums\TipoTicket;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'codigo_ticket',
    'id_cliente',
    'id_tipo_ticket',
    'id_estado_ticket',
    'id_prioridad',
    'asunto',
    'descripcion',
    'fecha_creacion',
    'fecha_cierre',
    'canal_origen',
    'creado_por',
    'observaciones_generales',
])]

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'Tickets';

    protected $primaryKey = 'id_ticket';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'id_tipo_ticket' => TipoTicket::class,
            'id_estado_ticket' => EstadoTicket::class,
            'fecha_creacion' => 'datetime',
            'fecha_cierre' => 'datetime',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionTicket::class, 'id_ticket');
    }

    public function historiales()
    {
        return $this->hasMany(HistorialTicket::class, 'id_ticket');
    }
}
