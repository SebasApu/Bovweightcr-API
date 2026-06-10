<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ganado extends Model
{
    protected $fillable = [
        'finca_id', 'estado_salud_id', 'estado_comercial_id',
        'arete', 'nombre', 'sexo', 'raza', 'imagen'
    ];

    /**
     * Expone el último peso registrado como atributo 'peso_kg' en el JSON.
     */
    protected $appends = ['peso_kg'];

    public function finca()
    {
        return $this->belongsTo(Finca::class, 'finca_id');
    }

    /**
     * Último registro de peso del animal (el más reciente por fecha).
     */
    public function ultimoPeso()
    {
        return $this->hasOne(RegistroPeso::class, 'ganado_id')->latestOfMany('fecha');
    }

    /**
     * Peso actual del animal: el corregido si existe, si no el estimado.
     * Devuelve null si nunca se le ha registrado un peso.
     */
    public function getPesoKgAttribute(): ?float
    {
        $registro = $this->relationLoaded('ultimoPeso')
            ? $this->getRelation('ultimoPeso')
            : $this->ultimoPeso()->first();

        if (! $registro) {
            return null;
        }

        return (float) ($registro->peso_corregido ?? $registro->peso_estimado);
    }

    public function estadoSalud()
    {
        return $this->belongsTo(EstadoSaludGanado::class, 'estado_salud_id');
    }

    public function estadoComercial()
    {
        return $this->belongsTo(EstadoComercialGanado::class, 'estado_comercial_id');
    }

    public function registrosPeso()
    {
        return $this->hasMany(RegistroPeso::class, 'ganado_id');
    }
}