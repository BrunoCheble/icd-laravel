<?php

namespace App\Http\Controllers\Site;

use App\Enums\AnnouncementType;
use App\Enums\InvitedBy;
use App\Enums\MaritalStatus;
use App\Enums\MembershipStatus;
use App\Helpers\DateHelper;
use App\Helpers\NameHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\SiteAnnouncementRequest;
use App\Http\Requests\SiteMemberRequest;
use App\Modules\Announcements\Models\Announcement;
use App\Modules\Members\Models\Member;
use App\Modules\Prayers\Models\Prayer;
use App\Modules\Visitors\Models\Visitor;
use App\Modules\Announcements\Services\RegisterAnnouncementService;
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




    public function announcement(): View
    {
        $announcement = new Announcement();
        $announcementOptions = AnnouncementType::options();

        return view('site.announcement', compact(
            'announcement',
            'announcementOptions',
        ));
    }

    /**
     * Store a newly created announcement in storage.
     */
    public function registerAnnouncement(SiteAnnouncementRequest $request, RegisterAnnouncementService $service): RedirectResponse
    {
        try {
            $successMessages = [
                'resume' => [
                    'success-title' => __('announcement.success.resume.title'),
                    'success-message' => __('announcement.success.resume.message'),
                ],
                'service' => [
                    'success-title' => __('announcement.success.service.title'),
                    'success-message' => __('announcement.success.service.message'),
                ],
                'product' => [
                    'success-title' => __('announcement.success.product.title'),
                    'success-message' => __('announcement.success.product.message'),
                    'success-note' => __('announcement.success.product.note'),
                ],
                'donation' => [
                    'success-title' => __('announcement.success.donation.title'),
                    'success-message' => __('announcement.success.donation.message'),
                ],
            ];

            $announcement = $service->execute($request->validated(), $request->member());
            return Redirect::route('site.announcement')
                ->with('success', [
                    'title' => $successMessages[$announcement->type]['success-title'],
                    'message' => $successMessages[$announcement->type]['success-message'],
                    'note' => $successMessages[$announcement->type]['success-note'] ?? null,
                ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return Redirect::route('site.announcement')
                ->withErrors($e->validator)
                ->withInput();
        }
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



    public function todayPrayer(): View
    {
        $prayers = Prayer::where('created_at', 'like', '%' . date('Y-m-d') . '%')->get();
        return view('site.todayprayer', compact('prayers'));
    }
}
