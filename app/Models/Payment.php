<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'amount',
        'transaction_id',
        'upi_transaction_id',
        'payment_method',
        'status',
        'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the user that owns the payment
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plan associated with the payment
     */
    public function plan()
    {
        return $this->belongsTo(PricingPlan::class, 'plan_id');
    }
}
