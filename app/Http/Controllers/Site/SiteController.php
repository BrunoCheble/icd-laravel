<?php

namespace App\Http\Controllers\Site;

use Illuminate\Support\Facades\Session;
use App\Enums\Gender;
use App\Enums\InvitedBy;
use App\Enums\MaritalStatus;
use App\Enums\MembershipStatus;
use App\Enums\VisitorGroup;
use App\Enums\VisitorStatus;
use App\Helpers\DateHelper;
use App\Helpers\NameHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\SiteMemberRequest;
use App\Http\Requests\VisitorRequest;
use App\Models\Member;
use App\Models\Visitor;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class SiteController extends Controller
{
    public function contact()
    {
        return view('site/contact');
    }

    public function member(): View
    {
        $member = new Member();
        return view('site.member', compact('member'));
    }

    public function register(SiteMemberRequest $request): RedirectResponse
    {
        try {
            $fullName = NameHelper::splitFullName($request->full_name);
            Member::create(array_merge(
                $request->validated(),
                [
                    'first_name' => $fullName['first_name'],
                    'middle_name' => $fullName['middle_name'],
                    'last_name' => $fullName['last_name'],
                    'membership_status' => MembershipStatus::PENDING,
                ]
            ));

            return Redirect::route('site.member')
                ->with('success', __('Thank you for your registration!'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Redirect::route('site.member')
                ->withErrors($e->validator)
                ->withInput();
        }
    }


    public function visitor(): View
    {
        $visitor = new Visitor();
        $visitor->created_by = session('visitor_created_by');
        $visitor->invited_by = session('invited_by');
        $visitor->phone_number = session('phone_number');
        $visitor->city = session('city');

        $genderOptions = Gender::options();
        $invitedByOptions = InvitedBy::options();
        $invitedByDefault = InvitedBy::WHO;

        $visitorGroupOptions = VisitorGroup::options();
        $invitedByOther = session('invited_by_other');

        return view('site.visitor', compact(
            'visitor',
            'genderOptions',
            'invitedByOther',
            'invitedByOptions',
            'invitedByDefault',
            'visitorGroupOptions'
        ));
    }

    public function registerVisitor(VisitorRequest $request): RedirectResponse
    {
        try {
            $request->validated();
            $visitor = new Visitor();
            $visitor->name = NameHelper::normalizeName($request->name);
            $visitor->phone_number = $request->phone_number;
            $visitor->gender = $request->gender;
            $visitor->city = NameHelper::normalizeName($request->city);
            $visitor->group = $request->group;
            $visitor->invited_by = $request->invited_by_other !== InvitedBy::WHO ? $request->invited_by_other : NameHelper::normalizeName($request->invited_by);
            $visitor->created_by = NameHelper::normalizeName($request->created_by);
            $visitor->status = VisitorStatus::ACTIVED;

            Session::put('invited_by_other', $request->invited_by_other);
            Session::put('invited_by', NameHelper::normalizeName($request->invited_by));
            Session::put('phone_number', $visitor->phone_number);
            Session::put('city', $visitor->city);
            Session::put('visitor_created_by', $visitor->created_by);

            $visitor->save();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Redirect::route('site.visitor')
                ->withErrors($e->validator)
                ->withInput();
        }

        return Redirect::route('site.visitor')
            ->with('success', __('Thank you for your registration!'))
            ->with('created_by', NameHelper::normalizeName($request->created_by));
    }

    public function todayVisitor()
    {
        $all = Visitor::actives()->where('created_at', 'like', '%' . date('Y-m-d') . '%')->get();
        $invitedByOptions = InvitedBy::options();

        $socialMedia = __('Social Media');
        $visitors = [];
        foreach ($all as $visitor) {
            $visitor->invited_by = isset($invitedByOptions[$visitor->invited_by]) ? $socialMedia : $visitor->invited_by;

            if (!isset($visitors[$visitor->invited_by])) {
                $visitors[$visitor->invited_by] = $visitor->name;
            } else {
                $visitors[$visitor->invited_by] .= ' - ' . $visitor->name;
            }
        }
        return view('site.todayvisitor', compact('visitors', 'invitedByOptions', 'socialMedia'));
    }

    public function checkDocumentNumber($document_number)
    {
        $member = Member::where('document_number', $document_number)->first();
        if ($member) {
            return response()->json(['exists' => true]);
        }
        return response()->json(['exists' => false]);
    }
}
