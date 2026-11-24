<?php

namespace App\Enums;

enum Status: string {
    case Queued = 'queued';
    case Processing = 'processing';
    case Succeeded  = 'succeeded';
    case Failed = 'failed';


    public function isTerminal(): bool
    {
        return in_array($this, [self::Succeeded, self::Failed], true);
    }

    public function succeeded(): bool
    {
        return $this->status === self::Succeeded;
    }

    public function failed(): bool
    {
        return $this->status === self::Failed;
    }

}