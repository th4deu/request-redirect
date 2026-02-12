<?php

use Illuminate\Support\Facades\Route;

Route::any('{path?}', function ($path = '') {
    $targetUrl = 'https://notifications.zig.tickets/' . $path;

    if (request()->getQueryString()) {
        $targetUrl .= '?' . request()->getQueryString();
    }

    $headers = [];
    foreach (request()->headers->all() as $key => $values) {
        if (!in_array(strtolower($key), ['host', 'content-length', 'connection'])) {
            $headers[] = $key . ': ' . $values[0];
        }
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $targetUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_CUSTOMREQUEST => request()->method(),
        CURLOPT_POSTFIELDS => request()->getContent(),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return response()->json(['error' => $error], 502);
    }

    $responseHeaders = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    $headerLines = explode("\r\n", trim($responseHeaders));
    $parsedHeaders = [];
    foreach ($headerLines as $line) {
        if (strpos($line, ':') !== false) {
            [$key, $value] = explode(':', $line, 2);
            $key = strtolower(trim($key));
            if (!in_array($key, ['transfer-encoding', 'connection', 'content-encoding'])) {
                $parsedHeaders[trim($key)] = trim($value);
            }
        }
    }

    return response($body, $httpCode)->withHeaders($parsedHeaders);
})->where('path', '.*');
