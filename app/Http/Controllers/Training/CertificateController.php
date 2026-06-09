<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Models\Training\Certificate;
use App\Services\Training\CertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Certificate access + governance (Wave 4a / D-059). Verification is PUBLIC + minimal
 * disclosure. Revoke/reissue are staff-only and audited HIGH (D-058). PDF is owner/staff-gated.
 */
class CertificateController extends Controller
{
    public function __construct(private readonly CertificateService $certificates) {}

    /** PUBLIC verification — no auth; minimal disclosure (D-059 §2). */
    public function verify(string $number): JsonResponse
    {
        $result = $this->certificates->verify($number);
        abort_if($result === null, 404);

        return response()->json($result);
    }

    public function mine(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('training.certificates.read.own'), 403);

        return response()->json(
            Certificate::query()->where('user_id', $request->user()->id)
                ->with('course:id,title')
                ->select(['id', 'course_id', 'certificate_number', 'issued_at', 'expires_at', 'status'])->paginate(25)
        );
    }

    public function download(Request $request, Certificate $certificate): StreamedResponse
    {
        // Owner or training staff only.
        $isOwner = (int) $certificate->user_id === (int) $request->user()->id;
        abort_unless($isOwner || $request->user()->can('training.certificates.issue'), 403);
        abort_unless($certificate->pdf_path !== null, 404);

        $disk = Storage::disk(config('ics.media.disk', 'public'));
        abort_unless($disk->exists($certificate->pdf_path), 404);

        return $disk->download($certificate->pdf_path, $certificate->certificate_number.'.pdf');
    }

    public function revoke(Request $request, Certificate $certificate): JsonResponse
    {
        abort_unless($request->user()->can('training.certificates.issue'), 403); // staff governance
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $this->certificates->revoke($certificate, $data['reason'], $request->user());

        return response()->json(['message' => __('Certificate revoked.')]);
    }

    public function reissue(Request $request, Certificate $certificate): JsonResponse
    {
        abort_unless($request->user()->can('training.certificates.issue'), 403);

        $new = $this->certificates->reissue($certificate, $request->user());

        return response()->json(['certificate_number' => $new->certificate_number], 201);
    }
}
