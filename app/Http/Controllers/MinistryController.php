<?php

namespace App\Http\Controllers;

use App\Http\Requests\MinistryRequest;
use App\Models\Ministry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class MinistryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ministries = Ministry::all();
        return view('ministries.index', compact('ministries'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $ministry = new Ministry();

        return view('ministries.create', compact('ministry'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id): View
    {
        $ministry = Ministry::find($id);

        return view('ministries.edit', compact('ministry'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MinistryRequest $request)
    {
        try {
            Ministry::create($request->validated());
        } catch (\Exception $e) {
            return Redirect::route('ministries.index')
                ->with('error', __('Something went wrong'));
        }
        return Redirect::route('ministries.index')
            ->with('success', 'Ministry created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MinistryRequest $request, Ministry $ministry)
    {
        try {
            $ministry->update($request->validated());
        } catch (\Exception $e) {
            return Redirect::route('ministries.index')
                ->with('error', __('Something went wrong'));
        }
        return Redirect::route('ministries.index')
            ->with('success', 'Ministry updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ministry $ministry)
    {
        try {
            if ($ministry->members()->count() > 0) {
                return Redirect::route('ministries.index')
                    ->with('error', 'Ministry has members, cannot delete.');
            }
            $ministry->delete();
        } catch (\Exception $e) {
            return Redirect::route('ministries.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('ministries.index')
            ->with('success', 'Ministry deleted successfully.');
    }
}
