<?php

namespace App\Modules\Ministries\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ministries\Http\Requests\MinistryRequest;
use App\Modules\Ministries\Models\Ministry;
use App\Modules\Ministries\Services\GetMinistryFormOptionsService;
use App\Modules\Ministries\Services\ListMinistriesService;
use App\Modules\Ministries\Services\RemoveMinistryService;
use App\Modules\Ministries\Services\SaveMinistryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MinistryController extends Controller
{
    public function index(ListMinistriesService $service): View
    {
        return view('ministries::ministries.index', ['ministries' => $service->execute()]);
    }

    public function create(): View
    {
        return view('ministries::ministries.form', ['ministry' => new Ministry()]);
    }

    public function store(MinistryRequest $request, SaveMinistryService $service): RedirectResponse
    {
        $ministry = $service->execute($request->validated());

        return redirect()->route('ministries.show', $ministry)->with('success', __('Ministry saved.'));
    }

    /** Its roles and members (with each one's role, order and biography). */
    public function show(Ministry $ministry, GetMinistryFormOptionsService $options): View
    {
        $ministry->load(['roles', 'memberships.member', 'memberships.ministryRole']);

        return view('ministries::ministries.show', ['ministry' => $ministry, 'people' => $options->people($ministry)]);
    }

    public function edit(Ministry $ministry): View
    {
        return view('ministries::ministries.form', ['ministry' => $ministry]);
    }

    public function update(MinistryRequest $request, Ministry $ministry, SaveMinistryService $service): RedirectResponse
    {
        $service->execute($request->validated(), $ministry);

        return redirect()->route('ministries.show', $ministry)->with('success', __('Ministry saved.'));
    }

    public function destroy(Ministry $ministry, RemoveMinistryService $service): RedirectResponse
    {
        $service->execute($ministry);

        return redirect()->route('ministries.index')->with('success', __('Ministry deleted.'));
    }
}
