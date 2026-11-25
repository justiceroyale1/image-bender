<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImageGenerationController;
use App\Http\Controllers\NewImageGenerationPageController;
use App\Http\Controllers\UsageController;
use App\Http\Controllers\Webhooks\PixelforgeWebhookController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    
    Route::get('generations/new', NewImageGenerationPageController::class)
        ->name('generations.create');
    Route::redirect('/generations', '/generations/new');
    Route::resource('generations', ImageGenerationController::class)
        ->only(['store', 'show']);

    Route::get('/usage', UsageController::class)->name('usage.index');
});

Route::post('/webhooks/pixelforge', PixelforgeWebhookController::class);

// phpinfo() prints every environment variable, including APP_KEY and DB_PASSWORD
if (app()->isLocal()) {
    Route::get('/info', function () {
        Log::info('Phpinfo page visited');

        return response()->stream(fn (): true => phpinfo());
    });
}

Route::get('/health', function () {
    $status = [];

    // Check Database Connection
    try {
        DB::connection()->getPdo();
        // Optionally, run a simple query
        DB::select('SELECT 1');
        $status['database'] = 'OK';
    } catch (Exception) {
        $status['database'] = 'Error';
    }

    // Check Redis Connection
    try {
        Cache::store('redis')->put('health_check', 'OK', 10);
        $value = Cache::store('redis')->get('health_check');
        if ($value === 'OK') {
            $status['redis'] = 'OK';
        } else {
            $status['redis'] = 'Error';
        }
    } catch (Exception) {
        $status['redis'] = 'Error';
    }

    // Check Storage Access
    try {
        $testFile = 'health_check.txt';
        Storage::put($testFile, 'OK');
        $content = Storage::get($testFile);
        Storage::delete($testFile);

        if ($content === 'OK') {
            $status['storage'] = 'OK';
        } else {
            $status['storage'] = 'Error';
        }
    } catch (Exception) {
        $status['storage'] = 'Error';
    }

    // Determine overall health status
    $isHealthy = collect($status)->every(fn ($value): bool => $value === 'OK');

    $httpStatus = $isHealthy ? 200 : 503;

    return response()->json($status, $httpStatus);
});

require __DIR__.'/settings.php';
