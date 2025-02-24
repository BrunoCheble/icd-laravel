<?php
namespace App\Http\Controllers;

use App\Http\Requests\FinancialCategoryRequest;
use App\Models\FinancialCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;

class FinancialCategoryController extends Controller
{
    public function index(): View
    {
        $categories = FinancialCategory::paginate();

        return view('financial-categories.index', compact('categories'));
    }

    public function create(): View
    {
        $category = new FinancialCategory();
        return view('financial-categories.create', compact('category'));
    }

    public function store(FinancialCategoryRequest $request): RedirectResponse
    {
        try {
            FinancialCategory::create($request->validated());
        } catch (\Exception $e) {
            return Redirect::route('financial-categories.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('financial-categories.index')
            ->with('success', 'Financial Category created successfully');
    }

    public function show($id): View
    {
        $category = FinancialCategory::find($id);
        return view('financial-categories.show', compact('category'));
    }

    public function edit($id): View
    {
        $category = FinancialCategory::findOrFail($id);
        return view('financial-categories.edit', compact('category'));
    }

    public function update(FinancialCategoryRequest $request, FinancialCategory $financial_category): RedirectResponse
    {
        try {
            $financial_category->update($request->validated());
        } catch (\Exception $e) {
            return Redirect::route('financial-categories.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('financial-categories.index')
            ->with('success', 'Financial Category updated successfully');
    }

    public function destroy($id): RedirectResponse
    {
        try {
            $category = FinancialCategory::findOrFail($id);
            // if ($category->financials()->count() > 0) {
            //     return Redirect::route('financial-categories.index')
            //         ->with('error', __('Financial Category can not be deleted'));
            // }

            $category->delete();

        } catch (\Exception $e) {
            return Redirect::route('financial-categories.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('financial-categories.index')->with('success', 'Financial Category deleted successfully.');
    }
}

