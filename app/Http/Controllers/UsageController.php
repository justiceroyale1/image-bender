<?php

namespace App\Http\Controllers;

use App\Contracts\ImageGenerator;
use App\Http\Requests\UsageRequest;
use Illuminate\Http\Client\RequestException;
use Inertia\Inertia;
use Inertia\Response;

class UsageController extends Controller
{
    public function __invoke(UsageRequest $request, ImageGenerator $generator): Response
    {
        try {
            $usage = $generator
                ->usage($request->startDate(), $request->endDate())
                ->withMarkup();

            return Inertia::render('usage/Index', [
                'usage' => $usage,
                'filters' => $request->filters(),
                'error' => null,
            ]);
        } catch (RequestException $e) {
            return Inertia::render('usage/Index', [
                'usage' => null,
                'filters' => $request->filters(),
                'error' => $e->response->json('error.message') ?? 'Unable to fetch usage data.',
            ]);
        }
    }


}
