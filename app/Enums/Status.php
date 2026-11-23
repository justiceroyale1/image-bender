<?php

namespace App\Enums;

enum Status: string {
    case Queued = 'queued';
    case Processing = 'processing';
    case Succeeded  = 'succeeded';
    case Failed = 'failed';
}