<?php

namespace App\Modules\Visitors\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Visitors\Http\Requests\ListVisitorsRequest;
use App\Modules\Visitors\Http\Requests\RemoveVisitorRequest;
use App\Modules\Visitors\Http\Requests\VisitorRequest;
use App\Modules\Visitors\Models\Visitor;
use App\Modules\Visitors\Services\ActivateVisitorService;
use App\Modules\Visitors\Services\ListVisitorsService;
use App\Modules\Visitors\Services\RemoveVisitorService;
use App\Modules\Visitors\Services\SaveVisitorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class VisitorController extends Controller
{
    public function index(ListVisitorsRequest $request, ListVisitorsService $service): View
    {
        $filters = $request->validated();
        $visitors = $service->execute($filters);
        $statusCounts = $service->countByStatus();
        $periodCounts = $service->countByPeriod();
        return view('visitors::visitors.index', compact('visitors', 'filters', 'statusCounts', 'periodCounts'));
    }

    public function create(): View
    {
        $visitor = new Visitor();
        $visitor->created_by = auth()->user()->name;
        return view('visitors::visitors.create', compact('visitor'));
    }

    public function store(VisitorRequest $request, SaveVisitorService $service): RedirectResponse
    {
        try {
            $service->execute($request->validated());
        } catch (\Exception $e) {
            return Redirect::route('visitors.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('visitors.index')
            ->with('success', __('Visitor created successfully'));
    }

    public function edit(Visitor $visitor): View
    {
        return view('visitors::visitors.edit', compact('visitor'));
    }

    public function update(VisitorRequest $request, Visitor $visitor, SaveVisitorService $service): RedirectResponse
    {
        try {
            $service->execute($request->validated(), $visitor);
        } catch (\Exception $e) {
            return Redirect::route('visitors.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('visitors.index')
            ->with('success', __('Visitor updated successfully!'));
    }

    /**
     * Deactivates an active visitor (with the reason) or deletes an inactive one.
     */
    public function destroy(RemoveVisitorRequest $request, Visitor $visitor, RemoveVisitorService $service): RedirectResponse
    {
        try {
            $result = $service->execute($visitor, $request->validated('reason'));
        } catch (\Exception $e) {
            return Redirect::route('visitors.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('visitors.index')
            ->with('success', $result === RemoveVisitorService::DELETED ? __('Visitor deleted successfully') : __('Visitor deactivated successfully'));
    }

    public function activate($id, ActivateVisitorService $service): RedirectResponse
    {
        try {
            $service->execute(Visitor::findOrFail($id));
        } catch (\Exception $e) {
            return Redirect::route('visitors.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::back()
            ->with('success', __('Visitor activated successfully'));
    }
}
