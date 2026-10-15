<?php

namespace App\Http\Controllers;

use App\Models\Screen;
use App\Services\PlayerManifestResolver;
use App\Support\CurrentTenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ScreenPreviewController extends Controller
{
    public function __construct(private readonly CurrentTenant $currentTenant) {}

    public function __invoke(Request $request, Screen $screen, PlayerManifestResolver $resolver): Response
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $screen);

        $manifest = null;
        $error = null;

        try {
            $manifest = $resolver->resolveScreen($screen);
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() !== 404) {
                throw $exception;
            }

            $error = 'Screen belum punya schedule aktif, playlist aktif, atau screen sedang inactive.';
        }

        return Inertia::render('screens/Preview', [
            'screen' => [
                'id' => $screen->id,
                'name' => $screen->name,
                'location' => $screen->location,
                'orientation' => $screen->orientation,
                'resolution' => [
                    'width' => $screen->resolution_width,
                    'height' => $screen->resolution_height,
                ],
            ],
            'manifest' => $manifest,
            'error' => $error,
        ]);
    }
}
