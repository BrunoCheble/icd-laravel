<?php
namespace App\Http\Controllers;

use App\Enums\VisitorGroup;
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
        $visitors = Visitor::orderBy('created_at', 'desc')->paginate();

        $visitorGroupOptions = VisitorGroup::options();
        return view('visitors.index', compact('visitors', 'visitorGroupOptions'))
            ->with('i', ($request->input('page', 1) - 1) * $visitors->perPage());
    }

    public function create()
    {
        return view('visitors.create');
    }

    public function store(VisitorRequest $request): RedirectResponse {
        try {
            $request->validated();
            $visitor = new Visitor();
            $visitor->name = NameHelper::normalizeName($request->name);
            $visitor->phone_number = $request->phone_number;
            $visitor->gender = $request->gender;
            $visitor->city = NameHelper::normalizeName($request->city);
            $visitor->group = $request->group;
            $visitor->invited_by = $request->invited_by;
            $visitor->created_by = NameHelper::normalizeName($request->created_by);
            $visitor->observation = $request->observation;
            $visitor->status = VisitorStatus::ACTIVED;

            $visitor->save();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Redirect::route('visitors.index')
                ->withErrors($e->validator)
                ->withInput();
        }

        return Redirect::route('visitors.index')
            ->with('success', __('Thank you for your registration!'))
            ->with('created_by', NameHelper::normalizeName($request->created_by));
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

    public function destroy($id): RedirectResponse
    {
        try {
            $visitor = Visitor::findOrFail($id);
            $isActived = $visitor->isActive();

            if ($visitor->status == VisitorStatus::ACTIVED) {
                $visitor->status = VisitorStatus::INACTIVED;
                $visitor->save();
            }
            else if($visitor->status == VisitorStatus::INACTIVED) {
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
