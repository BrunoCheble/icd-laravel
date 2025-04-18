<?php

namespace App\Http\Controllers;

use App\Http\Requests\WalletRequest;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class WalletController extends Controller
{
    public function index(): View
    {
        $wallets = Wallet::paginate();
        return view('wallets.index', compact('wallets'));
    }

    public function create(): View
    {
        $wallet = new Wallet();
        return view('wallets.create', compact('wallet'));
    }

    public function store(WalletRequest $request): RedirectResponse
    {
        try {
            Wallet::create($request->validated());
            return redirect()->route('wallets.index')->with('success', 'Wallet created successfully.');
        } catch (Exception $e) {
            return redirect()->route('wallets.index')->with('error', 'Failed to create wallet.');
        }
    }

    public function show($id): View
    {
        $wallet = Wallet::findOrFail($id);
        return view('wallets.show', compact('wallet', 'wallet'));
    }

    public function edit($id): View
    {
        $wallet = Wallet::findOrFail($id);
        return view('wallets.edit', compact('wallet'));
    }

    public function update(WalletRequest $request, Wallet $wallet): RedirectResponse
    {
        try {
            $wallet->update($request->validated());
            return redirect()->route('wallets.index')->with('success', 'Wallet updated successfully.');
        } catch (Exception $e) {
            return redirect()->route('wallets.index')->with('error', 'Failed to update wallet.');
        }
    }

    public function destroy($id): RedirectResponse
    {
        try {
            $wallet = Wallet::findOrFail($id);
            $wallet->delete();

            return redirect()->route('wallets.index')->with('success', 'Wallet deleted successfully.');
        } catch (Exception $e) {
            return redirect()->route('wallets.index')->with('error', 'Failed to delete wallet.');
        }
    }
}
