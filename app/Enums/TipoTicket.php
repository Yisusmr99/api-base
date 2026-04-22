<?php

namespace App\Enums;

enum TipoTicket: string
{
    case Consulta = 'consulta';
    case Reclamo = 'reclamo';
    case Solicitud = 'solicitud';
    case Soporte = 'soporte';
}
