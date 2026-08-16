<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Builder::macro('updateOrInsert', function (array $attributes, array $values = []) {
            $instance = static::where($attributes)->first();
            
            if ($instance) {
                return static::where($attributes)->update(array_merge($values, ['updated_at' => now()]));
            }
            
            return static::insert(array_merge($attributes, $values, [
                'created_at' => $values['created_at'] ?? now(),
                'updated_at' => $values['updated_at'] ?? now(),
            ]));
        });
    }
}
