<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TherapySoap extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'therapy_soaps';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = true;

    protected $fillable = [
        'therapy_session_id',
        'performed_at',
        'therapy_diagnosis',
        'interventions',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'implementation',
        'advanced_assessment',
        'advanced_plan',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    public function therapySession(): BelongsTo
    {
        return $this->belongsTo(TherapySession::class, 'therapy_session_id', 'id');
    }
}
