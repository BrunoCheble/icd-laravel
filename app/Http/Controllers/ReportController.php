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
}
