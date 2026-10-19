<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientTokensException;
use App\Exceptions\SubscriptionInactiveException;
use App\Models\ContentTemplate;
use App\Services\PlatformTemplateLibrary;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContentTemplateController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly PlatformTemplateLibrary $library,
    ) {}

    public function index(Request $request): Response
    {
        $tenant = $this->currentTenant->resolve($request);

        $templates = ContentTemplate::query()
            ->with('platformTemplate')
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('platform_template_id')
            ->where('is_active', true)
            ->whereHas('platformTemplate', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get()
            ->map(fn (ContentTemplate $template): array => [
                'id' => $template->id,
                'name' => $template->name,
                'slug' => $template->slug,
                'html' => $template->html,
                'css' => $template->css,
                'schema' => $template->schema ?? [],
                'token_cost' => (int) ($template->platformTemplate?->token_cost ?? 0),
                'is_rented' => (bool) $template->is_rented,
            ]);

        return Inertia::render('templates/Index', [
            'tenant' => ['id' => $tenant->id, 'name' => $tenant->name],
            'templates' => $templates,
            'balance' => $tenant->token_balance,
        ]);
    }

    public function rent(Request $request, ContentTemplate $template): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $template);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        try {
            $this->library->rent($tenant, $template, $request->user());
        } catch (InsufficientTokensException|SubscriptionInactiveException $exception) {
            return back()->withErrors(['billing' => $exception->getMessage()]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Template {$template->name} berhasil disewa.",
        ]);

        return back();
    }
}
