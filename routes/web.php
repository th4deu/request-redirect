<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::any('{path?}', function ($path = '') {
    $targetUrl = 'https://notifications.zig.tickets/' . $path;

    if (request()->getQueryString()) {
        $targetUrl .= '?' . request()->getQueryString();
    }

    $response = Http::withoutVerifying()
        ->withHeaders(
            collect(request()->headers->all())
                ->except(['host', 'content-length'])
                ->map(fn ($values) => $values[0])
                ->toArray()
        )
        ->withOptions([
            'curl' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ],
        ])
        ->send(
            request()->method(),
            $targetUrl,
            [
                'body' => request()->getContent(),
            ]
        );

    return response($response->body(), $response->status())
        ->withHeaders($response->headers());
})->where('path', '.*');
