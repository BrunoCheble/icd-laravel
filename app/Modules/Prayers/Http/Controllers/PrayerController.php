<?php

namespace App\Modules\Prayers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Prayers\Http\Requests\ListPrayersRequest;
use App\Modules\Prayers\Models\Prayer;
use App\Modules\Prayers\Services\ListPrayersService;
use App\Modules\Prayers\Services\RemovePrayerService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PrayerController extends Controller
{
    public function index(ListPrayersRequest $request, ListPrayersService $service): View
    {
        $filters = $request->validated();
        $prayers = $service->execute($filters);
        $periodCounts = $service->countByPeriod();
        return view('prayers::prayers.index', compact('prayers', 'filters', 'periodCounts'));
    }

    public function destroy(Prayer $prayer, RemovePrayerService $service): RedirectResponse
    {
        try {
            $service->execute($prayer);
        } catch (Exception $e) {
            return redirect()->back()->with('error', __('Failed to delete prayer.'));
        }

        return redirect()->back()->with('success', __('Prayer deleted successfully.'));
    }
}
