<?php

namespace App\Http\Controllers;

class PublicUrlFallbackController extends Controller
{
    public function __invoke(): never
    {
        abort(404);
    }
}
