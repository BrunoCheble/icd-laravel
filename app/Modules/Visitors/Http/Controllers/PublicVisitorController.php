<?php

namespace App\Modules\Visitors\Http\Controllers;

use App\Enums\Gender;
use App\Enums\InvitedBy;
use App\Enums\VisitorGroup;
use App\Http\Controllers\Controller;
use App\Modules\Visitors\Http\Requests\PublicVisitorRequest;
use App\Modules\Visitors\Models\Visitor;
use App\Modules\Visitors\Services\RegisterPublicVisitorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The reception's page to register the visitors of a service, one after the other.
 */
class PublicVisitorController extends Controller
{
    public function create(): View
    {
        // The reception speaks Portuguese.
        app()->setLocale('pt');

        return view('visitors::public.create', [
            'remembered' => [
                'created_by' => session('visitor_created_by'),
                'invited_by_other' => session('invited_by_other', InvitedBy::WHO),
                'invited_by' => session('invited_by'),
                'phone_number' => session('phone_number'),
                'city' => session('city'),
            ],
            'genders' => Gender::options(),
            'groups' => VisitorGroup::options(),
            'invitedBy' => InvitedBy::options(),
            'today' => Visitor::query()->actives()->whereDate('created_at', today())->count(),
        ]);
    }

    public function store(PublicVisitorRequest $request, RegisterPublicVisitorService $service): RedirectResponse
    {
        $visitor = $service->execute($request->validated());

        return redirect()->route('site.visitor')->with('registered', $visitor->name);
    }

    public function newFamily(RegisterPublicVisitorService $service): RedirectResponse
    {
        $service->forgetFamily();

        return redirect()->route('site.visitor');
    }
}
