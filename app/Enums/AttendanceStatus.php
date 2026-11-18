<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT          = 'present';
    case SICK             = 'sick';
    case EXCUSED          = 'excused';
    case THERAPIST_EXCUSED = 'therapist_excused';
    case THERAPIST_ABSENT  = 'therapist_absent';
    case ABSENT           = 'absent';
    case CANCELLED        = 'cancelled';
    case RESCHEDULED      = 'rescheduled';
    case PENDING          = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::PRESENT                                                   => 'Hadir',
            self::SICK                                                      => 'Sakit',
            self::EXCUSED, self::THERAPIST_EXCUSED, self::THERAPIST_ABSENT   => 'Izin',
            self::ABSENT, self::CANCELLED                                   => 'Alpa',
            self::RESCHEDULED                                               => 'Reschedule',
            self::PENDING                                                   => 'Pending',
        };
    }

    public function slug(): string
    {
        return match ($this) {
            self::PRESENT                                                   => 'hadir',
            self::SICK                                                      => 'sakit',
            self::EXCUSED, self::THERAPIST_EXCUSED, self::THERAPIST_ABSENT   => 'izin',
            self::ABSENT, self::CANCELLED                                   => 'alpa',
            self::RESCHEDULED                                               => 'reschedule',
            self::PENDING                                                   => 'pending',
        };
    }

    public static function fromStatus(?string $status): ?self
    {
        if (!$status) {
            return null;
        }

        return self::tryFrom(strtolower($status));
    }
}
