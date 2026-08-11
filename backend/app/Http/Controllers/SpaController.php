<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class SpaController extends Controller
{
    public function __invoke(?string $any = null): BinaryFileResponse|Response
    {
        $spa = public_path('index.html');

        if (file_exists($spa)) {
            return response()->file($spa);
        }

        return response('SPA not built. Run frontend build:deploy.', 503);
    }
}
