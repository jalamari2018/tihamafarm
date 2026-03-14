<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Farm;
use App\Models\Harvest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        }

        $requestedPanel = (string) $request->query('panel', 'myads');
        $legacyToCurrent = [
            'overview' => 'myads',
            'create' => 'myads',
            'farms' => 'myads',
            'harvests' => 'myads',
            'equipment' => 'myads',
        ];
        $normalizedPanel = $legacyToCurrent[$requestedPanel] ?? $requestedPanel;
        $allowedPanels = ['myads', 'profile'];
        $initialPanel = in_array($normalizedPanel, $allowedPanels, true) ? $normalizedPanel : 'myads';

        return Inertia::render('Dashboard', [
            'initialPanel' => $initialPanel,
            'stats' => [
                'farms_count' => Farm::where('user_id', $user->id)->count(),
                'harvests_count' => Harvest::where('user_id', $user->id)->count(),
                'equipment_count' => Equipment::where('user_id', $user->id)->count(),
            ],
            'myFarms' => Farm::query()
                ->where('user_id', $user->id)
                ->latest()
                ->get()
                ->map(fn (Farm $farm) => [
                    'id' => $farm->id,
                    'farm_name' => $farm->farm_name,
                    'farmer_name' => $farm->farmer_name,
                    'location_text' => $farm->location_text,
                    'phone' => $farm->phone,
                    'length' => $farm->length,
                    'width' => $farm->width,
                    'area' => $farm->area,
                    'has_well' => $farm->has_well,
                    'has_electricity' => $farm->has_electricity,
                    'description' => $farm->description,
                    'image_url' => '/storage/' . ltrim($farm->image_path, '/'),
                ]),
            'myHarvests' => Harvest::query()
                ->where('user_id', $user->id)
                ->latest()
                ->get()
                ->map(fn (Harvest $harvest) => [
                    'id' => $harvest->id,
                    'harvest_name' => $harvest->harvest_name,
                    'farmer_name' => $harvest->farmer_name,
                    'location_text' => $harvest->location_text,
                    'phone' => $harvest->phone,
                    'ready_status' => $harvest->ready_status,
                    'ready_date' => $harvest->ready_date?->toDateString(),
                    'ready_in_days' => $harvest->ready_in_days,
                    'description' => $harvest->description,
                    'image_url' => '/storage/' . ltrim($harvest->image_path, '/'),
                ]),
            'myEquipment' => Equipment::query()
                ->where('user_id', $user->id)
                ->latest()
                ->get()
                ->map(fn (Equipment $equipment) => [
                    'id' => $equipment->id,
                    'product_name' => $equipment->product_name,
                    'seller_name' => $equipment->seller_name,
                    'location_text' => $equipment->location_text,
                    'phone' => $equipment->phone,
                    'price' => $equipment->price,
                    'description' => $equipment->description,
                    'image_url' => '/storage/' . ltrim($equipment->image_path, '/'),
                ]),
        ]);
    }

    private function adminDashboard(): Response
    {
        $requestedPanel = (string) request()->query('panel', 'stats');
        $allowedPanels = ['stats', 'farms', 'harvests', 'equipment', 'users', 'profile'];
        $initialPanel = in_array($requestedPanel, $allowedPanels, true) ? $requestedPanel : 'stats';

        return Inertia::render('Admin/Dashboard', [
            'initialPanel' => $initialPanel,
            'stats' => [
                'users_count' => User::count(),
                'farms_count' => Farm::count(),
                'harvests_count' => Harvest::count(),
                'equipment_count' => Equipment::count(),
                'harvest_ready_now' => Harvest::where('ready_status', 'now')->count(),
                'harvest_ready_future' => Harvest::where('ready_status', 'future')->count(),
            ],
            'users' => User::query()
                ->withCount(['farms', 'harvests', 'equipment'])
                ->latest()
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'farms_count' => $user->farms_count,
                    'harvests_count' => $user->harvests_count,
                    'equipment_count' => $user->equipment_count,
                    'created_at' => $user->created_at?->toDateString(),
                ]),
            'recentFarms' => Farm::query()
                ->with('user:id,name')
                ->latest()
                ->take(20)
                ->get()
                ->map(fn (Farm $farm) => [
                    'id' => $farm->id,
                    'farm_name' => $farm->farm_name,
                    'farmer_name' => $farm->farmer_name,
                    'owner_name' => $farm->user?->name,
                    'phone' => $farm->phone,
                    'location_text' => $farm->location_text,
                    'area' => $farm->area,
                    'length' => $farm->length,
                    'width' => $farm->width,
                    'has_well' => $farm->has_well,
                    'has_electricity' => $farm->has_electricity,
                    'description' => $farm->description,
                    'image_url' => '/storage/' . ltrim($farm->image_path, '/'),
                    'created_at' => $farm->created_at?->toDateString(),
                ]),
            'recentHarvests' => Harvest::query()
                ->with('user:id,name')
                ->latest()
                ->take(20)
                ->get()
                ->map(fn (Harvest $harvest) => [
                    'id' => $harvest->id,
                    'harvest_name' => $harvest->harvest_name,
                    'farmer_name' => $harvest->farmer_name,
                    'owner_name' => $harvest->user?->name,
                    'phone' => $harvest->phone,
                    'location_text' => $harvest->location_text,
                    'ready_status' => $harvest->ready_status,
                    'ready_date' => $harvest->ready_date?->toDateString(),
                    'ready_in_days' => $harvest->ready_in_days,
                    'description' => $harvest->description,
                    'image_url' => '/storage/' . ltrim($harvest->image_path, '/'),
                    'created_at' => $harvest->created_at?->toDateString(),
                ]),
            'recentEquipment' => Equipment::query()
                ->with('user:id,name')
                ->latest()
                ->take(20)
                ->get()
                ->map(fn (Equipment $equipment) => [
                    'id' => $equipment->id,
                    'product_name' => $equipment->product_name,
                    'seller_name' => $equipment->seller_name,
                    'owner_name' => $equipment->user?->name,
                    'phone' => $equipment->phone,
                    'price' => $equipment->price,
                    'location_text' => $equipment->location_text,
                    'description' => $equipment->description,
                    'image_url' => '/storage/' . ltrim($equipment->image_path, '/'),
                    'created_at' => $equipment->created_at?->toDateString(),
                ]),
        ]);
    }
}
