<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformTemplate;
use App\Services\PlatformTemplateLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminTemplateController extends Controller
{
    public function __construct(private readonly PlatformTemplateLibrary $library) {}

    public function index(): Response
    {
        return Inertia::render('admin/Templates', [
            'templates' => PlatformTemplate::query()
                ->withCount('tenantCopies')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (PlatformTemplate $template): array => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'slug' => $template->slug,
                    'html' => $template->html,
                    'css' => $template->css,
                    'schema' => $template->schema ?? [],
                    'token_cost' => $template->token_cost,
                    'is_active' => $template->is_active,
                    'sort_order' => $template->sort_order,
                    'tenant_copies_count' => $template->tenant_copies_count,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $template = PlatformTemplate::query()->create([
            ...$this->templateData($validated),
            'sort_order' => (int) PlatformTemplate::query()->max('sort_order') + 10,
        ]);

        $this->library->sync($template);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Template platform dibuat dan disinkronkan ke tenant.']);

        return to_route('admin.templates.index');
    }

    public function update(Request $request, PlatformTemplate $platformTemplate): RedirectResponse
    {
        $validated = $this->validated($request, $platformTemplate);

        $platformTemplate->update($this->templateData($validated));

        $this->library->sync($platformTemplate);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Template diperbarui dan disinkronkan ke tenant.']);

        return to_route('admin.templates.index');
    }

    public function destroy(Request $request, PlatformTemplate $platformTemplate): RedirectResponse
    {
        $platformTemplate->delete();

        Inertia::flash('toast', ['type' => 'warning', 'message' => 'Template dihapus dari katalog platform.']);

        return to_route('admin.templates.index');
    }

    public function duplicate(Request $request, PlatformTemplate $platformTemplate): RedirectResponse
    {
        $copy = PlatformTemplate::query()->create([
            'name' => $platformTemplate->name.' (Copy)',
            'slug' => $this->uniqueSlug($platformTemplate->slug),
            'html' => $platformTemplate->html,
            'css' => $platformTemplate->css,
            'schema' => $platformTemplate->schema ?? [],
            'token_cost' => $platformTemplate->token_cost,
            'is_active' => $platformTemplate->is_active,
            'sort_order' => (int) PlatformTemplate::query()->max('sort_order') + 10,
        ]);

        $this->library->sync($copy);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Template diduplikasi.']);

        return to_route('admin.templates.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?PlatformTemplate $template = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:100', 'alpha_dash',
                Rule::unique('platform_templates', 'slug')->ignore($template?->id),
            ],
            'html' => ['required', 'string', 'max:100000'],
            'css' => ['nullable', 'string', 'max:100000'],
            'schema_json' => ['nullable', 'json', 'max:50000'],
            'token_cost' => ['required', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function templateData(array $validated): array
    {
        $schema = json_decode((string) ($validated['schema_json'] ?? '[]'), true);

        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?: Str::slug($validated['name']),
            'html' => $validated['html'],
            'css' => $validated['css'] ?? null,
            'schema' => is_array($schema) ? $schema : [],
            'token_cost' => $validated['token_cost'],
            'is_active' => $validated['is_active'],
        ];
    }

    private function uniqueSlug(string $baseSlug): string
    {
        $base = Str::slug($baseSlug).'-copy';
        $candidate = $base;
        $suffix = 2;

        while (PlatformTemplate::query()->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
