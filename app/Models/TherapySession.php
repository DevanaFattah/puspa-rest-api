<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TherapySession extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'therapy_sessions';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = true;

    protected $fillable = [
        'schedule_id',
        'session_number',
        'session_date',
        'therapist_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'session_number' => 'integer',
        'session_date' => 'date',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id', 'id');
    }

    public function substituteTherapist(): BelongsTo
    {
        return $this->belongsTo(Therapist::class, 'therapist_id', 'id');
    }

    public function rescheduleOriginal(): HasOne
    {
        return $this->hasOne(Reschedule::class, 'original_session_id', 'id');
    }

    public function rescheduleReplacement(): HasOne
    {
        return $this->hasOne(Reschedule::class, 'new_session_id', 'id');
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
