<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeaturePrice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminPricingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Pricing', [
            'prices' => FeaturePrice::query()
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get()
                ->map(fn (FeaturePrice $price): array => [
                    'id' => $price->id,
                    'key' => $price->key,
                    'label' => $price->label,
                    'description' => $price->description,
                    'kind' => $price->kind,
                    'token_cost' => $price->token_cost,
                    'free_limit' => $price->free_limit,
                    'unit_label' => $price->unit_label,
                    'is_active' => $price->is_active,
                ]),
            'kinds' => [
                FeaturePrice::KIND_ADDON,
                FeaturePrice::KIND_USAGE,
                FeaturePrice::KIND_SUBSCRIPTION,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('feature_prices', 'key')],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'kind' => ['required', Rule::in([FeaturePrice::KIND_ADDON, FeaturePrice::KIND_USAGE, FeaturePrice::KIND_SUBSCRIPTION])],
            'token_cost' => ['required', 'integer', 'min:0', 'max:1000000'],
            'free_limit' => ['required', 'integer', 'min:0', 'max:100000'],
            'unit_label' => ['nullable', 'string', 'max:40'],
        ]);

        FeaturePrice::query()->create([
            ...$validated,
            'unit_label' => $validated['unit_label'] ?? 'slot',
            'is_active' => true,
            'sort_order' => 100,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Harga fitur ditambahkan.']);

        return back();
    }

    public function update(Request $request, FeaturePrice $featurePrice): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'token_cost' => ['required', 'integer', 'min:0', 'max:1000000'],
            'free_limit' => ['required', 'integer', 'min:0', 'max:100000'],
            'unit_label' => ['nullable', 'string', 'max:40'],
            'is_active' => ['required', 'boolean'],
        ]);

        $featurePrice->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Harga fitur diperbarui.']);

        return back();
    }
}
