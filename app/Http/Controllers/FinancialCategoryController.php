<?php

namespace App\Http\Controllers;

use App\Models\FinancialCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Requests\FinancialCategoryRequest;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class FinancialCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $financialCategories = FinancialCategory::paginate();

        return view('financial-category.index', compact('financialCategories'))
            ->with('i', ($request->input('page', 1) - 1) * $financialCategories->perPage());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $financialCategory = new FinancialCategory();

        return view('financial-category.create', compact('financialCategory'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FinancialCategoryRequest $request): RedirectResponse
    {
        FinancialCategory::create($request->validated());

        return Redirect::route('financial-categories.index')
            ->with('success', 'FinancialCategory created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id): View
    {
        $financialCategory = FinancialCategory::find($id);

        return view('financial-category.show', compact('financialCategory'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id): View
    {
        $financialCategory = FinancialCategory::find($id);

        return view('financial-category.edit', compact('financialCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FinancialCategoryRequest $request, FinancialCategory $financialCategory): RedirectResponse
    {
        $financialCategory->update($request->validated());

        return Redirect::route('financial-categories.index')
            ->with('success', 'FinancialCategory updated successfully');
    }

    public function destroy($id): RedirectResponse
    {
        FinancialCategory::find($id)->delete();

        return Redirect::route('financial-categories.index')
            ->with('success', 'FinancialCategory deleted successfully');
    }
}
