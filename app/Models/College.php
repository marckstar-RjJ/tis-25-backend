<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class College extends Model
{
    use HasFactory;

    protected $table = 'colegios';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
    ];

    public function students()
    {
        return $this->hasMany(Student::class, 'colegio_id');
    }
} 