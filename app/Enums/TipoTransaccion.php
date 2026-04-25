<?php

namespace App\Enums;

enum TipoTransaccion: string
{
    case Transferencia = 'transferencia';
    case Deposito = 'deposito';
    case Retiro = 'retiro';
}
