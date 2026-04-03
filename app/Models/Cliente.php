<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['nombres', 'apellidos', 'dpi', 'direccion', 'telefono', 'correo_electronico', 'estado'])]
class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }
}
