<?php

declare(strict_types=1);

use App\Enums\ExportFormat;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| Pluck Helpers (Cached)
|--------------------------------------------------------------------------
|
| These helper functions return cached select/dropdown lists for commonly
| used models and enums. Each uses a 60-second TTL to keep data fresh
| while avoiding repeated database queries.
|
*/

if (! function_exists('pluck_category_list')) {
    /**
     * @return array<int, string>
     */
    function pluck_category_list(): array
    {
        return Cache::remember('pluck_category_list', 60, function () {
            return Category::query()
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        });
    }
}

if (! function_exists('pluck_category_list_by_type')) {
    /**
     * @return array<int, string>
     */
    function pluck_category_list_by_type(string $type): array
    {
        return Cache::remember("pluck_category_list_{$type}", 60, function () use ($type) {
            return Category::query()
                ->where('type', $type)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        });
    }
}

if (! function_exists('pluck_brand_list')) {
    /**
     * @return array<int, string>
     */
    function pluck_brand_list(): array
    {
        return Cache::remember('pluck_brand_list', 60, function () {
            return \App\Domains\Brand\Models\Brand::query()
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        });
    }
}

if (! function_exists('pluck_brand_list_by_category')) {
    /**
     * @return array<int, string>
     */
    function pluck_brand_list_by_category(int $categoryId): array
    {
        return Cache::remember("pluck_brand_list_category_{$categoryId}", 60, function () use ($categoryId) {
            return \App\Domains\Brand\Models\Brand::query()
                ->where('category_id', $categoryId)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        });
    }
}

if (! function_exists('pluck_user_list')) {
    /**
     * @return array<int, string>
     */
    function pluck_user_list(): array
    {
        return Cache::remember('pluck_user_list', 60, function () {
            return User::query()
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        });
    }
}

if (! function_exists('pluck_active_user_list')) {
    /**
     * @return array<int, string>
     */
    function pluck_active_user_list(): array
    {
        return Cache::remember('pluck_active_user_list', 60, function () {
            return User::query()
                ->where('status', UserStatus::ACTIVE)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        });
    }
}

if (! function_exists('pluck_user_role_list')) {
    /**
     * @return array<string, string>
     */
    function pluck_user_role_list(): array
    {
        return collect(UserRole::cases())
            ->mapWithKeys(fn (UserRole $role) => [$role->value => $role->label()])
            ->toArray();
    }
}

if (! function_exists('pluck_user_status_list')) {
    /**
     * @return array<string, string>
     */
    function pluck_user_status_list(): array
    {
        return collect(UserStatus::cases())
            ->mapWithKeys(fn (UserStatus $status) => [$status->value => $status->label()])
            ->toArray();
    }
}

if (! function_exists('pluck_export_format_list')) {
    /**
     * @return array<string, string>
     */
    function pluck_export_format_list(): array
    {
        return collect(ExportFormat::cases())
            ->mapWithKeys(fn (ExportFormat $format) => [$format->value => $format->label()])
            ->toArray();
    }
}

if (! function_exists('pluck_setting_group_list')) {
    /**
     * @return array<int, string>
     */
    function pluck_setting_group_list(): array
    {
        return Cache::remember('pluck_setting_group_list', 60, function () {
            return Setting::query()
                ->select('group')
                ->distinct()
                ->orderBy('group')
                ->pluck('group')
                ->toArray();
        });
    }
}
