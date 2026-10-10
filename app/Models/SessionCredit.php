<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionCredit extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'session_credits';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'child_id',
        'guardian_id',
        'source_invoice_id',
        'unused_session_count',
        'credit_amount',
        'is_used',
        'used_in_invoice_id',
    ];

    protected $casts = [
        'unused_session_count' => 'integer',
        'credit_amount' => 'decimal:2',
        'is_used' => 'boolean',
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class, 'child_id', 'id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class, 'guardian_id', 'id');
    }

    public function sourceInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'source_invoice_id', 'id');
    }

    public function usedInInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'used_in_invoice_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) \Symfony\Component\Uid\Ulid::generate();
            }
        });
    }
}
