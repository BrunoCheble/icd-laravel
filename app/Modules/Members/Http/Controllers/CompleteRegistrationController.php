<?php

namespace App\Modules\Members\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Members\Http\Requests\CompleteRegistrationRequest;
use App\Modules\Members\Models\Member;
use App\Modules\Members\Services\CompleteRegistrationService;
use App\Modules\Members\Services\GetMemberPendingInformationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The public page a pending member opens from the link sent by WhatsApp, to fill in what is missing. The link is
 * signed and expires (GetRegistrationLinkService); without a valid one the page only says so. In Portuguese, or in
 * English with "lang=en" (outside the signature).
 */
class CompleteRegistrationController extends Controller
{
    // The member is looked up only after the signature is checked: a forged link learns nothing, not even whether
    // the member exists.
    public function show(Request $request, string $member, GetMemberPendingInformationService $pending): View
    {
        app()->setLocale(CompleteRegistrationRequest::locale($request));
        $member = $request->hasValidSignatureWhileIgnoring(['lang']) ? Member::find($member) : null;

        return view('members::complete.index', ['member' => $member, 'fields' => $member ? $pending->fields($member) : []]);
    }

    // The "signed" middleware already refused a forged or expired link.
    public function update(CompleteRegistrationRequest $request, CompleteRegistrationService $service): RedirectResponse
    {
        $service->execute($request->member(), $request->validated());

        $locale = CompleteRegistrationRequest::locale($request);
        return redirect()->to($locale === 'pt' ? $request->fullUrl() : $request->fullUrlWithQuery(['lang' => $locale]))->with('success', true);
    }
}
