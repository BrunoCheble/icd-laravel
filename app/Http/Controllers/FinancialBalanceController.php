<?php

namespace App\Http\Controllers;

use App\Models\FinancialBalance;
use App\Http\Requests\FinancialBalanceRequest;
use Illuminate\Contracts\View\View;

class FinancialBalanceController extends Controller
{
    public function index()
    {
        $balances = FinancialBalance::all();
        return view('financial-balances.index', compact('balances'));
    }

    public function create(): View
    {
        return view('financial-balances.create');
    }

    public function store(FinancialBalanceRequest $request)
    {
        FinancialBalance::create($request->validated());
        return redirect()->route('financial-balances.index')->with('success', 'Financial Balance created successfully.');
    }

    public function show(FinancialBalance $financialBalance): View
    {
        return view('financial-balances.show', compact('financialBalance'));
    }

    public function edit(FinancialBalance $financialBalance): View
    {
        return view('financial-balances.edit', compact('financialBalance'));
    }

    public function update(FinancialBalanceRequest $request, FinancialBalance $financialBalance)
    {
        $financialBalance->update($request->validated());
        return redirect()->route('financial-balances.index')->with('success', 'Financial Balance updated successfully.');
    }

    public function destroy(FinancialBalance $financialBalance)
    {
        $financialBalance->delete();
        return redirect()->route('financial-balances.index')->with('success', 'Financial Balance deleted successfully.');
    }
}
