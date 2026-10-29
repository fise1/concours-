<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inscription extends Model
{
    use HasFactory;

    protected $table = 'inscriptions';

    protected $fillable = [
        'concours_id',
        'centre_id',
        'npi',
        'numero_table',
        'statut',
        'cree_le',
    ];

    public function centre()
    {
        return $this->belongsTo(Centre::class);
    }
}