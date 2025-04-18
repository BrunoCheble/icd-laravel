<?php
namespace App\Http\Controllers;

use App\Enums\FinancialMovementOptions;
use App\Enums\FinancialMovementType;
use App\Helpers\ArrayHelper;
use App\Http\Requests\FinancialMovementRequest;
use App\Models\FinancialCategory;
use App\Models\FinancialMovement;
use App\Models\Member;
use App\Models\Ministry;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class FinancialMovementController extends Controller
{
    public function index(Request $request): View
    {
        $financials = FinancialMovement::paginate();

        $availableAttributes = FinancialMovementOptions::options();

        return view('financial-movements.index', compact('financials', 'availableAttributes'))
            ->with('i', ($request->input('page', 1) - 1) * $financials->perPage());
    }

    public function create(): View
    {
        $financialMovement = new FinancialMovement();
        $types = FinancialMovementType::options();
        $categories = ArrayHelper::toKeyValueArray(FinancialCategory::all(), 'id', 'name');
        $wallets = ArrayHelper::toKeyValueArray(Wallet::all(), 'id', 'name');
        $ministries = ArrayHelper::toKeyValueArray(Ministry::all(), 'id', 'name');
        $members = ArrayHelper::toKeyValueArray(Member::all(), 'id', 'first_and_last_name_and_document');

        return view('financial-movements.create', compact('types','categories','wallets', 'ministries', 'financialMovement', 'members'));
    }

    public function store(FinancialMovementRequest $request): RedirectResponse
    {
        try {
            FinancialMovement::create($request->validated());
            return Redirect::route('financial-movements.index')
                ->with('success', 'Financial Movement created successfully');
        } catch (\Exception $e) {
            return Redirect::route('financial-movements.index')
                ->with('error', __('Something went wrong'));
        }
    }

    public function edit($id): View
    {
        $financialMovement = FinancialMovement::find($id);
        $types = FinancialMovementType::options();
        $categories = ArrayHelper::toKeyValueArray(FinancialCategory::all(), 'id', 'name');
        $wallets = ArrayHelper::toKeyValueArray(Wallet::all(), 'id', 'name');

        $ministries = ArrayHelper::toKeyValueArray(Ministry::all(), 'id', 'name');
        $members = ArrayHelper::toKeyValueArray(Member::all(), 'id', 'first_and_last_name_and_document');

        return view('financial-movements.edit', compact('types', 'categories', 'wallets', 'ministries', 'financialMovement', 'members'));
    }

    public function update(FinancialMovementRequest $request, FinancialMovement $financial_movement): RedirectResponse
    {
        try {
            $financial_movement->update($request->validated());
            return Redirect::route('financial-movements.index')
                ->with('success', 'Financial Movement updated successfully');
        } catch (\Exception $e) {
            return Redirect::route('financial-movements.index')
                ->with('error', __('Something went wrong'));
        }

    }

    public function destroy(FinancialMovement $financial_movement)
    {
        try {
            $financial_movement->delete();
            return Redirect::route('financial-movements.index')
                ->with('success', 'Financial Movement deleted successfully');
        } catch (\Exception $e) {
            return Redirect::route('financial-movements.index')
                ->with('error', __('Something went wrong'));
        }
    }
}
