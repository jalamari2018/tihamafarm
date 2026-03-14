<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Farm;
use App\Models\Harvest;
use App\Models\User;
use App\Support\DigitNormalizer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Admin',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $regularUsers = collect([
            ['name' => 'مستخدم ١', 'email' => 'user1@example.com'],
            ['name' => 'مستخدم ٢', 'email' => 'user2@example.com'],
            ['name' => 'مستخدم ٣', 'email' => 'user3@example.com'],
            ['name' => 'مستخدم ٤', 'email' => 'user4@example.com'],
            ['name' => 'مستخدم ٥', 'email' => 'user5@example.com'],
        ])->map(function (array $userData) {
            return User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'role' => 'user',
                    'email_verified_at' => now(),
                ]
            );
        })->values();

        $regularUserIds = $regularUsers->pluck('id')->all();

        $farmImagePath = $this->seedImageToPublicDisk('farm.jpg', 'farms');
        $harvestImagePath = $this->seedImageToPublicDisk('harvest.jpg', 'harvests');
        $equipmentImagePath = $this->seedImageToPublicDisk('equipment.jpg', 'equipment');

        Farm::query()->delete();
        Harvest::query()->delete();
        Equipment::query()->delete();

        for ($i = 1; $i <= 10; $i++) {
            $length = (float) (40 + ($i * 2));
            $width = (float) (30 + $i);
            $ownerId = $regularUserIds[($i - 1) % count($regularUserIds)];

            Farm::query()->create([
                'user_id' => $ownerId,
                'farm_name' => DigitNormalizer::toArabicIndicDigits("مزرعة رقم {$i}"),
                'farmer_name' => DigitNormalizer::toArabicIndicDigits("مزارع {$i}"),
                'phone' => DigitNormalizer::toArabicIndicDigits('05'.str_pad((string) (10000000 + $i), 8, '0', STR_PAD_LEFT)),
                'location_text' => DigitNormalizer::toArabicIndicDigits("المنطقة الزراعية {$i}"),
                'length' => $length,
                'width' => $width,
                'area' => $length * $width,
                'has_well' => $i % 2 === 0,
                'has_electricity' => $i % 3 !== 0,
                'description' => DigitNormalizer::toArabicIndicDigits("إعلان مزرعة تجريبي رقم {$i}."),
                'image_path' => $farmImagePath,
            ]);
        }

        for ($i = 1; $i <= 10; $i++) {
            $isFuture = $i % 2 === 0;
            $readyDate = $isFuture ? Carbon::today()->addDays($i * 3)->toDateString() : null;
            $ownerId = $regularUserIds[($i - 1) % count($regularUserIds)];

            Harvest::query()->create([
                'user_id' => $ownerId,
                'harvest_name' => DigitNormalizer::toArabicIndicDigits("محصول رقم {$i}"),
                'farmer_name' => DigitNormalizer::toArabicIndicDigits("مزارع {$i}"),
                'phone' => DigitNormalizer::toArabicIndicDigits('05'.str_pad((string) (20000000 + $i), 8, '0', STR_PAD_LEFT)),
                'location_text' => DigitNormalizer::toArabicIndicDigits("موقع المحصول {$i}"),
                'ready_status' => $isFuture ? 'future' : 'now',
                'ready_date' => $readyDate,
                'description' => DigitNormalizer::toArabicIndicDigits("إعلان محصول تجريبي رقم {$i}."),
                'image_path' => $harvestImagePath,
            ]);
        }

        for ($i = 1; $i <= 10; $i++) {
            $ownerId = $regularUserIds[($i - 1) % count($regularUserIds)];

            Equipment::query()->create([
                'user_id' => $ownerId,
                'product_name' => DigitNormalizer::toArabicIndicDigits("معدات زراعية {$i}"),
                'seller_name' => DigitNormalizer::toArabicIndicDigits("بائع {$i}"),
                'phone' => DigitNormalizer::toArabicIndicDigits('05'.str_pad((string) (30000000 + $i), 8, '0', STR_PAD_LEFT)),
                'location_text' => DigitNormalizer::toArabicIndicDigits("موقع المعدات {$i}"),
                'price' => 1000 + ($i * 250),
                'description' => DigitNormalizer::toArabicIndicDigits("إعلان معدات تجريبي رقم {$i}."),
                'image_path' => $equipmentImagePath,
            ]);
        }

        $admin->forceFill(['role' => 'admin'])->save();
    }

    private function seedImageToPublicDisk(string $fileName, string $directory): string
    {
        $sourcePath = base_path($fileName);
        $targetPath = "{$directory}/seed-{$fileName}";

        if (! File::exists($sourcePath)) {
            throw new \RuntimeException("Seed image not found: {$sourcePath}");
        }

        Storage::disk('public')->put($targetPath, File::get($sourcePath));

        return $targetPath;
    }
}
