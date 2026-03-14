<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UserModerationController extends Controller
{
    public function destroy(User $user): RedirectResponse
    {
        $authUser = request()->user();

        abort_unless($authUser->isAdmin(), 403);
        abort_if($authUser->id === $user->id, 422, 'لا يمكن حذف حسابك الإداري الحالي.');

        $user->delete();

        return back()->with('success', 'تم حذف المستخدم وجميع منشوراته.');
    }
}
