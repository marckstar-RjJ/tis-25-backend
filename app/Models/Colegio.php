<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Colegio extends Model
{
    protected $table = 'colegios';
    
    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'verification_code'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($colegio) {
            if (empty($colegio->verification_code)) {
                do {
                    $codigo = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                } while (static::where('verification_code', $codigo)->exists());
                
                $colegio->verification_code = $codigo;
            }
        });
    }

    /**
     * Obtener los estudiantes asociados a este colegio
     */
    public function estudiantes(): HasMany
    {
        return $this->hasMany(Estudiante::class, 'colegio_id');
    }
} 