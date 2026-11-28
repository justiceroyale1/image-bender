<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\ImageGenerator;
use App\Data\ImageGeneration\UsageBucketData;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(ImageGenerator $generator): Response
    {
        return Inertia::render('Dashboard', [
            'usageSummary' => Inertia::defer(function () use ($generator): array {
                try {
                    // Defaults to last 30 UTC days when no dates are passed
                    $usage = $generator->usage()->withMarkup();
                    $today = $usage->data[array_key_last($usage->data)] ?? null;

                    return [
                        'period' => [
                            'start_date' => $usage->startDate,
                            'end_date' => $usage->endDate,
                        ],
                        'total_generations' => $usage->total->generations,
                        'total_tokens' => $usage->total->inputTextTokens
                            + $usage->total->inputImageTokens
                            + $usage->total->outputTokens
                            + $usage->total->reasoningTokens,
                        'total_billed_usd' => $usage->total->costUsd,
                        'today_generations' => $today->generations ?? 0,
                        'today_billed_usd' => $today->costUsd ?? '0.000000000',
                        'recent_days' => array_map(
                            fn (UsageBucketData $day): array => $day->toArray(),
                            array_slice($usage->data, -7)
                        ),
                        'error' => null,
                    ];
                } catch (RequestException $e) {
                    return [
                        'error' => $e->response->json('error.message') ?? 'Unable to fetch usage data.',
                    ];
                } catch (ConnectionException $e) {
                    logger()->critical($e->getMessage(), $e->getTrace());

                    return [
                        'error' => 'Connection failed.',
                    ];
                }
            }),
        ]);
    }
}
