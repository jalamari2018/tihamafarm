<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHarvestRequest;
use App\Http\Requests\UpdateHarvestRequest;
use App\Models\Harvest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class HarvestController extends Controller
{
    public function store(StoreHarvestRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['image_path'] = $request->file('image')->store('harvests', 'public');

        $request->user()->harvests()->create($validated);

        return back()->with('success', 'تم إضافة إعلان المحصول بنجاح.');
    }

    public function update(UpdateHarvestRequest $request, Harvest $harvest): RedirectResponse
    {
        $this->authorizeAction($harvest->user_id, (int) $request->user()->id, $request->user()->isAdmin());

        $validated = $request->validated();

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($harvest->image_path);
            $validated['image_path'] = $request->file('image')->store('harvests', 'public');
        }

        $harvest->update($validated);

        return back()->with('success', 'تم تحديث إعلان المحصول.');
    }

    public function destroy(Harvest $harvest): RedirectResponse
    {
        $user = request()->user();
        $this->authorizeAction($harvest->user_id, (int) $user->id, $user->isAdmin());

        Storage::disk('public')->delete($harvest->image_path);
        $harvest->delete();

        return back()->with('success', 'تم حذف إعلان المحصول.');
    }

    private function authorizeAction(int $ownerId, int $userId, bool $isAdmin): void
    {
        abort_unless($isAdmin || $ownerId === $userId, 403);
    }
}
