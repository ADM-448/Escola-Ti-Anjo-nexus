<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Edital extends Model
{
    protected $table = 'editais';
    protected $fillable = [
        'external_id', 'titulo', 'link', 'objetivo', 
        'fonte', 'condicao_financiamento', 'operacao', 'publico'
    ];
    /**
     * Valores padrão para atributos.
     */
    protected $attributes = [
        'fonte' => 'FINEP',
    ];
    protected $casts = [
        'data_publicacao' => 'date',
    ];
}
