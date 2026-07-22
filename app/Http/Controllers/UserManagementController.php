<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        return view('administration.users.index', [
            'users' => User::with(['roles', 'department'])
                ->when($request->filled('search'), fn ($query) => $query->where(fn ($search) => $search
                    ->where('name', 'like', '%'.$request->string('search').'%')
                    ->orWhere('staff_number', 'like', '%'.$request->string('search').'%')
                    ->orWhere('email', 'like', '%'.$request->string('search').'%')))
                ->when($request->filled('department'), fn ($query) => $query->where('department_id', $request->integer('department')))
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
                ->latest()->paginate(15)->withQueryString(),
            'departments' => DB::table('departments')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('administration.users.create', [
            'roles' => Role::orderBy('name')->get(),
            'categories' => AssetCategory::with('group')->where('is_active', true)->orderBy('name')->get(),
            'units' => DB::table('organizational_units')->where('is_active', true)->orderBy('name')->get(),
            'departments' => DB::table('departments')->where('is_active', true)->orderBy('name')->get(),
            'locations' => DB::table('locations')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(User $user): View
    {
        $user->load(['roles', 'maintainableCategories', 'reviewableMaintenanceCategories']);

        return view('administration.users.edit', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
            'categories' => AssetCategory::with('group')->where('is_active', true)->orderBy('name')->get(),
            'units' => DB::table('organizational_units')->where('is_active', true)->orderBy('name')->get(),
            'departments' => DB::table('departments')->where('is_active', true)->orderBy('name')->get(),
            'locations' => DB::table('locations')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'staff_number' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9 -]+$/', Rule::unique('users')->ignore($user)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role_id' => ['required', 'exists:roles,id'],
            'organizational_unit_id' => ['nullable', 'exists:organizational_units,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'primary_location_id' => ['nullable', 'exists:locations,id'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'maintenance_categories' => ['nullable', 'array'],
            'maintenance_categories.*' => ['integer', 'exists:asset_categories,id'],
            'review_categories' => ['nullable', 'array'],
            'review_categories.*' => ['integer', 'exists:asset_categories,id'],
        ]);
        if ($user->is($request->user()) && $validated['status'] !== 'active') {
            throw ValidationException::withMessages(['status' => 'You cannot deactivate or suspend your own administrator account.']);
        }
        $role = Role::findOrFail($validated['role_id']);
        $this->validateResponsibilities($role, $validated);
        DB::transaction(function () use ($validated, $role, $request, $user) {
            $user->update(collect($validated)->only(['name', 'staff_number', 'email', 'phone', 'organizational_unit_id', 'department_id', 'primary_location_id', 'status'])->all());
            $user->roles()->sync([$role->id => ['assigned_by' => $request->user()->id, 'assigned_at' => now()]]);
            DB::table('maintenance_category_responsibilities')->where('user_id', $user->id)->delete();
            $this->syncResponsibilities($user, $role, $validated, $request->user()->id);
        });

        return redirect()->route('administration.users.edit', $user)->with('success', 'Officer account and responsibilities updated.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate(['password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()]]);
        $user->forceFill(['password' => Hash::make($validated['password']), 'remember_token' => Str::random(60)])->save();

        return back()->with('success', 'Password reset successfully. Existing remembered sessions were invalidated.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'staff_number' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9 -]+$/', 'unique:users,staff_number'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role_id' => ['required', 'exists:roles,id'],
            'organizational_unit_id' => ['nullable', 'exists:organizational_units,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'primary_location_id' => ['nullable', 'exists:locations,id'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
            'maintenance_categories' => ['nullable', 'array'],
            'maintenance_categories.*' => ['integer', 'exists:asset_categories,id'],
            'review_categories' => ['nullable', 'array'],
            'review_categories.*' => ['integer', 'exists:asset_categories,id'],
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $this->validateResponsibilities($role, $validated);

        DB::transaction(function () use ($validated, $role, $request) {
            $user = User::create([
                ...collect($validated)->only(['name', 'staff_number', 'email', 'phone', 'organizational_unit_id', 'department_id', 'primary_location_id'])->all(),
                'public_id' => (string) Str::ulid(),
                'status' => 'active',
                'password' => Hash::make($validated['password']),
            ]);
            $user->roles()->attach($role->id, ['assigned_by' => $request->user()->id, 'assigned_at' => now()]);

            $this->syncResponsibilities($user, $role, $validated, $request->user()->id);
        });

        return redirect()->route('administration.users.index')->with('success', 'Officer account created with the selected responsibilities.');
    }

    private function validateResponsibilities(Role $role, array $validated): void
    {
        if ($role->slug === 'maintenance-officer' && empty($validated['maintenance_categories'])) {
            throw ValidationException::withMessages(['maintenance_categories' => 'Select at least one category this maintenance officer can maintain.']);
        }
        if ($role->slug === 'maintenance-review-officer' && empty($validated['review_categories'])) {
            throw ValidationException::withMessages(['review_categories' => 'Select at least one category this review officer can review.']);
        }
    }

    private function syncResponsibilities(User $user, Role $role, array $validated, int $actorId): void
    {
        $responsibility = match ($role->slug) {
            'maintenance-officer' => ['maintenance', $validated['maintenance_categories'] ?? []],
            'maintenance-review-officer' => ['review', $validated['review_categories'] ?? []],
            default => null,
        };
        if (! $responsibility) {
            return;
        }
        [$type, $categoryIds] = $responsibility;
        foreach (array_unique($categoryIds) as $categoryId) {
            DB::table('maintenance_category_responsibilities')->insert(['user_id' => $user->id, 'asset_category_id' => $categoryId, 'responsibility' => $type, 'assigned_by_user_id' => $actorId, 'is_active' => true, 'assigned_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
