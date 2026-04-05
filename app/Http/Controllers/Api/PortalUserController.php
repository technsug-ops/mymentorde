<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PortalUserController extends Controller
{
    public function index(Request $request)
    {
        $role = (string) $request->query('role', '');

        $query = User::query()
            ->whereIn('role', [User::ROLE_STUDENT, User::ROLE_DEALER])
            ->orderByDesc('updated_at');

        if ($role !== '') {
            $query->where('role', $role);
        }

        return $query->get([
            'id',
            'name',
            'email',
            'role',
            'student_id',
            'dealer_code',
            'is_active',
            'created_at',
            'updated_at',
        ]);
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'role' => ['required', Rule::in([User::ROLE_STUDENT, User::ROLE_DEALER])],
            'student_id' => ['nullable', 'string', 'max:64'],
            'dealer_code' => ['nullable', 'string', 'max:64'],
            'password' => ['nullable', 'string', 'min:8', 'max:190'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $role = (string) $payload['role'];
        $studentId = trim((string) ($payload['student_id'] ?? ''));
        $dealerCode = strtoupper(trim((string) ($payload['dealer_code'] ?? '')));

        if ($role === User::ROLE_STUDENT && $studentId === '') {
            return response()->json([
                'message' => 'Student role icin student_id zorunlu.',
                'error_code' => 'ERR_VALIDATION',
                'status' => 422,
            ], 422);
        }
        if ($role === User::ROLE_DEALER && $dealerCode === '') {
            return response()->json([
                'message' => 'Dealer role icin dealer_code zorunlu.',
                'error_code' => 'ERR_VALIDATION',
                'status' => 422,
            ], 422);
        }

        $generatedPassword = '';
        $password = (string) ($payload['password'] ?? '');
        if ($password === '') {
            $generatedPassword = Str::random(12);
            $password = $generatedPassword;
        }

        $user = User::query()->create([
            'name' => trim((string) $payload['name']),
            'email' => strtolower(trim((string) $payload['email'])),
            'role' => $role,
            'student_id' => $role === User::ROLE_STUDENT ? $studentId : null,
            'dealer_code' => $role === User::ROLE_DEALER ? $dealerCode : null,
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'password' => Hash::make($password),
        ]);

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'role', 'student_id', 'dealer_code', 'is_active']),
            'generated_password' => $generatedPassword,
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        abort_if(!in_array((string) $user->role, [User::ROLE_STUDENT, User::ROLE_DEALER], true), 404, 'Portal user bulunamadi.');

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in([User::ROLE_STUDENT, User::ROLE_DEALER])],
            'student_id' => ['nullable', 'string', 'max:64'],
            'dealer_code' => ['nullable', 'string', 'max:64'],
            'password' => ['nullable', 'string', 'min:8', 'max:190'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $role = (string) $payload['role'];
        $studentId = trim((string) ($payload['student_id'] ?? ''));
        $dealerCode = strtoupper(trim((string) ($payload['dealer_code'] ?? '')));

        if ($role === User::ROLE_STUDENT && $studentId === '') {
            return response()->json([
                'message' => 'Student role icin student_id zorunlu.',
                'error_code' => 'ERR_VALIDATION',
                'status' => 422,
            ], 422);
        }
        if ($role === User::ROLE_DEALER && $dealerCode === '') {
            return response()->json([
                'message' => 'Dealer role icin dealer_code zorunlu.',
                'error_code' => 'ERR_VALIDATION',
                'status' => 422,
            ], 422);
        }

        $update = [
            'name' => trim((string) $payload['name']),
            'email' => strtolower(trim((string) $payload['email'])),
            'role' => $role,
            'student_id' => $role === User::ROLE_STUDENT ? $studentId : null,
            'dealer_code' => $role === User::ROLE_DEALER ? $dealerCode : null,
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ];

        $password = (string) ($payload['password'] ?? '');
        if ($password !== '') {
            $update['password'] = Hash::make($password);
        }

        $user->update($update);

        return response()->json([
            'user' => $user->fresh()->only(['id', 'name', 'email', 'role', 'student_id', 'dealer_code', 'is_active']),
        ]);
    }

    public function resetPassword(User $user)
    {
        abort_if(!in_array((string) $user->role, [User::ROLE_STUDENT, User::ROLE_DEALER], true), 404, 'Portal user bulunamadi.');

        $generatedPassword = Str::random(12);
        $user->update([
            'password' => Hash::make($generatedPassword),
        ]);

        return response()->json([
            'user_id' => $user->id,
            'generated_password' => $generatedPassword,
        ]);
    }

    public function destroy(User $user)
    {
        abort_if(!in_array((string) $user->role, [User::ROLE_STUDENT, User::ROLE_DEALER], true), 404, 'Portal user bulunamadi.');

        $user->delete();

        return response()->json([
            'deleted' => true,
        ]);
    }
}
