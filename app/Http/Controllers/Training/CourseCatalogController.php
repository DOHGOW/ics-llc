<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Models\Training\TrainingCourse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public course catalogue (Wave 4a). Browsing published courses is open; lesson CONTENT is
 * enrollment-gated elsewhere (EnrollmentController). Only lesson titles + is_preview show here.
 */
class CourseCatalogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $courses = TrainingCourse::query()->where('status', 'published')
            ->when($request->filled('q'), fn ($q) => $q->whereFullText(['title', 'description'], (string) $request->string('q')))
            ->select(['id', 'title', 'slug', 'description', 'price', 'currency', 'is_paid', 'level', 'delivery_mode', 'duration_hours', 'thumbnail_path'])
            ->paginate(15);

        return response()->json($courses);
    }

    public function show(string $slug): JsonResponse
    {
        $course = TrainingCourse::query()->where('status', 'published')->where('slug', $slug)
            ->with(['lessons' => fn ($q) => $q->orderBy('sort_order')
                ->select(['id', 'course_id', 'section_id', 'title', 'type', 'duration_minutes', 'is_preview'])])
            ->firstOrFail();

        return response()->json($course); // lesson bodies/files NOT included (enrollment-gated)
    }
}
