<?php

namespace App\Modules\Members\Http\Controllers;

use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Modules\Members\Http\Requests\ListMembersRequest;
use App\Modules\Members\Http\Requests\MemberRequest;
use App\Modules\Members\Http\Requests\RemoveMemberRequest;
use App\Modules\Members\Http\Requests\UploadMemberPhotoRequest;
use App\Modules\Members\Models\Member;
use App\Modules\Members\Services\ActivateMemberService;
use App\Modules\Members\Services\GetMemberFormOptionsService;
use App\Modules\Members\Services\GetMemberPendingInformationService;
use App\Modules\Members\Services\GetMemberProfileService;
use App\Modules\Members\Services\ListMembersService;
use App\Modules\Members\Services\RemoveMemberService;
use App\Modules\Members\Services\SaveMemberService;
use App\Modules\Members\Services\UploadMemberPhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(ListMembersRequest $request, ListMembersService $service): View
    {
        $filters = $request->validated();
        $members = $service->execute($filters);
        $statusCounts = $service->countByStatus();
        return view('members::members.index', compact('members', 'filters', 'statusCounts'));
    }

    public function create(GetMemberFormOptionsService $options): View
    {
        $member = new Member();
        $member->membership_status = MembershipStatus::ACTIVED;
        return view('members::members.create', ['member' => $member] + $options->execute());
    }

    public function store(MemberRequest $request, SaveMemberService $service): RedirectResponse
    {
        try {
            $service->execute($request->validated());
        } catch (\Exception $e) {
            return Redirect::route('members.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('members.index')
            ->with('success', __('Member created successfully'));
    }

    public function show(Member $member, GetMemberProfileService $service): View
    {
        return view('members::members.show', ['member' => $member] + $service->execute($member));
    }

    public function edit(Member $member, GetMemberFormOptionsService $options, GetMemberPendingInformationService $pending): View
    {
        return view('members::members.edit', ['member' => $member, 'pendingFields' => $pending->fields($member)] + $options->execute($member));
    }

    public function update(MemberRequest $request, Member $member, SaveMemberService $service): RedirectResponse
    {
        try {
            $service->execute($request->validated(), $member);
        } catch (\Exception $e) {
            return Redirect::route('members.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('members.index')
            ->with('success', __('Member updated successfully'));
    }

    /**
     * Deactivates an active member (with the reason) or deletes a pending one.
     */
    public function destroy(RemoveMemberRequest $request, Member $member, RemoveMemberService $service): RedirectResponse
    {
        try {
            $result = $service->execute($member, $request->validated('reason'));
        } catch (\Exception $e) {
            return Redirect::route('members.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('members.index')
            ->with('success', $result === RemoveMemberService::DELETED ? __('Member deleted successfully') : __('Member deactivated successfully'));
    }

    public function uploadPhoto(UploadMemberPhotoRequest $request, $id, UploadMemberPhotoService $service): RedirectResponse
    {
        try {
            $service->execute(Member::findOrFail($id), $request->file('photo'));
        } catch (\Exception $e) {
            return Redirect::back()
                ->with('error', __('Something went wrong'));
        }

        return Redirect::back()
            ->with('success', __('Member updated successfully'));
    }

    public function activate($id, ActivateMemberService $service): RedirectResponse
    {
        try {
            $service->execute(Member::findOrFail($id));
        } catch (\Exception $e) {
            return Redirect::route('members.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::back()
            ->with('success', __('Member activated successfully'));
    }

    public function generateMemberCard($id): View
    {
        $member = Member::findOrFail($id);

        return view('card', compact('member'));
    }
}
