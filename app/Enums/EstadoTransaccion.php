<?php

namespace App\Enums;

enum EstadoTransaccion: string
{
    case Pendiente = 'pendiente';
    case Completada = 'completada';
    case Fallida = 'fallida';
}
