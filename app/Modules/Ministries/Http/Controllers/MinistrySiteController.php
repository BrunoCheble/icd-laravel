<?php

namespace App\Modules\Ministries\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ministries\Http\Requests\MinistrySiteRequest;
use App\Modules\Ministries\Models\Ministry;
use App\Modules\Ministries\Services\SaveMinistrySiteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** A ministry's page on the church site ("Conheça Nossos Ministérios"): its card and its text in sections. */
class MinistrySiteController extends Controller
{
    public function edit(Ministry $ministry): View
    {
        return view('ministries::ministries.site', ['ministry' => $ministry]);
    }

    public function update(MinistrySiteRequest $request, Ministry $ministry, SaveMinistrySiteService $service): RedirectResponse
    {
        $service->execute($ministry, $request->validated());

        return redirect()->route('ministries.show', $ministry)->with('success', __('Page on the site saved.'));
    }
}
