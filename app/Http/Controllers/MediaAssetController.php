<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use App\Support\ActivityLogger;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MediaAssetController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $tenant = $this->currentTenant->resolve($request);

        return Inertia::render('media-assets/Index', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'mediaAssets' => MediaAsset::query()
                ->where('tenant_id', $tenant->id)
                ->latest()
                ->get()
                ->map(fn (MediaAsset $asset): array => [
                    'id' => $asset->id,
                    'name' => $asset->name,
                    'type' => $asset->type,
                    'disk' => $asset->disk,
                    'path' => $asset->path,
                    'url' => $this->mediaUrl($asset),
                    'availability' => $this->availability($asset),
                    'mime_type' => $asset->mime_type,
                    'size' => $asset->size,
                    'duration_seconds' => $asset->duration_seconds,
                    'width' => $asset->width,
                    'height' => $asset->height,
                    'created_at' => $asset->created_at?->diffForHumans(),
                ]),
            'types' => [
                MediaAsset::TYPE_IMAGE,
                MediaAsset::TYPE_VIDEO,
                MediaAsset::TYPE_AUDIO,
                MediaAsset::TYPE_WEB,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeCanManage($request, $tenant);
        $validated = $this->validated($request);
        $type = $validated['type'];

        if ($type === MediaAsset::TYPE_WEB) {
            $mediaAsset = MediaAsset::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'type' => $type,
                'disk' => 'public',
                'path' => $validated['url'],
                'duration_seconds' => $validated['duration_seconds'] ?? null,
                'metadata' => [
                    'source' => 'url',
                ],
            ]);

            $this->activityLogger->log($request, $tenant, 'media.created', $mediaAsset, [
                'media_name' => $mediaAsset->name,
                'media_type' => $mediaAsset->type,
                'source' => 'url',
            ]);

            return to_route('media-assets.index');
        }

        $file = $request->file('file');
        abort_unless($file, 422);

        $path = $file->store("tenants/{$tenant->id}/media", 'public');
        [$width, $height] = $type === MediaAsset::TYPE_IMAGE
            ? $this->imageDimensions($file->getRealPath())
            : [null, null];

        $mediaAsset = MediaAsset::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'type' => $type,
            'disk' => 'public',
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'duration_seconds' => $validated['duration_seconds'] ?? null,
            'width' => $width,
            'height' => $height,
            'metadata' => [
                'original_name' => $file->getClientOriginalName(),
            ],
        ]);

        $this->activityLogger->log($request, $tenant, 'media.created', $mediaAsset, [
            'media_name' => $mediaAsset->name,
            'media_type' => $mediaAsset->type,
            'disk' => $mediaAsset->disk,
            'size' => $mediaAsset->size,
        ]);

        return to_route('media-assets.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MediaAsset $mediaAsset): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $mediaAsset);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:86400'],
        ]);

        $mediaAsset->update($validated);

        $this->activityLogger->log($request, $tenant, 'media.updated', $mediaAsset, [
            'media_name' => $mediaAsset->name,
            'media_type' => $mediaAsset->type,
            'duration_seconds' => $mediaAsset->duration_seconds,
        ]);

        return to_route('media-assets.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, MediaAsset $mediaAsset): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $mediaAsset);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        $properties = [
            'media_name' => $mediaAsset->name,
            'media_type' => $mediaAsset->type,
            'disk' => $mediaAsset->disk,
        ];

        $this->activityLogger->log($request, $tenant, 'media.deleted', $mediaAsset, $properties);

        if ($mediaAsset->type !== MediaAsset::TYPE_WEB) {
            Storage::disk($mediaAsset->disk)->delete($mediaAsset->path);
        }

        $mediaAsset->delete();

        return to_route('media-assets.index');
    }

    /**
     * @return array{name: string, type: string, file?: mixed, url?: string, duration_seconds?: int|null}
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in([MediaAsset::TYPE_IMAGE, MediaAsset::TYPE_VIDEO, MediaAsset::TYPE_AUDIO, MediaAsset::TYPE_WEB])],
            'file' => ['nullable', 'file', 'max:102400', 'required_unless:type,'.MediaAsset::TYPE_WEB],
            'url' => ['nullable', 'url', 'max:2048', 'required_if:type,'.MediaAsset::TYPE_WEB],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:86400'],
        ]);
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function imageDimensions(string|false $path): array
    {
        if (! is_string($path)) {
            return [null, null];
        }

        $dimensions = @getimagesize($path);

        if (! $dimensions) {
            return [null, null];
        }

        return [$dimensions[0], $dimensions[1]];
    }

    private function mediaUrl(MediaAsset $mediaAsset): string
    {
        if ($mediaAsset->type === MediaAsset::TYPE_WEB) {
            return $mediaAsset->path;
        }

        if ($mediaAsset->disk === 'public') {
            return asset('storage/'.ltrim($mediaAsset->path, '/'));
        }

        return Storage::disk($mediaAsset->disk)->url($mediaAsset->path);
    }

    /**
     * @return array{status: string, label: string, message: string}
     */
    private function availability(MediaAsset $mediaAsset): array
    {
        if ($mediaAsset->type === MediaAsset::TYPE_WEB) {
            return [
                'status' => 'external',
                'label' => 'External URL',
                'message' => 'URL web akan dimuat langsung oleh player.',
            ];
        }

        if (! Storage::disk($mediaAsset->disk)->exists($mediaAsset->path)) {
            return [
                'status' => 'missing',
                'label' => 'File missing',
                'message' => 'File tidak ditemukan di storage. Upload ulang atau hapus media ini.',
            ];
        }

        if ($mediaAsset->disk === 'public' && ! file_exists(public_path('storage'))) {
            return [
                'status' => 'warning',
                'label' => 'Storage link missing',
                'message' => 'Jalankan php artisan storage:link agar file bisa diakses browser.',
            ];
        }

        return [
            'status' => 'ok',
            'label' => 'Ready',
            'message' => 'File tersedia dan siap dipakai playlist.',
        ];
    }
}
