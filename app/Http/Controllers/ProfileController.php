<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Farm;
use App\Models\Harvest;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): RedirectResponse
    {
        return Redirect::route('dashboard', ['panel' => 'profile']);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $removeAvatar = (bool) ($validated['remove_avatar'] ?? false);

        if ($removeAvatar && $user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $validated['avatar_path'] = null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $validated['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        unset($validated['avatar'], $validated['remove_avatar']);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($user->isAdmin()) {
            return Redirect::route('dashboard', ['panel' => 'profile'])->with('success', 'تم تحديث معلومات الملف الشخصي.');
        }

        return Redirect::route('dashboard', ['panel' => 'profile'])->with('success', 'تم تحديث معلومات الملف الشخصي.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $userId = (int) $user->id;

        $farmImagePaths = Farm::where('user_id', $userId)->pluck('image_path')->unique()->values();
        $harvestImagePaths = Harvest::where('user_id', $userId)->pluck('image_path')->unique()->values();
        $equipmentImagePaths = Equipment::where('user_id', $userId)->pluck('image_path')->unique()->values();

        foreach ($farmImagePaths as $imagePath) {
            if (Farm::where('image_path', $imagePath)->where('user_id', '!=', $userId)->doesntExist()) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        foreach ($harvestImagePaths as $imagePath) {
            if (Harvest::where('image_path', $imagePath)->where('user_id', '!=', $userId)->doesntExist()) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        foreach ($equipmentImagePaths as $imagePath) {
            if (Equipment::where('image_path', $imagePath)->where('user_id', '!=', $userId)->doesntExist()) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
