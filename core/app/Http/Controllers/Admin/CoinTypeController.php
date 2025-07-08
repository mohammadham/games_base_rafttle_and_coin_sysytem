<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CoinType;
use App\Constants\Status;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB; // Added for DB transaction in saveCoinTypeData


class CoinTypeController extends Controller
{
    public function index()
    {
        $pageTitle = trans('Manage Coin Types');
        $coinTypes = CoinType::orderBy('is_base_coin', 'desc')->orderBy('name')->paginate(getPaginate());
        // Ensure the view path is correct for your admin theme structure
        // Example: 'admin.coin_types.index' if you create a 'coin_types' folder in admin views
        return view('admin.coin_type.index', compact('pageTitle', 'coinTypes'));
    }

    public function create()
    {
        $pageTitle = trans('Create New Coin Type');
        $coinType = new CoinType();
        $coinType->status = Status::ENABLE; // Default to enabled
        return view('admin.coin_type.form', compact('pageTitle', 'coinType'));
    }

    public function store(Request $request)
    {
        $isBaseCoinRequest = $request->boolean('is_base_coin');

        $rules = [
            'name' => 'required|string|max:100',
            'code' => 'required|string|alpha_dash|max:50|unique:coin_types,code',
            'symbol' => 'nullable|string|max:20',
            'is_base_coin' => 'sometimes|boolean',
            'base_coin_value_multiplier' => [
                Rule::requiredIf(!$isBaseCoinRequest),
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) use ($isBaseCoinRequest) {
                    if (!$isBaseCoinRequest && (float)$value <= 0) {
                        $fail(trans('The :attribute must be greater than 0 for non-base coins.'));
                    }
                },
            ],
            'status' => 'required|in:' . Status::ENABLE . ',' . Status::DISABLE,
            'description' => 'nullable|string',
            'meta.color' => 'nullable|string|max:7|regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/',
            'meta.icon_class' => 'nullable|string|max:50',
            'meta.precision' => 'nullable|integer|min:0|max:18',
        ];

        $request->validate($rules, [
            'base_coin_value_multiplier.required_if' => trans('The base coin value multiplier is required when this is not a base coin.')
        ]);

        if ($isBaseCoinRequest && $request->status == Status::ENABLE) {
            $activeBaseExists = CoinType::where('is_base_coin', true)
                                       ->where('status', Status::ENABLE)
                                       ->exists();
            if ($activeBaseExists) {
                $notify[] = ['error', trans('An active base coin already exists. You must deactivate or demote the current base coin first.')];
                return back()->withNotify($notify)->withInput();
            }
        }

        $coinType = new CoinType();
        try {
            $this->saveCoinTypeData($coinType, $request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // This can come from model events if they throw ValidationException
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify)->withInput();
        } catch (\Exception $e) {
            $notify[] = ['error', trans('Could not save coin type: ') . $e->getMessage()];
            return back()->withNotify($notify)->withInput();
        }


