<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->only(['name', 'email']));

        // Photo de profil : nouvelle image (l'ancienne est supprimée) ou retrait demandé.
        if ($request->hasFile('photo_profil') || $request->boolean('supprimer_photo')) {
            if ($user->photo_profil) {
                Storage::disk('public')->delete($user->photo_profil);
            }
            $user->photo_profil = $request->hasFile('photo_profil')
                ? $request->file('photo_profil')->store('profils', 'public')
                : null;
        }

        $emailModifie = $request->user()->isDirty('email');
        if ($emailModifie) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        // Nouvelle adresse : elle doit être confirmée avec un nouveau code.
        if ($emailModifie) {
            $request->user()->sendEmailVerificationNotification();
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        if ($user->photo_profil) {
            Storage::disk('public')->delete($user->photo_profil);
        }
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
