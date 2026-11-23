<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class NewImageGenerationPageController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('generations/New');
    }
}
