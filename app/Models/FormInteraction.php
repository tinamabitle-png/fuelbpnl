<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'form',
        'action',
        'outcome',
        'ip',
        'country_code',
        'submitted_city',
        'submitted_country',
        'path',
        'referer',
        'user_agent',
    ];
}

