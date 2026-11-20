<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\AccessManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $r)
    {
        $data = $r->validate([
            'company_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $company = Company::create([
            'name' => $data['company_name'],
            'slug' => Str::slug($data['company_name']) . '-' . Str::lower(Str::random(5)),
        ]);

        app(AccessManager::class)->ensureDefaults();

        $user = User::create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => true,
        ]);

        $user->assignRole('Administrator');

        return response()->json([
            'user' => $user->fresh()->load('company', 'branch', 'roles'),
            'token' => $user->createToken('web')->plainTextToken,
        ], 201);
    }

    public function login(Request $r)
    {
        $data = $r->validate(['email' => 'required|email', 'password' => 'required']);
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Invalid credentials.']);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['email' => 'Account is disabled.']);
        }

        return [
            'user' => $user->load('company', 'branch', 'roles', 'permissions'),
            'token' => $user->createToken('web')->plainTextToken,
        ];
    }

    public function me(Request $r)
    {
        return $r->user()->load('company', 'branch', 'roles', 'permissions');
    }

    public function logout(Request $r)
    {
        $r->user()->currentAccessToken()?->delete();
        return response()->noContent();
    }
}
