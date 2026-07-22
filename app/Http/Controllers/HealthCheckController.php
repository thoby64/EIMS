<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class HealthCheckController extends Controller
{
    public function __invoke(): Response
    {
        return response('ok', 200);
    }
}
