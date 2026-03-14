<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Farm;
use App\Models\Harvest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'myFarms' => Farm::query()
                ->where('user_id', $user->id)
                ->latest()
                ->get()
                ->map(fn (Farm $farm) => [
                    'id' => $farm->id,
                    'farm_name' => $farm->farm_name,
                    'location_text' => $farm->location_text,
                    'phone' => $farm->phone,
                    'area' => $farm->area,
                    'image_url' => Storage::url($farm->image_path),
                ]),
            'myHarvests' => Harvest::query()
                ->where('user_id', $user->id)
                ->latest()
                ->get()
                ->map(fn (Harvest $harvest) => [
                    'id' => $harvest->id,
                    'harvest_name' => $harvest->harvest_name,
                    'location_text' => $harvest->location_text,
                    'phone' => $harvest->phone,
                    'ready_status' => $harvest->ready_status,
                    'ready_in_days' => $harvest->ready_in_days,
                    'image_url' => Storage::url($harvest->image_path),
                ]),
            'myEquipment' => Equipment::query()
                ->where('user_id', $user->id)
                ->latest()
                ->get()
                ->map(fn (Equipment $equipment) => [
                    'id' => $equipment->id,
                    'product_name' => $equipment->product_name,
                    'location_text' => $equipment->location_text,
                    'phone' => $equipment->phone,
                    'price' => $equipment->price,
                    'image_url' => Storage::url($equipment->image_path),
                ]),
        ]);
    }
}
