<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Models\Equipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class EquipmentController extends Controller
{
    public function store(StoreEquipmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['image_path'] = $request->file('image')->store('equipment', 'public');

        $request->user()->equipment()->create($validated);

        return back()->with('success', 'تم إضافة إعلان المعدات بنجاح.');
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment): RedirectResponse
    {
        $this->authorizeAction($equipment->user_id, (int) $request->user()->id, $request->user()->isAdmin());

        $validated = $request->validated();

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($equipment->image_path);
            $validated['image_path'] = $request->file('image')->store('equipment', 'public');
        }

        $equipment->update($validated);

        return back()->with('success', 'تم تحديث إعلان المعدات.');
    }

    public function destroy(Equipment $equipment): RedirectResponse
    {
        $user = request()->user();
        $this->authorizeAction($equipment->user_id, (int) $user->id, $user->isAdmin());

        Storage::disk('public')->delete($equipment->image_path);
        $equipment->delete();

        return back()->with('success', 'تم حذف إعلان المعدات.');
    }

    private function authorizeAction(int $ownerId, int $userId, bool $isAdmin): void
    {
        abort_unless($isAdmin || $ownerId === $userId, 403);
    }
}
