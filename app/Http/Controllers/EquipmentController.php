<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Models\Equipment;
use App\Support\DigitNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentController extends Controller
{
    public function store(StoreEquipmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['seller_name'] = DigitNormalizer::toArabicIndicDigits((string) $request->user()->name);
        $validated = $this->normalizeTextDigitsToArabicIndic($validated);
        $validated['image_path'] = $request->file('image')->store('equipment', 'public');

        $request->user()->equipment()->create($validated);

        return back()->with('success', 'تم إضافة إعلان المعدات بنجاح.');
    }

    public function edit(Equipment $equipment): Response
    {
        $user = request()->user();
        $this->authorizeAction($equipment->user_id, (int) $user->id, $user->isAdmin());

        return Inertia::render('Equipment/Edit', [
            'equipment' => [
                'id' => $equipment->id,
                'product_name' => $equipment->product_name,
                'seller_name' => $equipment->seller_name,
                'phone' => $equipment->phone,
                'location_text' => $equipment->location_text,
                'price' => $equipment->price,
                'description' => $equipment->description,
                'image_url' => '/storage/' . ltrim($equipment->image_path, '/'),
            ],
        ]);
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment): RedirectResponse
    {
        $this->authorizeAction($equipment->user_id, (int) $request->user()->id, $request->user()->isAdmin());

        $validated = $request->validated();
        $validated = $this->normalizeTextDigitsToArabicIndic($validated);

        if ($request->hasFile('image')) {
            if (Equipment::where('image_path', $equipment->image_path)->count() <= 1) {
                Storage::disk('public')->delete($equipment->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('equipment', 'public');
        }

        $equipment->update($validated);

        if ($request->user()->isAdmin()) {
            return redirect()->route('dashboard', ['panel' => 'equipment'])->with('success', 'تم تحديث إعلان المعدات.');
        }

        return redirect()->route('dashboard', ['panel' => 'myads'])->with('success', 'تم تحديث إعلان المعدات.');
    }

    public function destroy(Equipment $equipment): RedirectResponse
    {
        $user = request()->user();
        $this->authorizeAction($equipment->user_id, (int) $user->id, $user->isAdmin());

        if (Equipment::where('image_path', $equipment->image_path)->count() <= 1) {
            Storage::disk('public')->delete($equipment->image_path);
        }
        $equipment->delete();

        return back()->with('success', 'تم حذف إعلان المعدات.');
    }

    private function authorizeAction(int $ownerId, int $userId, bool $isAdmin): void
    {
        abort_unless($isAdmin || $ownerId === $userId, 403);
    }

    private function normalizeTextDigitsToArabicIndic(array $validated): array
    {
        foreach (['product_name', 'seller_name', 'phone', 'location_text', 'description'] as $field) {
            if (array_key_exists($field, $validated) && is_string($validated[$field])) {
                $validated[$field] = DigitNormalizer::toArabicIndicDigits($validated[$field]);
            }
        }

        return $validated;
    }
}
