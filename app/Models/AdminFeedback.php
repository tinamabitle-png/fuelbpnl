<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminFeedback extends Model
{
    use HasFactory;

    protected $table = 'admin_feedback';

    protected $fillable = [
        'user_id',
        'message',
        'sentiment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

