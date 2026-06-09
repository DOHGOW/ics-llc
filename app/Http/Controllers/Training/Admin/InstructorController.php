<?php

namespace App\Http\Controllers\Training\Admin;

use App\Events\Training\InstructorApproved;
use App\Http\Controllers\Controller;
use App\Models\Training\Instructor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Instructor onboarding (Wave 4a). A user applies (pending); ICS training staff approve
 * (training.instructors.manage) — audited (InstructorApproved, TRAINING_MANAGEMENT).
 */
class InstructorController extends Controller
{
    /** A user applies to become an instructor (self). */
    public function apply(Request $request): JsonResponse
    {
        $instructor = Instructor::updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validate([
                'bio' => ['nullable', 'string'],
                'specializations' => ['nullable', 'array'],
            ]) + ['status' => 'pending'],
        );

        return response()->json(['id' => $instructor->id, 'status' => $instructor->status], 201);
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('training.instructors.manage'), 403);

        return response()->json(
            Instructor::query()->select(['id', 'user_id', 'status', 'approved_at'])->paginate(25)
        );
    }

    public function approve(Request $request, Instructor $instructor): JsonResponse
    {
        abort_unless($request->user()->can('training.instructors.manage'), 403);

        $instructor->forceFill([
            'status' => 'active',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ])->save();

        event(new InstructorApproved($instructor, $request->user()->id, $request->user()->getRoleNames()->first()));

        return response()->json(['message' => __('Instructor approved.')]);
    }
}
