<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Domains\Cms\Enums\TourScope;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('destinations')) {
            if (! Schema::hasColumn('destinations', 'country_id')) {
                Schema::table('destinations', function (Blueprint $table): void {
                    $table->foreignId('country_id')
                        ->nullable()
                        ->after('region_id')
                        ->constrained('destinations')
                        ->nullOnDelete();
                });
            }

            if (! Schema::hasColumn('destinations', 'is_country_root')) {
                Schema::table('destinations', function (Blueprint $table): void {
                    $table->boolean('is_country_root')
                        ->default(false)
                        ->after('country_id')
                        ->index();
                });
            }

            $this->backfillDefaultCountryRoot();
        }

        if (Schema::hasTable('blog_posts')) {
            if (! Schema::hasColumn('blog_posts', 'country_destination_id')) {
                Schema::table('blog_posts', function (Blueprint $table): void {
                    $table->foreignId('country_destination_id')
                        ->nullable()
                        ->after('content_category_id')
                        ->constrained('destinations')
                        ->nullOnDelete();
                });
            }

            if (! Schema::hasColumn('blog_posts', 'destination_id')) {
                Schema::table('blog_posts', function (Blueprint $table): void {
                    $table->foreignId('destination_id')
                        ->nullable()
                        ->after('country_destination_id')
                        ->constrained('destinations')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('blog_posts')) {
            Schema::table('blog_posts', function (Blueprint $table): void {
                if (Schema::hasColumn('blog_posts', 'destination_id')) {
                    $table->dropConstrainedForeignId('destination_id');
                }

                if (Schema::hasColumn('blog_posts', 'country_destination_id')) {
                    $table->dropConstrainedForeignId('country_destination_id');
                }
            });
        }

        if (Schema::hasTable('destinations')) {
            Schema::table('destinations', function (Blueprint $table): void {
                if (Schema::hasColumn('destinations', 'is_country_root')) {
                    $table->dropColumn('is_country_root');
                }
            });

            Schema::table('destinations', function (Blueprint $table): void {
                if (Schema::hasColumn('destinations', 'country_id')) {
                    $table->dropConstrainedForeignId('country_id');
                }
            });
        }
    }

    protected function backfillDefaultCountryRoot(): void
    {
        $now = now();
        $vietnamSlug = $this->countryRootSlug('viet-nam');
        $vietnamId = DB::table('destinations')->where('slug', $vietnamSlug)->value('id');

        if (! $vietnamId) {
            $vietnamId = DB::table('destinations')->where('slug', 'viet-nam')->value('id');
        }

        if (! $vietnamId) {
            $vietnamId = DB::table('destinations')->insertGetId([
                'name' => 'Việt Nam',
                'slug' => $vietnamSlug,
                'scope' => TourScope::Domestic->value,
                'excerpt' => 'Quốc gia root mặc định cho các điểm đến trong nước.',
                'status' => 'published',
                'is_featured' => false,
                'sort_order' => 0,
                'published_at' => $now,
                'robots_directive' => 'index,follow',
                'is_country_root' => true,
                'country_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('destinations')
                ->where('id', $vietnamId)
                ->update([
                    'slug' => $vietnamSlug,
                    'country_id' => null,
                    'is_country_root' => true,
                    'updated_at' => $now,
                ]);
        }

        DB::table('destinations')
            ->where('destinations.id', '<>', $vietnamId)
            ->where(function ($query): void {
                $query
                    ->whereNull('destinations.is_country_root')
                    ->orWhere('destinations.is_country_root', false);
            })
            ->whereNull('destinations.country_id')
            ->leftJoin('regions', 'destinations.region_id', '=', 'regions.id')
            ->select([
                'destinations.id',
                'destinations.name',
                'destinations.slug',
                'destinations.scope',
                'regions.name as region_name',
                'regions.slug as region_slug',
            ])
            ->orderBy('destinations.id')
            ->lazy()
            ->each(function ($destination) use ($now, $vietnamId): void {
                $countryName = $this->inferCountryName(
                    (string) $destination->name,
                    (string) $destination->slug,
                    (string) $destination->scope,
                    (string) $destination->region_name,
                    (string) $destination->region_slug,
                );
                $countryId = $countryName === 'Việt Nam'
                    ? $vietnamId
                    : $this->ensureCountryRootRow($countryName, $now);

                DB::table('destinations')
                    ->where('id', $destination->id)
                    ->update([
                        'country_id' => $countryId,
                        'updated_at' => $now,
                    ]);
            });
    }

    protected function ensureCountryRootRow(string $countryName, mixed $now): int
    {
        $slug = $this->countryRootSlug($countryName);
        $countryId = DB::table('destinations')->where('slug', $slug)->value('id');

        if ($countryId) {
            DB::table('destinations')
                ->where('id', $countryId)
                ->update([
                    'country_id' => null,
                    'is_country_root' => true,
                    'updated_at' => $now,
                ]);

            return (int) $countryId;
        }

        $legacyCountryId = DB::table('destinations')
            ->where('slug', $this->countryBaseSlug($slug))
            ->where('is_country_root', true)
            ->value('id');

        if ($legacyCountryId) {
            DB::table('destinations')
                ->where('id', $legacyCountryId)
                ->update([
                    'slug' => $slug,
                    'country_id' => null,
                    'is_country_root' => true,
                    'updated_at' => $now,
                ]);

            return (int) $legacyCountryId;
        }

        return (int) DB::table('destinations')->insertGetId([
            'name' => $countryName,
            'slug' => $slug,
            'scope' => $countryName === 'Việt Nam' ? TourScope::Domestic->value : TourScope::International->value,
            'excerpt' => 'Quốc gia root cho các điểm đến thuộc '.$countryName.'.',
            'status' => 'published',
            'is_featured' => false,
            'sort_order' => 0,
            'published_at' => $now,
            'robots_directive' => 'index,follow',
            'is_country_root' => true,
            'country_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    protected function countryBaseSlug(string $value): string
    {
        $slug = Str::slug($value);
        $prefix = 'du-lich-';

        if (str_starts_with($slug, $prefix)) {
            return substr($slug, strlen($prefix));
        }

        return $slug;
    }

    protected function countryRootSlug(string $value): string
    {
        $baseSlug = $this->countryBaseSlug($value);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'viet-nam';

        return 'du-lich-'.$baseSlug;
    }

    protected function inferCountryName(string $name, string $slug, string $scope, string $regionName, string $regionSlug): string
    {
        $haystack = Str::of($name.' '.$slug.' '.$scope.' '.$regionName.' '.$regionSlug)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->value();
        $countryMap = [
            'Việt Nam' => ['viet nam', 'mien bac', 'mien trung', 'mien nam', 'tay nguyen', 'ha noi', 'da nang', 'nha trang', 'ninh chu', 'vinh hy', 'cam ranh', 'binh hung', 'phu quoc', 'da lat'],
            'Trung Quốc' => ['trung quoc', 'thuong hai', 'bac kinh', 'le giang', 'shangrila', 'shangri la', 'dai ly', 'tay tang', 'no shop', 'noshop', 'charter'],
            'Hàn Quốc' => ['han quoc', 'seoul', 'busan'],
            'Nhật Bản' => ['nhat ban', 'tokyo', 'osaka'],
            'Đài Loan' => ['dai loan', 'taiwan'],
            'Thái Lan' => ['thai lan', 'thailand', 'bangkok'],
            'Singapore' => ['singapore'],
            'Malaysia' => ['malaysia'],
            'Ấn Độ' => ['an do', 'himalaya'],
            'Nepal' => ['nepal'],
            'Bhutan' => ['bhutan'],
            'Úc' => ['uc', 'australia'],
            'New Zealand' => ['new zealand', 'newzeland'],
            'Hoa Kỳ' => ['hoa ky', 'my', 'usa'],
            'Canada' => ['canada'],
            'Pháp' => ['phap', 'paris'],
            'Đức' => ['duc', 'germany'],
            'Ý' => ['italy'],
        ];

        foreach ($countryMap as $country => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($haystack, $keyword)) {
                    return $country;
                }
            }
        }

        return $scope === TourScope::International->value ? 'Quốc tế' : 'Việt Nam';
    }
};
