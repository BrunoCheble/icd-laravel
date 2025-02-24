<?php

namespace App\Http\Controllers;

use App\Helpers\ArrayHelper;
use App\Enums\MemberOptions;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Requests\MemberRequest;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\MembershipStatus;
use App\Helpers\MemberHelper;
use App\Services\UploadMemberPhoto;
use App\Services\GetMemberPendingInformationService;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $members = Member::byStatus($request->membership_status)->filter($request->attribute, $request->search)->orderBy($request->sort ?? 'created_at', $request->order ?? 'desc')->paginate();

        foreach ($members as $member) {
            $member->pendingInformations = GetMemberPendingInformationService::execute($member);
        }

        $membershipStatus = MembershipStatus::options();
        $availableAttributes = MemberOptions::options();
        $view = $request->query('view') ?? 'table';

        return view('member.index', compact('members', 'membershipStatus', 'availableAttributes', 'view'))
            ->with('i', ($request->input('page', 1) - 1) * $members->perPage());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $member = new Member();
        $member->membership_status = MembershipStatus::ACTIVED;

        $members = Member::all();

        $genders = Gender::options();
        $mothers = ArrayHelper::toKeyValueArray(MemberHelper::getAdultWomen($members), 'id', 'full_name');
        $fathers = ArrayHelper::toKeyValueArray(MemberHelper::getAdultMen($members), 'id', 'full_name');
        $spouses = ArrayHelper::toKeyValueArray(MemberHelper::getSingleByOppositeGender($members, null), 'id', 'full_name');
        $membershipStatus = MembershipStatus::options();
        $maritalStatus = MaritalStatus::options();

        return view('member.create', compact(
            'genders',
            'member',
            'mothers',
            'fathers',
            'spouses',
            'membershipStatus',
            'maritalStatus'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MemberRequest $request): RedirectResponse
    {
        try {
            Member::create($request->validated());
        } catch (\Exception $e) {
            return Redirect::route('members.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('members.index')
            ->with('success', 'Member created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show($id): View
    {
        $member = Member::find($id);

        return view('member.show', compact('member'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id): View
    {
        $member = Member::findOrFail($id);
        $members = Member::all()->where('id', '!=', $id);

        $genders = Gender::options();
        $mothers = ArrayHelper::toKeyValueArray(MemberHelper::getAdultWomen($members), 'id', 'full_name');
        $fathers = ArrayHelper::toKeyValueArray(MemberHelper::getAdultMen($members), 'id', 'full_name');
        $spouses = ArrayHelper::toKeyValueArray(MemberHelper::getSingleByOppositeGender($members, null), 'id', 'full_name');
        $membershipStatus = MembershipStatus::options();
        $maritalStatus = MaritalStatus::options();

        return view('member.edit', compact(
            'member',
            'genders',
            'mothers',
            'fathers',
            'spouses',
            'membershipStatus',
            'maritalStatus'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MemberRequest $request, Member $member): RedirectResponse
    {
        try {
            $member->update($request->validated());
        } catch (\Exception $e) {
            return Redirect::route('members.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('members.index')
            ->with('success', 'Member updated successfully');
    }


    public function destroy($id, Request $request): RedirectResponse
    {
        try {
            $member = Member::findOrFail($id);
            $isActived = $member->isActive();

            if ($member->membership_status == MembershipStatus::ACTIVED) {
                $member->membership_status = MembershipStatus::INACTIVED;
                $member->notes .= ' - Motivo da desativação: ' . $request->input('reason');
                $member->save();
            }
            else if($member->membership_status == MembershipStatus::PENDING) {
                $member->delete();
            }
        } catch (\Exception $e) {
            return Redirect::route('members.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('members.index')
            ->with('success', $isActived ? __('Member deactivated successfully') : __('Member deleted successfully'));
    }

    public function uploadPhoto($id, Request $request): RedirectResponse
    {
        try {
            $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
            $member = Member::findOrFail($id);
            UploadMemberPhoto::uploadAndSave($request->file('photo'), $member);
        } catch (\Exception $e) {
            return Redirect::route('members.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('members.index')
            ->with('success', __('Member updated successfully'));
    }

    public function activate($id): RedirectResponse
    {
        try {
            $member = Member::findOrFail($id);
            $member->membership_status = MembershipStatus::ACTIVED;
            $member->save();
        } catch (\Exception $e) {
            return Redirect::route('members.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('members.index')
            ->with('success', __('Member activated successfully'));
    }

    public function generateMemberCard($id): View
    {
        $member = Member::findOrFail($id);

        return view('card', compact('member'));
    }
}
