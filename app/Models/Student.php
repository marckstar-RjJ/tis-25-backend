<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

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

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'cuenta_id');
    }

    public function college()
    {
        return $this->belongsTo(College::class, 'colegio_id');
    }

    public function tutor()
    {
        // La relación se establece a través del colegio
        return $this->belongsTo(Tutor::class, 'colegio_id', 'colegio_id');
    }

    public function areas()
    {
        return $this->belongsToMany(Area::class, 'estudiante_area', 'estudiante_id', 'area_id')
            ->withPivot('estado')
            ->withTimestamps();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'estudiante_id');
    }
} 