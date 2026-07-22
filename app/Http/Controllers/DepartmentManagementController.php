<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentManagementController extends Controller
{
    public function index(Request $request): View
    {
        $departments = Department::query()
            ->withCount(['users', 'assets'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->input('status') === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('administration.departments.index', [
            'departments' => $departments,
        ]);
    }

    public function create(): View
    {
        return view('administration.departments.create');
    }

    public function edit(Department $department): View
    {
        $department->loadCount(['users', 'assets']);

        return view('administration.departments.edit', compact('department'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Department::create([
            ...$validated,
            'public_id' => (string) Str::ulid(),
            'code' => Str::upper($validated['code']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('administration.departments.index')->with('success', 'Department created successfully.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $this->validated($request, $department);

        $department->update([
            ...$validated,
            'code' => Str::upper($validated['code']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('administration.departments.edit', $department)->with('success', 'Department updated successfully.');
    }

    private function validated(Request $request, ?Department $department = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('departments')->ignore($department)],
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9-]+$/', Rule::unique('departments')->ignore($department)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
