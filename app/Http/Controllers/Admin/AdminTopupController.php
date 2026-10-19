<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TokenTopupRequest;
use App\Models\TokenTransaction;
use App\Services\TenantBilling;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminTopupController extends Controller
{
    public function __construct(private readonly TenantBilling $billing) {}

    public function index(): Response
    {
        $requests = TokenTopupRequest::query()
            ->with(['tenant', 'requester'])
            ->latest()
            ->limit(100)
            ->get();

        return Inertia::render('admin/Topups', [
            'requests' => $requests->map(fn (TokenTopupRequest $topup): array => [
                'id' => $topup->id,
                'tenant' => $topup->tenant ? [
                    'id' => $topup->tenant->id,
                    'name' => $topup->tenant->name,
                    'slug' => $topup->tenant->slug,
                ] : null,
                'tokens' => $topup->tokens,
                'status' => $topup->status,
                'note' => $topup->note,
                'requested_by' => $topup->requester?->name,
                'created_at' => $topup->created_at?->diffForHumans(),
                'approved_at' => $topup->approved_at?->diffForHumans(),
            ]),
            'pending_count' => TokenTopupRequest::query()->where('status', TokenTopupRequest::STATUS_PENDING)->count(),
        ]);
    }

    public function approve(Request $request, TokenTopupRequest $topupRequest): RedirectResponse
    {
        abort_unless($topupRequest->status === TokenTopupRequest::STATUS_PENDING, 422);

        $topupRequest->loadMissing('tenant');

        if (! $topupRequest->tenant) {
            return back()->withErrors(['topup' => 'Tenant untuk request ini tidak ditemukan.']);
        }

        $this->billing->credit(
            $topupRequest->tenant,
            $topupRequest->tokens,
            TokenTransaction::TYPE_TOPUP,
            "Topup token #{$topupRequest->id}",
            null,
            ['topup_request_id' => $topupRequest->id],
            $request->user(),
        );

        $topupRequest->forceFill([
            'status' => TokenTopupRequest::STATUS_APPROVED,
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Topup disetujui dan token ditambahkan.']);

        return back();
    }

    public function reject(Request $request, TokenTopupRequest $topupRequest): RedirectResponse
    {
        abort_unless($topupRequest->status === TokenTopupRequest::STATUS_PENDING, 422);

        $topupRequest->forceFill([
            'status' => TokenTopupRequest::STATUS_REJECTED,
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
        ])->save();

        Inertia::flash('toast', ['type' => 'warning', 'message' => 'Request topup ditolak.']);

        return back();
    }
}
