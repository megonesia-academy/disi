<?php

namespace App\Http\Controllers;

use App\Models\ContentTemplate;
use App\Support\ActivityLogger;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ContentTemplateController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): Response
    {
        $tenant = $this->currentTenant->resolve($request);

        return Inertia::render('templates/Index', [
            'tenant' => ['id' => $tenant->id, 'name' => $tenant->name],
            'templates' => ContentTemplate::query()
                ->where('tenant_id', $tenant->id)
                ->withCount('playlistItems')
                ->orderBy('name')
                ->get()
                ->map(fn (ContentTemplate $template): array => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'slug' => $template->slug,
                    'html' => $template->html,
                    'css' => $template->css,
                    'schema' => $template->schema ?? [],
                    'is_active' => $template->is_active,
                    'playlist_items_count' => $template->playlist_items_count,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeCanManage($request, $tenant);
        $validated = $this->validated($request, $tenant);

        $template = ContentTemplate::query()->create([
            'tenant_id' => $tenant->id,
            ...$this->templateData($validated),
        ]);

        $this->activityLogger->log($request, $tenant, 'content_template.created', $template, [
            'template_name' => $template->name,
        ]);

        return to_route('templates.index');
    }

    public function update(Request $request, ContentTemplate $template): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $template);
        $this->currentTenant->authorizeCanManage($request, $tenant);
        $validated = $this->validated($request, $tenant, $template);

        $template->update($this->templateData($validated));

        $this->activityLogger->log($request, $tenant, 'content_template.updated', $template, [
            'template_name' => $template->name,
        ]);

        return to_route('templates.index');
    }

    public function destroy(Request $request, ContentTemplate $template): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $template);
        $this->currentTenant->authorizeCanManage($request, $tenant);
        $template->delete();

        return to_route('templates.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, $tenant, ?ContentTemplate $template = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:100', 'alpha_dash',
                Rule::unique('content_templates', 'slug')->where('tenant_id', $tenant->id)->ignore($template?->id),
            ],
            'html' => ['required', 'string', 'max:100000'],
            'css' => ['nullable', 'string', 'max:100000'],
            'schema_json' => ['nullable', 'json', 'max:50000'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    /** @param array<string, mixed> $validated */
    private function templateData(array $validated): array
    {
        $schema = json_decode((string) ($validated['schema_json'] ?? '[]'), true);

        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?: Str::slug($validated['name']),
            'html' => $validated['html'],
            'css' => $validated['css'] ?? null,
            'schema' => is_array($schema) ? $schema : [],
            'is_active' => $validated['is_active'],
        ];
    }
}
