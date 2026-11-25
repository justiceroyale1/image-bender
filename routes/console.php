<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Artisan::command('inspire', function (): void {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote');

Schedule::command('model:prune')->daily();
