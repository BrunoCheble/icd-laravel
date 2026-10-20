<?php

namespace App\Modules\Members\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Members\Http\Requests\PublicMemberRequest;
use App\Modules\Members\Models\Member;
use App\Modules\Members\Services\RegisterPublicMemberService;
use Illuminate\Http\JsonResponse;

/**
 * The church site's own forms: whether a NIF is a member's, and becoming a member (waiting for the team).
 */
class PublicMemberController extends Controller
{
    public function check(string $document): JsonResponse
    {
        return response()->json(['exists' => Member::where('document_number', preg_replace('/\D+/', '', $document))->exists()]);
    }

    public function store(PublicMemberRequest $request, RegisterPublicMemberService $service): JsonResponse
    {
        $service->execute($request->validated());

        return response()->json([
            'title' => __('Welcome to the church\'s family!'),
            'message' => __('We received your registration. The team will confirm it soon; you can already announce on the site with your NIF.'),
        ], 201);
    }
}
