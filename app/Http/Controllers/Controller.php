<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesApiInput;

abstract class Controller
{
    use ValidatesApiInput;
}
