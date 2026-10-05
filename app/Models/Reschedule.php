<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reschedule extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'reschedules';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = true;

    protected $fillable = [
        'original_session_id',
        'new_session_id',
        'requested_by',
    ];

    public function originalSession(): BelongsTo
    {
        return $this->belongsTo(TherapySession::class, 'original_session_id', 'id');
    }

    public function newSession(): BelongsTo
    {
        return $this->belongsTo(TherapySession::class, 'new_session_id', 'id');
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
