<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFarmRequest;
use App\Http\Requests\UpdateFarmRequest;
use App\Models\Farm;
use App\Support\DigitNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FarmController extends Controller
{
    public function store(StoreFarmRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated = $this->normalizeTextDigitsToArabicIndic($validated);
        $validated['has_well'] = $request->boolean('has_well');
        $validated['has_electricity'] = $request->boolean('has_electricity');
        $validated['image_path'] = $request->file('image')->store('farms', 'public');
        $validated['area'] = (float) $validated['length'] * (float) $validated['width'];

        $request->user()->farms()->create($validated);

        return back()->with('success', 'تم إضافة المزرعة بنجاح.');
    }

    public function edit(Farm $farm): Response
    {
        $user = request()->user();
        $this->authorizeAction($farm->user_id, (int) $user->id, $user->isAdmin());

        return Inertia::render('Farms/Edit', [
            'farm' => [
                'id' => $farm->id,
                'farm_name' => $farm->farm_name,
                'farmer_name' => $farm->farmer_name,
                'phone' => $farm->phone,
                'location_text' => $farm->location_text,
                'length' => $farm->length,
                'width' => $farm->width,
                'has_well' => $farm->has_well,
                'has_electricity' => $farm->has_electricity,
                'description' => $farm->description,
                'image_url' => '/storage/' . ltrim($farm->image_path, '/'),
            ],
        ]);
    }

    public function update(UpdateFarmRequest $request, Farm $farm): RedirectResponse
    {
        $this->authorizeAction($farm->user_id, (int) $request->user()->id, $request->user()->isAdmin());

        $validated = $request->validated();
        $validated = $this->normalizeTextDigitsToArabicIndic($validated);
        $validated['has_well'] = $request->boolean('has_well');
        $validated['has_electricity'] = $request->boolean('has_electricity');
        $validated['area'] = (float) $validated['length'] * (float) $validated['width'];

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($farm->image_path);
            $validated['image_path'] = $request->file('image')->store('farms', 'public');
        }

        $farm->update($validated);

        return redirect()->route('dashboard')->with('success', 'تم تحديث بيانات المزرعة.');
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

    private function normalizeTextDigitsToArabicIndic(array $validated): array
    {
        foreach (['farm_name', 'farmer_name', 'phone', 'location_text', 'description'] as $field) {
            if (array_key_exists($field, $validated) && is_string($validated[$field])) {
                $validated[$field] = DigitNormalizer::toArabicIndicDigits($validated[$field]);
            }
        }

        return $validated;
    }
}
