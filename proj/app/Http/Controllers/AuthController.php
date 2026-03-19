<?php

namespace App\Http\Controllers;

use App\Domain\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function create()
    {
        return view('auth.login', [
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function store(HttpRequest $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = User::query()->findOrFail($data['user_id']);

        Auth::login($user);

        return $user->role === UserRole::Master->value
            ? redirect()->route('master.dashboard')
            : redirect()->route('dispatcher.dashboard');
    }

    public function destroy()
    {
        Auth::logout();

        return redirect()->route('login');
    }
}
