<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController
{
    public function index(Request $request)
    {
        $query = User::query()
            ->withCount('children')
            ->orderBy('created_at', 'desc');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        return UserResource::collection($query->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'role' => ['required', Rule::in(['admin', 'parent'])],
            'password' => ['required', 'string', 'min:6'],
            'is_active' => ['boolean'],
        ]);

        $user = User::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Пользователь создан',
            'data' => new UserResource($user),
        ], 201);
    }

    public function show(User $user)
    {
        $user->load('children')->loadCount('children');

        return new UserResource($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'role' => ['sometimes', 'required', Rule::in(['admin', 'parent'])],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['boolean'],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Пользователь обновлен',
            'data' => new UserResource($user),
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалить свой аккаунт',
            ], 422);
        }

        if ($user->children()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалить пользователя, у которого есть ученики',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Пользователь удален',
        ]);
    }
}
