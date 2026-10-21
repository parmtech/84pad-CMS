<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class GuidedTourController extends Controller
{
    public function complete(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isOwner(), 403);
        DB::table('core_user_preferences')->updateOrInsert(
            ['user_id' => $request->user()->id],
            ['tour_completed' => true, 'updated_at' => now()]
        );
        return response()->json(['completed' => true]);
    }
}
