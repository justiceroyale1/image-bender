<?php

namespace App\Contracts;

use App\Data\GenerationData;
use App\Models\ImageGeneration;

interface ImageGenerator
{
    public function generate(ImageGeneration $generation): GenerationData;
}