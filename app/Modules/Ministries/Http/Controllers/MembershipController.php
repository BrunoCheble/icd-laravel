<?php

namespace App\Modules\Ministries\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ministries\Http\Requests\AddMinistryMemberRequest;
use App\Modules\Ministries\Http\Requests\MembershipRequest;
use App\Modules\Ministries\Models\Ministry;
use App\Modules\Ministries\Models\MinistryMember;
use App\Modules\Ministries\Services\AddMinistryMemberService;
use App\Modules\Ministries\Services\RemoveMembershipService;
use App\Modules\Ministries\Services\SaveMembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MembershipController extends Controller
{
    public function store(AddMinistryMemberRequest $request, Ministry $ministry, AddMinistryMemberService $service): RedirectResponse
    {
        $service->execute($ministry, $request->validated());

        return redirect()->to(route('ministries.show', $ministry) . '#members')->with('success', __('Member added to the ministry.'));
    }

    /** The member's role, order and whether active in the ministry, and their biography. */
    public function edit(MinistryMember $membership): View
    {
        $membership->load(['ministry.roles', 'member']);

        return view('ministries::memberships.edit', ['membership' => $membership]);
    }

    public function update(MembershipRequest $request, MinistryMember $membership, SaveMembershipService $service): RedirectResponse
    {
        $service->execute($membership, $request->validated());

        return redirect()->to(route('ministries.show', $membership->ministry_id) . '#members')->with('success', __('Saved.'));
    }

    public function destroy(MinistryMember $membership, RemoveMembershipService $service): RedirectResponse
    {
        $service->execute($membership);

        return redirect()->to(route('ministries.show', $membership->ministry_id) . '#members')->with('success', __('Member removed from the ministry.'));
    }
}
