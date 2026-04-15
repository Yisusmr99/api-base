<?php

namespace App\Enums;

enum TipoCuenta: string
{
    case Monetaria = 'monetaria';
    case Ahorro = 'ahorro';
    case Estudiantil = 'estudiantil';
}
