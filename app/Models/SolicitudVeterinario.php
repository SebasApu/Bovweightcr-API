<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudVeterinario extends Model
{
    protected $table = 'solicitud_veterinarios';

    protected $fillable = [
        'finca_id',
        'ganadero_id',
        'veterinario_id',
        'estado',
        'aprobado_en',
        'aprobado_por',
    ];

    protected function casts(): array
    {
        return [
            'aprobado_en' => 'datetime',
        ];
    }

    public function finca()
    {
        return $this->belongsTo(Finca::class, 'finca_id');
    }

    public function ganadero()
    {
        return $this->belongsTo(User::class, 'ganadero_id');
    }

    public function veterinario()
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }

    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }
}
