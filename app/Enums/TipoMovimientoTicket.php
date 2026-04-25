<?php

namespace App\Enums;

enum TipoMovimientoTicket: string
{
    case Creacion = 'creacion';
    case CambioEstado = 'cambio_estado';
    case Reasignacion = 'reasignacion';
    case Comentario = 'comentario';
    case Cierre = 'cierre';
}
