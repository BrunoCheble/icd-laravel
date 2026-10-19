<?php

namespace App\Modules\Prayers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Prayers\Http\Requests\PublicPrayerRequest;
use App\Modules\Prayers\Services\RegisterPrayerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The public page where anyone leaves a prayer request (name, request, mobile number).
 */
class PublicPrayerController extends Controller
{
    public function create(): View
    {
        return view('prayers::public.create');
    }

    public function store(PublicPrayerRequest $request, RegisterPrayerService $service): RedirectResponse
    {
        $service->execute($request->validated());

        return redirect()->route('site.prayer')->with('sent', true);
    }
}
