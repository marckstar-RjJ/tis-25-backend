<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estudiante extends Model
{
    protected $table = 'estudiantes';
    
    protected $fillable = [
        'cuenta_id',
        'fecha_nacimiento',
        'curso',
        'colegio_id',
        'celular',
        'nombre_tutor',
        'apellido_tutor',
        'email_tutor',
        'celular_tutor'
    ];

    /**
     * Obtener la cuenta asociada a este estudiante
     */
    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cuenta_id');
    }

    /**
     * Obtener el colegio asociado a este estudiante
     */
    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'colegio_id');
    }

    /**
     * Obtener las áreas inscritas por el estudiante
     */
    public function areasInscritas(): HasMany
    {
        return $this->hasMany(AreasInscrita::class, 'estudiante_id');
    }
} 