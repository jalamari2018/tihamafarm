<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Farm;
use App\Models\Harvest;
use Inertia\Inertia;
use Inertia\Response;

class MarketplaceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Marketplace/Index', [
            'farms' => Farm::query()->latest()->get()->map(fn (Farm $farm) => [
                'id' => $farm->id,
                'farm_name' => $farm->farm_name,
                'farmer_name' => $farm->farmer_name,
                'phone' => $farm->phone,
                'location_text' => $farm->location_text,
                'length' => $farm->length,
                'width' => $farm->width,
                'area' => $farm->area,
                'has_well' => $farm->has_well,
                'has_electricity' => $farm->has_electricity,
                'description' => $farm->description,
                'image_url' => '/storage/' . ltrim($farm->image_path, '/'),
                'created_at' => $farm->created_at?->toDateString(),
            ]),
            'harvests' => Harvest::query()->latest()->get()->map(fn (Harvest $harvest) => [
                'id' => $harvest->id,
                'harvest_name' => $harvest->harvest_name,
                'farmer_name' => $harvest->farmer_name,
                'phone' => $harvest->phone,
                'location_text' => $harvest->location_text,
                'ready_status' => $harvest->ready_status,
                'ready_date' => $harvest->ready_date?->toDateString(),
                'ready_in_days' => $harvest->ready_in_days,
                'description' => $harvest->description,
                'image_url' => '/storage/' . ltrim($harvest->image_path, '/'),
                'created_at' => $harvest->created_at?->toDateString(),
            ]),
            'equipment' => Equipment::query()->latest()->get()->map(fn (Equipment $equipment) => [
                'id' => $equipment->id,
                'product_name' => $equipment->product_name,
                'seller_name' => $equipment->seller_name,
                'phone' => $equipment->phone,
                'location_text' => $equipment->location_text,
                'price' => $equipment->price,
                'description' => $equipment->description,
                'image_url' => '/storage/' . ltrim($equipment->image_path, '/'),
                'created_at' => $equipment->created_at?->toDateString(),
            ]),
        ]);
    }
}
