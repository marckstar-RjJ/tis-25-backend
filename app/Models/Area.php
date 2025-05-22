<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;
    
    protected $table = 'areas';
    
    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
    ];
    
    protected $casts = [
        'estado' => 'boolean',
    ];
    
    /**
     * Relación con las convocatorias a las que pertenece esta área
     */
    public function convocatorias()
    {
        return $this->belongsToMany(Convocatoria::class, 'convocatoria_areas', 'area_id', 'convocatoria_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'estudiante_area', 'area_id', 'estudiante_id')
            ->withPivot('estado')
            ->withTimestamps();
    }
}
