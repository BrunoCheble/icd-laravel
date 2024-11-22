<?php

namespace App\Http\Controllers;

use App\Models\Financial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Requests\FinancialRequest;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class FinancialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $financials = Financial::paginate();

        return view('financial.index', compact('financials'))
            ->with('i', ($request->input('page', 1) - 1) * $financials->perPage());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $financial = new Financial();

        return view('financial.create', compact('financial'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FinancialRequest $request): RedirectResponse
    {
        Financial::create($request->validated());

        return Redirect::route('financials.index')
            ->with('success', 'Financial created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id): View
    {
        $financial = Financial::find($id);

        return view('financial.show', compact('financial'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id): View
    {
        $financial = Financial::find($id);

        return view('financial.edit', compact('financial'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FinancialRequest $request, Financial $financial): RedirectResponse
    {
        $financial->update($request->validated());

        return Redirect::route('financials.index')
            ->with('success', 'Financial updated successfully');
    }

    public function destroy($id): RedirectResponse
    {
        Financial::find($id)->delete();

        return Redirect::route('financials.index')
            ->with('success', 'Financial deleted successfully');
    }
}
