<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseFormatter;
use App\Http\Requests\Admin\StoreSchoolRequest;
use App\Http\Requests\Admin\UpdateSchoolRequest;
use App\Http\Resources\SchoolResource;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SchoolController extends Controller
{
    use ResponseFormatter;

    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');

        $schools = School::query()
            ->with('user:id,username,email')
            ->withCount('children')
            ->when($search, function ($q, $search) {
                $q->where('school_name', 'like', "%{$search}%")
                  ->orWhere('npsn', 'like', "%{$search}%");
            })
            ->latest('created_at')
            ->get();

        return $this->successResponse(
            SchoolResource::collection($schools),
            'Daftar sekolah berhasil diambil',
            200
        );
    }

    public function show(School $school): JsonResponse
    {
        $school->load('user:id,username,email')->loadCount('children');

        return $this->successResponse(
            new SchoolResource($school),
            'Detail sekolah berhasil diambil',
            200
        );
    }

    public function store(StoreSchoolRequest $request): JsonResponse
    {
        $data = $request->validated();

        $school = DB::transaction(function () use ($data) {
            $user = User::create([
                'username'          => $data['username'],
                'email'             => $data['email'],
                'password'          => Hash::make($data['password']),
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);

            $user->assignRole('sekolah');

            return School::create([
                'user_id'             => $user->id,
                'school_name'         => $data['school_name'],
                'npsn'                => $data['npsn'] ?? null,
                'jenjang'             => $data['jenjang'] ?? null,
                'school_phone'        => $data['school_phone'] ?? null,
                'school_address'      => $data['school_address'] ?? null,
                'pic_name'            => $data['pic_name'] ?? null,
                'verification_status' => 'verified',
                'verified_at'         => now(),
            ]);
        });

        $school->load('user:id,username,email');

        return $this->successResponse(
            new SchoolResource($school),
            'Akun sekolah berhasil dibuat',
            201
        );
    }

    public function update(UpdateSchoolRequest $request, School $school): JsonResponse
    {
        $school->update($request->validated());

        $school->load('user:id,username,email')->loadCount('children');

        return $this->successResponse(
            new SchoolResource($school),
            'Data sekolah berhasil diperbarui',
            200
        );
    }

    public function destroy(School $school): JsonResponse
    {
        DB::transaction(function () use ($school) {
            $user = $school->user;
            $school->delete();
            $user?->delete();
        });

        return $this->successResponse([], 'Sekolah berhasil dihapus', 200);
    }
}

