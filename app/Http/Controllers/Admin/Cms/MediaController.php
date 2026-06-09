<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Content\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Media library (Wave 1c, D-024). Stored via Laravel Storage (config-driven disk).
 * `alt_text` is REQUIRED for images (WCAG 1.1.1 / W1c-2). mime/size validated.
 */
class MediaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('cms.media.upload'), 403);

        return response()->json(
            Media::query()->select(['id', 'type', 'original_name', 'alt_text', 'created_at'])->paginate(25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('cms.media.upload'), 403);

        $data = $request->validate([
            'type' => ['required', 'in:image,document,video,other'],
            'file' => ['required', 'file', 'max:'.(int) config('ics.media.max_kb', 10240)],
            // WCAG 1.1.1 (W1c-2): images MUST carry descriptive alt text.
            'alt_text' => ['nullable', 'string', 'max:255', 'required_if:type,image'],
        ]);

        $file = $request->file('file');
        $path = $file->store(config('ics.media.path', 'media'), config('ics.media.disk', 'public'));

        $media = Media::create([
            'type' => $data['type'],
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_kb' => (int) ceil($file->getSize() / 1024),
            'alt_text' => $data['alt_text'] ?? null,
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json([
            'id' => $media->id,
            'url' => Storage::disk(config('ics.media.disk', 'public'))->url($path),
        ], 201);
    }

    public function destroy(Request $request, Media $medium): JsonResponse
    {
        abort_unless($request->user()->can('cms.media.delete'), 403);
        $medium->delete(); // soft delete; file retained for audit/restore

        return response()->json(['message' => __('Media removed.')]);
    }
}
