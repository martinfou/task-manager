<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PwaController extends Controller
{
    /**
     * Web app manifest (installable PWA shell).
     */
    public function manifest(): JsonResponse
    {
        $name = (string) config('app.name');

        return response()->json([
            'name' => $name,
            'short_name' => Str::limit($name, 12, ''),
            'description' => 'Google Tasks in the browser.',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#0f172a',
            'theme_color' => '#0f172a',
            'icons' => [
                [
                    'src' => '/icons/icon-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => '/icons/icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json; charset=UTF-8',
        ]);
    }
}
