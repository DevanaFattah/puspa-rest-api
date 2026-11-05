<?php

namespace App\Policies;

use App\Models\Child;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ChildPolicy
{
    use HandlesAuthorization;

    public function manageChild(User $user, Child $child): bool
    {
        return $user->guardian && $user->guardian->family_id === $child->family_id;
    }

    public function viewAsSchool(User $user, Child $child): Response
    {
        if (!$user->hasRole('sekolah') || !$user->school) {
            return $this->deny('Profil sekolah tidak ditemukan.');
        }

        if ($child->school_id !== $user->school->id) {
            return $this->deny('Siswa ini bukan berasal dari sekolah Anda.');
        }

        return $this->allow();
    }
}
