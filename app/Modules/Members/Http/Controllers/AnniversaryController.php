<?php

namespace App\Modules\Members\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Members\Http\Requests\ListAnniversariesRequest;
use App\Modules\Members\Services\ListAnniversariesService;
use Illuminate\View\View;

class AnniversaryController extends Controller
{
    public function index(ListAnniversariesRequest $request, ListAnniversariesService $service): View
    {
        $month = (int) ($request->validated('month') ?? now()->month);
        return view('members::anniversaries.index', ['month' => $month] + $service->execute($month));
    }
}
