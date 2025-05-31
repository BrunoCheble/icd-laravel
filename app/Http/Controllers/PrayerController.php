<?php

namespace App\Http\Controllers;

use App\Models\Prayer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Exception;

class PrayerController extends Controller
{
    public function index(): View
    {
        $prayers = Prayer::paginate();
        return view('prayers.index', compact('prayers'));
    }

    public function destroy($id): RedirectResponse
    {
        try {
            $prayer = Prayer::findOrFail($id);
            $prayer->delete();

            return redirect()->route('prayers.index')->with('success', 'Prayer deleted successfully.');
        } catch (Exception $e) {
            return redirect()->route('prayers.index')->with('error', 'Failed to delete prayer.');
        }
    }
}
