<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvestorDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'investor_id',
        'document_type', // registration_certificate, tax_certificate, id_card, proof_of_address
        'document_path',
        'document_name',
        'verified',
        'verified_by',
        'verified_at',
        'expiry_date',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'verified_at' => 'datetime',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Business logic methods
    public function verify($userId)
    {
        $this->update([
            'verified' => true,
            'verified_by' => $userId,
            'verified_at' => now(),
        ]);

        return $this;
    }

    public function isExpired()
    {
        return $this->expiry_date && $this->expiry_date < now();
    }
}