        $notify[] = ['success', trans('Coin type created successfully.')];
        return redirect()->route('admin.coin.type.index')->withNotify($notify);
    }

    public function edit($id)
    {
        $pageTitle = trans('Edit Coin Type');
        $coinType = CoinType::findOrFail($id);
        return view('admin.coin_type.form', compact('pageTitle', 'coinType'));
    }

    public function update(Request $request, $id)
    {
        $coinType = CoinType::findOrFail($id);
        $isBaseCoinRequest = $request->boolean('is_base_coin');

        $rules = [
            'name' => 'required|string|max:100',
            'code' => 'required|string|alpha_dash|max:50|unique:coin_types,code,' . $coinType->id,
            'symbol' => 'nullable|string|max:20',
            'is_base_coin' => 'sometimes|boolean',
            'base_coin_value_multiplier' => [
                Rule::requiredIf(!$isBaseCoinRequest),
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) use ($isBaseCoinRequest) {
                    if (!$isBaseCoinRequest && (float)$value <= 0) {
                        $fail(trans('The :attribute must be greater than 0 for non-base coins.'));
                    }
                },
            ],
            'status' => 'required|in:' . Status::ENABLE . ',' . Status::DISABLE,
            'description' => 'nullable|string',
            'meta.color' => 'nullable|string|max:7|regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/',
            'meta.icon_class' => 'nullable|string|max:50',
            'meta.precision' => 'nullable|integer|min:0|max:18',
        ];

        $request->validate($rules,[
            'base_coin_value_multiplier.required_if' => trans('The base coin value multiplier is required when this is not a base coin.')
        ]);

        // Logic to ensure only one active base coin
        if ($isBaseCoinRequest && $request->status == Status::ENABLE) {
            $activeBaseExists = CoinType::where('id', '!=', $coinType->id)
                                       ->where('is_base_coin', true)
                                       ->where('status', Status::ENABLE)
                                       ->exists();
            if ($activeBaseExists) {
                $notify[] = ['error', trans('Another active base coin already exists. You must deactivate or demote it first.')];
                return back()->withNotify($notify)->withInput();
            }
        }

        // Logic to prevent demoting/deactivating the last active base coin
        if ($coinType->is_base_coin && (!$isBaseCoinRequest || $request->status == Status::DISABLE)) {
            $otherActiveBaseExists = CoinType::where('id', '!=', $coinType->id)
                                            ->where('is_base_coin', true)
                                            ->where('status', Status::ENABLE)
                                            ->exists();
            if (!$otherActiveBaseExists && CoinType::active()->baseCoin()->where('id', $coinType->id)->count() == 1 && CoinType::active()->baseCoin()->count() == 1) {
                 $notify[] = ['error', trans('You must ensure another coin type is the active base coin before demoting or deactivating this one.')];
                 return back()->withNotify($notify)->withInput();
            }
        }
        try {
            $this->saveCoinTypeData($coinType, $request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify)->withInput();
        } catch (\Exception $e) {
            $notify[] = ['error', trans('Could not update coin type: ') . $e->getMessage()];
            return back()->withNotify($notify)->withInput();
        }

        $notify[] = ['success', trans('Coin type updated successfully.')];
        return redirect()->route('admin.coin.type.index')->withNotify($notify);
    }

    private function saveCoinTypeData(CoinType $coinType, Request $request)
    {
        $isBaseCoin = $request->boolean('is_base_coin');

        $coinType->name = $request->name;
        $coinType->code = strtoupper(str_replace(' ', '_', trim($request->code))); // Sanitize code
        $coinType->symbol = $request->symbol;

        DB::transaction(function () use ($coinType, $isBaseCoin, $request) {
            // If this coin is being set as the (active) base coin,
            // ensure no other coin remains the active base coin.
            if ($isBaseCoin && $request->status == Status::ENABLE) {
                CoinType::where('is_base_coin', true)
                        // ->where('id', '!=', $coinType->id) // Not needed if we update all to false then set current
                        ->update(['is_base_coin' => false]);
            }

            $coinType->is_base_coin = $isBaseCoin;
            $coinType->base_coin_value_multiplier = $isBaseCoin ? 1.0 : (float)$request->base_coin_value_multiplier;
            $coinType->status = $request->status;
            $coinType->description = $request->description;
            $coinType->meta = $request->meta ?? ($coinType->meta ?? []);

            if (!$coinType->exists) { // Only set on creation
                $coinType->created_by_admin_id = auth('admin')->id();
            }

            $coinType->saveOrFail(); // saveOrFail will throw an exception on failure
        });
    }

    public function toggleStatus($id)
    {
        $coinType = CoinType::findOrFail($id);
        $newStatus = ($coinType->status == Status::ENABLE) ? Status::DISABLE : Status::ENABLE;

        // If deactivating the current (and potentially only) active base coin
        if ($coinType->is_base_coin && $coinType->status == Status::ENABLE && $newStatus == Status::DISABLE) {
            $activeBaseCoinCount = CoinType::active()->baseCoin()->count();
            if ($activeBaseCoinCount == 1) { // This is the only active base coin
                $notify[] = ['error', trans('Cannot deactivate the only active base coin. Please set another coin as active base first.')];
                return back()->withNotify($notify);
            }
        }

        // If activating a coin as base, ensure no other is active base
        if ($coinType->is_base_coin && $newStatus == Status::ENABLE) {
             $otherActiveBaseExists = CoinType::where('id', '!=', $coinType->id)
                                       ->where('is_base_coin', true)
                                       ->where('status', Status::ENABLE)
                                       ->exists();
            if ($otherActiveBaseExists) {
                $notify[] = ['error', trans('Another active base coin already exists. You must deactivate or demote it first before activating this one as base.')];
                return back()->withNotify($notify);
            }
        }

        $coinType->status = $newStatus;
        $coinType->save();

        $notify[] = ['success', trans('Coin type status updated successfully.')];
        return back()->withNotify($notify);
    }

    public function destroy($id)
    {
        try {
            $coinType = CoinType::findOrFail($id);
            $coinTypeName = $coinType->name;
            // The model's 'deleting' event should handle validation (e.g., cannot delete base coin, cannot delete if balances exist)
            $coinType->delete();
            $notify[] = ['success', trans("Coin type ':name' deleted successfully.",['name' => $coinTypeName])];
        } catch (\Illuminate\Validation\ValidationException $e) {
            // This catches exceptions thrown by the model's 'deleting' event (if they are ValidationException)
            $errors = $e->validator->errors()->all();
            $notify[] = ['error', implode(' ', $errors)];
        } catch (\Exception $e) { // Catch other general exceptions
            $notify[] = ['error', trans('Could not delete coin type. ') . $e->getMessage()];
        }
        return back()->withNotify($notify);
    }
}
