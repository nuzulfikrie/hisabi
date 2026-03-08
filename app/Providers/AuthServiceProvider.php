<?php

namespace App\Providers;

use App\Domains\Brand\Models\Brand;
use App\Domains\Budget\Models\Budget;
use App\Domains\Transaction\Models\Transaction;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\TelegramTransaction;
use App\Models\User;
use App\Policies\BrandPolicy;
use App\Policies\BudgetPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\SettingPolicy;
use App\Policies\TagPolicy;
use App\Policies\TelegramTransactionPolicy;
use App\Policies\TransactionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Transaction::class => TransactionPolicy::class,
        Category::class => CategoryPolicy::class,
        Brand::class => BrandPolicy::class,
        Budget::class => BudgetPolicy::class,
        Setting::class => SettingPolicy::class,
        Tag::class => TagPolicy::class,
        TelegramTransaction::class => TelegramTransactionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {

        //
    }
}
