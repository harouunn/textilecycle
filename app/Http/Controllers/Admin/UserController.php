<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Don;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $users = User::query()
            ->addSelect([
                'dons_count' => Don::selectRaw('count(*)')->whereColumn('user_id', 'users.id'),
                'dons_sum_poids_kg' => Don::selectRaw('sum(poids_kg)')->whereColumn('user_id', 'users.id'),
            ])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    public function create(): View
    {
        return view('admin.users.create', ['user' => new User]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        return redirect()->route('admin.users.show', $user)
            ->with('success', "Le compte de {$user->name} a été créé.");
    }

    public function show(User $user): View
    {
        $dons = Don::whereBelongsTo($user)->with('association')->latest()->paginate(10);
        $stats = Don::whereBelongsTo($user)
            ->selectRaw('count(*) as total, coalesce(sum(quantite), 0) as articles, coalesce(sum(poids_kg), 0) as poids')
            ->first();

        return view('admin.users.show', compact('user', 'dons', 'stats'));
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return redirect()->route('admin.users.show', $user)
            ->with('success', "Le compte de {$user->name} a été mis à jour.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte depuis cette page.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Le compte de {$user->name} et ses dons ont été supprimés.");
    }
}
