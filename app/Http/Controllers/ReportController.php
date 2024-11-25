<?php

namespace App\Http\Controllers;

use App\Helpers\ArrayHelper;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function families(Request $request): View
    {
        $members = Member::get();
        $families = ArrayHelper::groupBy($members, 'full_address');

        return view('report.families.index', compact('families'));
    }

    public function anniversaries(Request $request)
    {
        $month = $request->query('month', now()->month);

        if ($month < 1 || $month > 12) {
            return response()->json(['error' => 'Invalid month value. Must be between 1 and 12.'], 400);
        }

        $birthdays = Member::whereMonth('date_of_birth', $month)->get();
        $weddingAnniversaries = Member::whereMonth('date_joined', $month)->get();

        return view('report.anniversaries.index', [
            'month' => $month,
            'birthdays' => $birthdays,
            'weddingAnniversaries' => $weddingAnniversaries,
        ]);
    }
}
