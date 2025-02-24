<?php

namespace App\Http\Controllers;

use App\Enums\VisitorGroup;
use App\Enums\VisitorOptions;
use App\Enums\VisitorStatus;
use App\Helpers\NameHelper;
use App\Models\Visitor;
use App\Http\Requests\VisitorRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class VisitorController extends Controller
{
    public function index(Request $request)
    {
        $visitors = Visitor::filter($request->attribute, $request->search)->orderBy($request->sort ?? 'created_at', $request->order ?? 'desc')->paginate();
        $availableAttributes = VisitorOptions::options();
        $visitorGroupOptions = VisitorGroup::options();
        return view('visitors.index', compact('visitors', 'visitorGroupOptions','availableAttributes'))
            ->with('i', ($request->input('page', 1) - 1) * $visitors->perPage());
    }

    public function create()
    {
        return view('visitors.create');
    }

    public function store(VisitorRequest $request): RedirectResponse
    {
        try {
            Visitor::create(array_merge(
                $request->validated(),
                ['status' => VisitorStatus::ACTIVED]
            ));

            return Redirect::route('visitors.index')
                ->with('success', __('Thank you for your registration!'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Redirect::route('visitors.index')
                ->withErrors($e->validator)
                ->withInput();
        }
    }


    public function edit(Visitor $visitor)
    {
        return view('visitors.edit', compact('visitor'));
    }

    public function update(VisitorRequest $request, Visitor $visitor)
    {
        $visitor->update($request->validated());
        return redirect()->route('visitors.index')->with('success', 'Visitor updated successfully!');
    }

    public function destroy($id, Request $request): RedirectResponse
    {
        try {
            $visitor = Visitor::findOrFail($id);
            $isActived = $visitor->isActive();

            if ($visitor->status == VisitorStatus::ACTIVED) {
                $visitor->status = VisitorStatus::INACTIVED;
                $visitor->observation .= ' - Motivo da desativação: ' . $request->input('reason');

                $visitor->save();
            } else if ($visitor->status == VisitorStatus::INACTIVED) {
                $visitor->delete();
            }
        } catch (\Exception $e) {
            return Redirect::route('visitors.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('visitors.index')
            ->with('success', $isActived ? __('Visitor deactivated successfully') : __('Visitor deleted successfully'));
    }

    public function activate($id): RedirectResponse
    {
        try {
            $visitor = Visitor::findOrFail($id);
            $visitor->status = VisitorStatus::ACTIVED;
            $visitor->save();
        } catch (\Exception $e) {
            return Redirect::route('visitors.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('visitors.index')
            ->with('success', __('Visitor activated successfully'));
    }
}
