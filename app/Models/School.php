<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory, HasUlids;

    protected $guard_name = 'api';

    protected $table = 'schools';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'school_name',
        'npsn',
        'jenjang',
        'school_phone',
        'school_address',
        'pic_name',
        'verification_status',
        'verified_at',
    ];

    protected $casts = [
        'school_phone' => 'encrypted',
        'verified_at' => 'datetime',
    ];

    protected $touches = ['user'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Child::class, 'school_id', 'id');
    }

    public function therapists(): HasMany
    {
        return $this->hasMany(Therapist::class, 'school_id', 'id');
    }
}

