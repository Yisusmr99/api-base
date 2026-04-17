<?php

namespace App\Enums;

enum TipoTransferenciaExterna: string
{
    case Entrante = 'entrante';
    case Saliente = 'saliente';
}
