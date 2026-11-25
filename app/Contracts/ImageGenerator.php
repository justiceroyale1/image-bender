<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\ImageGeneration\GenerationData;
use App\Data\ImageGeneration\UsageData;
use App\Models\ImageGeneration;

interface ImageGenerator
{
    public function generate(ImageGeneration $generation): GenerationData;

    public function usage(?string $startDate = null, ?string $endDate = null): UsageData;
}
