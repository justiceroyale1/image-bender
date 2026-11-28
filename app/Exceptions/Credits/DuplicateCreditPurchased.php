<?php

namespace App\Exceptions\Credits;

use Exception;

class DuplicateCreditPurchased extends Exception
{
    protected $message = 'This credit purchase has already been processed.';
}
