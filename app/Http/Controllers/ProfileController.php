<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $r): View
    {
        $user = $r->user()->load(['roles', 'department']);

        return view('profile.edit', [
            'user' => $user,
            'locationName' => DB::table('locations')->where('id', $user->primary_location_id)->value('name'),
        ]);
    }

    public function update(Request $r): RedirectResponse
    {
        $v = $r->validate(['name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:30']]);
        $r->user()->update($v);

        return back()->with('success', 'Your profile details were updated.');
    }

    public function password(Request $r): RedirectResponse
    {
        $v = $r->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()]]);
        $r->user()->update(['password' => Hash::make($v['password'])]);

        return back()->with('success', 'Your password was changed successfully.');
    }
}
