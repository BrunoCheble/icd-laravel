<?php

namespace App\Modules\Prayers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Prayers\Http\Requests\PublicPrayerRequest;
use App\Modules\Prayers\Services\RegisterPrayerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The public page where anyone leaves a prayer request (name, request, mobile number). In Portuguese, or in English
 * with "lang=en".
 */
class PublicPrayerController extends Controller
{
    public const LOCALES = ['pt', 'en'];

    public static function locale(Request $request): string
    {
        return in_array($request->input('lang'), self::LOCALES, true) ? $request->input('lang') : 'pt';
    }

    public function create(Request $request): View
    {
        app()->setLocale(self::locale($request));
        return view('prayers::public.create');
    }

    public function store(PublicPrayerRequest $request, RegisterPrayerService $service): RedirectResponse
    {
        $service->execute($request->validated());

        $locale = self::locale($request);
        return redirect()->route('site.prayer', $locale === 'pt' ? [] : ['lang' => $locale])->with('sent', true);
    }
}
