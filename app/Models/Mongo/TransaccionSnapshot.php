<?php

namespace App\Models\Mongo;

use MongoDB\Laravel\Eloquent\Model as MongoModel;

/**
 * TransaccionSnapshot
 * -------------------
 * Snapshot inmutable y desnormalizado de una transacción.
 * Se crea desde TransaccionObserver cada vez que una Transaccion es persistida.
 *
 * En MySQL queda el dato "vivo" (relacional, normalizado). Aquí queda la foto
 * histórica con toda la información embebida para consultas y reportes
 * rápidos sin depender de joins.
 *
 * Campos esperados:
 *  - transaccion_id_sql      int       Id en MySQL
 *  - tipo_transaccion        string
 *  - estado                  string
 *  - moneda                  string
 *  - monto                   float
 *  - monto_convertido        ?float
 *  - es_externa              bool
 *  - banco_externo           ?string
 *  - referencia              ?string
 *  - cuenta_origen           ?array    { id, numero_cuenta, tipo, moneda, cliente: { id, nombre, dpi } }
 *  - cuenta_destino          ?array    idem cuenta_origen
 *  - registrado_por          ?array    { id, name, email, ip, user_agent }
 *  - fecha_transaccion       datetime
 *  - hora_transaccion        ?datetime
 *  - created_at              datetime
 */
class TransaccionSnapshot extends MongoModel
{
    protected $connection = 'mongodb';

    protected $collection = 'transaccion_snapshots';

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'transaccion_id_sql' => 'integer',
        'monto'              => 'float',
        'monto_convertido'   => 'float',
        'es_externa'         => 'boolean',
        'cuenta_origen'      => 'array',
        'cuenta_destino'     => 'array',
        'registrado_por'     => 'array',
        'fecha_transaccion'  => 'datetime',
        'hora_transaccion'   => 'datetime',
        'created_at'         => 'datetime',
    ];
}
