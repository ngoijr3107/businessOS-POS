<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    private function authorizeAction(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, 'You do not have permission to manage user access.');
    }

    private function authorizeAny(Request $request, array $permissions): void
    {
        $user = $request->user();
        $allowed = $user && (
            $user->hasRole('Super Admin')
            || collect($permissions)->contains(fn (string $permission) => $user->can($permission))
        );

        abort_unless($allowed, 403, 'You do not have permission to view access profiles.');
    }

    private function companyUser(Request $request, User $user): User
    {
        abort_unless((int) $user->company_id === (int) $request->user()->company_id, 404);
        return $user;
    }

    private function normalizedRole(Request $request): ?string
    {
        $role = $request->input('role');
        if (is_array($role)) {
            $role = $role['name'] ?? null;
        }
        $role = $role ?: $request->input('role.name');
        return is_string($role) ? trim($role) : null;
    }

    private function validateRole(Request $request): string
    {
        $role = $this->normalizedRole($request);
        abort_unless($role, 422, 'A role is required. Select a role before saving the user.');

        $exists = Role::where('name', $role)->where('guard_name', 'web')->exists();
        abort_unless($exists, 422, "The selected role '{$role}' does not exist. Run php artisan db:seed to create the default roles.");

        return $role;
    }

    public function index(Request $request)
    {
        $this->authorizeAction($request, 'users.view');

        return User::where('company_id', $request->user()->company_id)
            ->with(['roles:id,name', 'branch:id,name'])
            ->latest()
            ->paginate(min($request->integer('per_page', 30), 200));
    }

    public function store(Request $request)
    {
        $this->authorizeAction($request, 'users.create');
        $companyId = $request->user()->company_id;
        $role = $this->validateRole($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('company_id', $companyId)],
        ]);

        $user = User::create([
            'company_id' => $companyId,
            'branch_id' => $data['branch_id'] ?? $request->user()->branch_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);

        $user->syncRoles([$role]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json($user->fresh()->load(['roles:id,name', 'branch:id,name']), 201);
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAction($request, 'users.update');
        $user = $this->companyUser($request, $user);
        $companyId = $request->user()->company_id;
        $role = $this->validateRole($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'is_active' => ['required', 'boolean'],
        ]);

        if ((int) $user->id === (int) $request->user()->id && $data['is_active'] === false) {
            return response()->json(['message' => 'You cannot deactivate your own account.'], 422);
        }

        $user->update([
            'name' => $data['name'],
            'branch_id' => $data['branch_id'] ?? null,
            'is_active' => $data['is_active'],
        ]);
        $user->syncRoles([$role]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh()->load(['roles:id,name', 'branch:id,name']);
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorizeAction($request, 'users.delete');
        $user = $this->companyUser($request, $user);

        if ((int) $user->id === (int) $request->user()->id) {
            return response()->json(['message' => 'You cannot deactivate your own account.'], 422);
        }

        $user->update(['is_active' => false]);
        return response()->noContent();
    }

    public function roles(Request $request)
    {
        $this->authorizeAny($request, ['users.view', 'users.create', 'users.update']);

        return Role::where('guard_name', 'web')
            ->withCount(['users', 'permissions'])
            ->orderByRaw("FIELD(name, 'Super Admin', 'Administrator', 'Manager', 'Accountant', 'Cashier', 'Salesperson')")
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
