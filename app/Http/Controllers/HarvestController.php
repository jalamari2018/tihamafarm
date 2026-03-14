<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHarvestRequest;
use App\Http\Requests\UpdateHarvestRequest;
use App\Models\Harvest;
use App\Support\DigitNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HarvestController extends Controller
{
    public function store(StoreHarvestRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated = $this->normalizeTextDigitsToArabicIndic($validated);
        $validated['image_path'] = $request->file('image')->store('harvests', 'public');

        $request->user()->harvests()->create($validated);

        return back()->with('success', 'تم إضافة إعلان المحصول بنجاح.');
    }

    public function edit(Harvest $harvest): Response
    {
        $user = request()->user();
        $this->authorizeAction($harvest->user_id, (int) $user->id, $user->isAdmin());

        return Inertia::render('Harvests/Edit', [
            'harvest' => [
                'id' => $harvest->id,
                'harvest_name' => $harvest->harvest_name,
                'farmer_name' => $harvest->farmer_name,
                'phone' => $harvest->phone,
                'location_text' => $harvest->location_text,
                'ready_status' => $harvest->ready_status,
                'ready_date' => $harvest->ready_date?->toDateString(),
                'description' => $harvest->description,
                'image_url' => '/storage/' . ltrim($harvest->image_path, '/'),
            ],
        ]);
    }

    public function update(UpdateHarvestRequest $request, Harvest $harvest): RedirectResponse
    {
        $this->authorizeAction($harvest->user_id, (int) $request->user()->id, $request->user()->isAdmin());

        $validated = $request->validated();
        $validated = $this->normalizeTextDigitsToArabicIndic($validated);

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($harvest->image_path);
            $validated['image_path'] = $request->file('image')->store('harvests', 'public');
        }

        $harvest->update($validated);

        return redirect()->route('dashboard')->with('success', 'تم تحديث إعلان المحصول.');
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

    private function normalizeTextDigitsToArabicIndic(array $validated): array
    {
        foreach (['harvest_name', 'farmer_name', 'phone', 'location_text', 'description'] as $field) {
            if (array_key_exists($field, $validated) && is_string($validated[$field])) {
                $validated[$field] = DigitNormalizer::toArabicIndicDigits($validated[$field]);
            }
        }

        return $validated;
    }
}
