<?php

namespace App\Providers;

use App\Contracts\ImageGenerator;
use App\Services\ImageGeneration\MockImageGenerator;
use App\Services\ImageGeneration\PixelforgeImageGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(fn ($app): ImageGenerator => match (config('ai-image.driver')) {
            'pixelforge' => new PixelforgeImageGenerator(
                baseUrl: config('ai-image.base_url'),
                apiKey: config('ai-image.api_key'),
            ),
            default => new MockImageGenerator,
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );

        JsonResource::withoutWrapping();
    }
}
