<?php
namespace App\Http\Controllers\Site;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\MembershipStatus;
use App\Helpers\NameHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\SiteMemberRequest;
use App\Models\Member;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class SiteController extends Controller
{
    public function contact() {
        return view('site/contact');
    }

    public function member(): View {
        $member = new Member();
        return view('site.member', compact('member'));
    }

    public function register(SiteMemberRequest $request): RedirectResponse {

        $request->validated();

        $member = new Member();

        $full_name = NameHelper::splitFullName($request->full_name);

        $member->first_name = $full_name['first_name'];
        $member->middle_name = $full_name['middle_name'];
        $member->last_name = $full_name['last_name'];

        $member->email = strtolower($request->email);
        $member->phone_number = $request->phone_number;
        $member->document_number = $request->document_number;
        $member->date_of_birth = $request->birthdate;

        $member->zip_code = $request->zip_code;
        $member->city = NameHelper::normalizeName($request->city);
        $member->address = NameHelper::normalizeName($request->address).' '.$request->address_number;

        $member->gender = Gender::getIndexByValue($request->gender);
        $member->marital_status = MaritalStatus::getIndexByValue($request->marital_status);
        $member->membership_status = MembershipStatus::PENDING;

        $member->save();

        return Redirect::route('site.member')
            ->with('success', __('Thank you for your registration!'));
    }

    public function checkDocumentNumber($document_number) {
        $member = Member::where('document_number', $document_number)->first();
        if ($member) {
            return response()->json(['exists' => true]);
        }
        return response()->json(['exists' => false]);
    }
}
