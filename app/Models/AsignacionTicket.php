<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'id_ticket',
    'id_usuario_asignado',
    'id_usuario_asigna',
    'fecha_asignacion',
    'motivo_asignacion',
    'estado_asignacion',
])]
class AsignacionTicket extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'asignaciones_ticket';

    protected $primaryKey = 'id_asignacion';

    const CREATED_AT = 'fecha_asignacion';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'fecha_asignacion' => 'datetime',
        ];
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'id_ticket');
    }

    public function usuarioAsignado()
    {
        return $this->belongsTo(User::class, 'id_usuario_asignado');
    }

    public function usuarioAsigna()
    {
        return $this->belongsTo(User::class, 'id_usuario_asigna');
    }
}
