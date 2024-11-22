<?php

namespace App\Http\Controllers;

use App\Models\Ministry;
use App\Http\Requests\SaveMinistryMemberRequest; // Importa a classe de Request
use App\Models\Member;
use App\Services\MinistryMemberService; // Importa o serviço

class MinistryMemberController extends Controller
{
    protected $ministryMemberService;

    public function __construct(MinistryMemberService $ministryMemberService)
    {
        $this->ministryMemberService = $ministryMemberService;
    }

    /**
     * Display a listing of ministries.
     */
    public function index()
    {
        $ministries = Ministry::all();
        return view('ministries.index', compact('ministries'));
    }

    /**
     * Show the form for managing members of a ministry.
     */
    public function manage($ministryId)
    {
        $ministry = Ministry::findOrFail($ministryId);
        $allMembers = Member::all();
        $associatedMembers = $ministry->members()->withPivot('role', 'active')->get();

        return view('ministries.manage', compact('ministry', 'allMembers', 'associatedMembers'));
    }

    /**
     * Save or update the association of a member to a ministry.
     */
    public function save(SaveMinistryMemberRequest $request, $ministryId)
    {
        // Validate the request is automatically handled by SaveMinistryMemberRequest

        // Get the ministry and ensure it exists
        $ministry = Ministry::findOrFail($ministryId);

        // Save or update the member's association using the service
        $this->ministryMemberService->saveOrUpdateMemberAssociation($ministry, $request->validated());

        return redirect()->route('ministries.manage', $ministryId)
                         ->with('success', 'Member association saved successfully.');
    }

    /**
     * Remove a member from a ministry.
     */
    public function removeMember($ministryId, $memberId)
    {
        $ministry = Ministry::findOrFail($ministryId);

        if ($ministry->members()->find($memberId)) {
            $ministry->members()->detach($memberId);
        }

        return redirect()->route('ministries.manage', $ministryId)
                         ->with('success', 'Member removed successfully.');
    }
}
