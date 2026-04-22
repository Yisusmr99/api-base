<?php

namespace App\Enums;

enum EstadoTicket: string
{
    case Abierto = 'abierto';
    case Asignado = 'asignado';
    case EnProceso = 'en proceso';
    case Pendiente = 'pendiente';
    case Resuelto = 'resuelto';
    case Cerrado = 'cerrado';
}
