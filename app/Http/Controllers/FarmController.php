<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFarmRequest;
use App\Http\Requests\UpdateFarmRequest;
use App\Models\Farm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class FarmController extends Controller
{
    public function store(StoreFarmRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['image_path'] = $request->file('image')->store('farms', 'public');
        $validated['area'] = (float) $validated['length'] * (float) $validated['width'];

        $request->user()->farms()->create($validated);

        return back()->with('success', 'تم إضافة المزرعة بنجاح.');
    }

    public function update(UpdateFarmRequest $request, Farm $farm): RedirectResponse
    {
        $this->authorizeAction($farm->user_id, (int) $request->user()->id, $request->user()->isAdmin());

        $validated = $request->validated();
        $validated['area'] = (float) $validated['length'] * (float) $validated['width'];

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($farm->image_path);
            $validated['image_path'] = $request->file('image')->store('farms', 'public');
        }

        $farm->update($validated);

        return back()->with('success', 'تم تحديث بيانات المزرعة.');
    }

    public function destroy(Farm $farm): RedirectResponse
    {
        $user = request()->user();
        $this->authorizeAction($farm->user_id, (int) $user->id, $user->isAdmin());

        Storage::disk('public')->delete($farm->image_path);
        $farm->delete();

        return back()->with('success', 'تم حذف إعلان المزرعة.');
    }

    private function authorizeAction(int $ownerId, int $userId, bool $isAdmin): void
    {
        abort_unless($isAdmin || $ownerId === $userId, 403);
    }
}
