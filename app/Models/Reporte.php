<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reporte extends Model
{
    protected $table = 'reportes';
    
    protected $fillable = [
        'fecha_registro',
        'fecha_inscripcion',
        'fecha_pago',
        'estudiante_id',
        'area_id',
        'estado_pago',
        'monto_pagado',
        'observaciones'
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
        'fecha_inscripcion' => 'datetime',
        'fecha_pago' => 'datetime',
        'monto_pagado' => 'decimal:2'
    ];

    /**
     * Obtener el estudiante asociado a este reporte
     */
    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    /**
     * Obtener el área asociada a este reporte
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
} 