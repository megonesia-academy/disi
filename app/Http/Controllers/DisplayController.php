<?php

namespace App\Http\Controllers;

use App\Models\DisplayPairingSession;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DisplayController extends Controller
{
    public function show(Request $request): Response
    {
        $pairingSession = DisplayPairingSession::query()->create([
            'code' => $this->code(),
            'token' => Str::random(64),
            'expires_at' => now()->addMinutes(10),
        ]);

        $pairingUrl = route('display.pair.show', ['code' => $pairingSession->code]);

        return Inertia::render('display/Show', [
            'code' => $pairingSession->code,
            'token' => $pairingSession->token,
            'statusUrl' => route('api.display.status', ['token' => $pairingSession->token]),
            'pairingUrl' => $pairingUrl,
            'qrSvg' => $this->qrSvg($pairingUrl),
            'expiresAt' => $pairingSession->expires_at->toISOString(),
        ]);
    }

    private function code(): string
    {
        do {
            $code = Str::upper(Str::random(3).'-'.Str::random(3));
        } while (DisplayPairingSession::query()->where('code', $code)->exists());

        return $code;
    }

    private function qrSvg(string $value): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(280, 2),
            new SvgImageBackEnd,
        );

        return (new Writer($renderer))->writeString($value);
    }
}
