<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

it('generates secure asset URLs for HTTPS requests forwarded by a proxy', function (): void {
    URL::useOrigin('http://example.test');
    Route::get('/trusted-proxy-asset-url', fn (): string => asset('build/assets/app.css'));

    $response = $this
        ->withServerVariables(['HTTP_X_FORWARDED_PROTO' => 'https'])
        ->get('/trusted-proxy-asset-url');

    $response
        ->assertOk()
        ->assertContent('https://example.test/build/assets/app.css');
});
