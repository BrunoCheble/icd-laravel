<?php

namespace App\Http\Controllers;

use App\Helpers\ArrayHelper;
use App\Models\Ministry;
use App\Http\Requests\MinistryMemberRequest; // Importa a classe de Request
use App\Models\Member;
use App\Models\MinistryMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;


class MinistryMemberController extends Controller
{
    public function manage(int $ministryId): View
    {
        $ministry = Ministry::findOrFail($ministryId);
        $members = ArrayHelper::toKeyValueArray(Member::all(), 'id', 'first_and_last_name');

        $ministryMembers = MinistryMember::where('ministry_id', $ministryId)->get();

        return view('ministries-members.index', compact('ministry', 'members', 'ministryMembers'));
    }

    public function store(int $ministryId, MinistryMemberRequest $request): RedirectResponse
    {
        try {
            MinistryMember::create($request->validated());
        } catch (\Exception $e) {
            return Redirect::route('ministries-members.manage', $ministryId)
                ->with('error', __('Something went wrong'));
        }
        return Redirect::route('ministries-members.manage', $ministryId)
            ->with('success', 'Ministry created successfully.');
    }

    public function destroy($id): RedirectResponse
    {
        $member = MinistryMember::findOrFail($id);
        $ministryId = $member->ministry_id;
        $member->delete();

        return redirect()->route('ministries-members.manage', $ministryId)
                         ->with('success', 'Member removed successfully.');
    }
}
