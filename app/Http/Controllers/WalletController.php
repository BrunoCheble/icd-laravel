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
        try {
            $wallets = Wallet::paginate();
            return view('wallets.index', compact('wallets'));
        } catch (Exception $e) {
            return redirect()->route('wallets.index')->with('error', 'Failed to load wallets.');
        }
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
        try {
            $wallet = Wallet::findOrFail($id);
            return view('wallets.show', compact('wallet', 'wallet'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('wallets.index')->with('error', 'Wallet not found.');
        } catch (Exception $e) {
            return redirect()->route('wallets.index')->with('error', 'Failed to load wallet details.');
        }
    }

    public function edit($id): View
    {
        try {
            $wallet = Wallet::findOrFail($id);
            return view('wallets.edit', compact('wallet'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('wallets.index')->with('error', 'Wallet not found.');
        } catch (Exception $e) {
            return redirect()->route('wallets.index')->with('error', 'Failed to load wallet for editing.');
        }
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

            // if ($wallet->financialMovements()->count() > 0) {
            //     return redirect()->route('wallets.index')->with('error', 'Wallet has financial movements, cannot delete.');
            // }

            $wallet->delete();

            return redirect()->route('wallets.index')->with('success', 'Wallet deleted successfully.');
        } catch (Exception $e) {
            return redirect()->route('wallets.index')->with('error', 'Failed to delete wallet.');
        }
    }
}
