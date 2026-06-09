<?php

namespace App\Http\Controllers\Training\Admin;

use App\Http\Controllers\Controller;
use App\Models\Training\TrainingCourse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Course management (Wave 4a). Permission-gated (training.courses.*). Courses are NOT
 * ContentAccessible (D-057); publish is a simple status transition.
 */
class CourseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('training.courses.read.all'), 403);

        return response()->json(
            TrainingCourse::query()->select(['id', 'title', 'slug', 'status', 'is_paid', 'enrollment_count'])->paginate(25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('training.courses.create'), 403);

        $data = $request->validate([
            'instructor_id' => ['nullable', 'integer', 'exists:training_instructors,id'],
            'category_id' => ['nullable', 'integer', 'exists:training_course_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:training_courses,slug'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_paid' => ['nullable', 'boolean'],
            'level' => ['nullable', 'in:beginner,intermediate,advanced,all'],
            'delivery_mode' => ['nullable', 'in:online,in_person,hybrid'],
            'duration_hours' => ['nullable', 'numeric', 'min:0'],
            'validity_months' => ['nullable', 'integer', 'between:1,120'], // D-059 expiry
        ]);
        $data['created_by'] = $request->user()->id;

        $course = TrainingCourse::create($data);

        return response()->json(['id' => $course->id], 201);
    }

    public function update(Request $request, TrainingCourse $course): JsonResponse
    {
        abort_unless($request->user()->can('training.courses.update'), 403);

        $course->update($request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_paid' => ['nullable', 'boolean'],
            'level' => ['nullable', 'in:beginner,intermediate,advanced,all'],
            'delivery_mode' => ['nullable', 'in:online,in_person,hybrid'],
            'duration_hours' => ['nullable', 'numeric', 'min:0'],
            'validity_months' => ['nullable', 'integer', 'between:1,120'],
        ]));

        return response()->json(['message' => __('Course updated.')]);
    }

    public function publish(Request $request, TrainingCourse $course): JsonResponse
    {
        abort_unless($request->user()->can('training.courses.publish'), 403); // P-1 human approval
        $course->forceFill(['status' => 'published', 'published_at' => $course->published_at ?? now()])->save();

        return response()->json(['message' => __('Course published.')]);
    }

    public function addLesson(Request $request, TrainingCourse $course): JsonResponse
    {
        abort_unless($request->user()->can('training.courses.update'), 403);

        $lesson = $course->lessons()->create($request->validate([
            'section_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:video,pdf,text,quiz,assignment'],
            'content' => ['nullable', 'string'],
            'video_embed_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'is_preview' => ['nullable', 'boolean'],
        ]));

        return response()->json(['id' => $lesson->id], 201);
    }
}
