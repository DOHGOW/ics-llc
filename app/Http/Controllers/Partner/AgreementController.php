<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner\PartnerAgreement;
use App\Services\Partner\PartnerPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Partner agreements (Wave 2). ORG-OWNED (D-055): AccountScope auto-filters; policy gates.
 * Files are policy-gated/streamed (W2-5). Create/sign are ICS-staff-managed; sign is
 * audited HIGH (D-056).
 */
class AgreementController extends Controller
{
    public function __construct(private readonly PartnerPortalService $portal) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAny(['partner.agreements.read.own', 'partner.agreements.manage']), 403);

        return response()->json(
            PartnerAgreement::query()->select(['id', 'account_id', 'partner_id', 'title', 'type', 'signed_at', 'expiry_date'])->paginate(25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('create', PartnerAgreement::class), 403);

        $validated = $request->validate([
            'partner_id' => ['required', 'integer', 'exists:partner_profiles,id'],
            'account_id' => ['required', 'integer', 'exists:crm_accounts,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'effective_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'file' => ['nullable', 'file', 'max:'.(int) config('ics.media.max_kb', 10240)],
        ]);

        $path = $request->hasFile('file')
            ? $request->file('file')->store(config('ics.media.path', 'media').'/agreements', config('ics.media.disk', 'public'))
            : null;

        $agreement = PartnerAgreement::create([
            'partner_id' => $validated['partner_id'],
            'account_id' => $validated['account_id'],
            'title' => $validated['title'],
            'type' => $validated['type'],
            'effective_date' => $validated['effective_date'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'file_path' => $path,
        ]);

        return response()->json(['id' => $agreement->id], 201);
    }

    public function sign(Request $request, PartnerAgreement $agreement): JsonResponse
    {
        abort_unless($request->user()->can('manage', $agreement), 403);
        $this->portal->signAgreement($agreement, $request->user());

        return response()->json(['message' => __('Agreement signed.')]);
    }

    /** W2-5: agreement file delivery is policy-gated/streamed; never a public URL. */
    public function download(Request $request, PartnerAgreement $agreement): StreamedResponse
    {
        abort_unless($request->user()->can('view', $agreement), 403);
        abort_unless($agreement->file_path !== null, 404);

        $disk = Storage::disk(config('ics.media.disk', 'public'));
        abort_unless($disk->exists($agreement->file_path), 404);

        return $disk->download($agreement->file_path, $agreement->title);
    }
}
