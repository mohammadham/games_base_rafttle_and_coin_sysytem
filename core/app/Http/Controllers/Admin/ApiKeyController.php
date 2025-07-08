<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ApiKey;
use App\Models\User; // For associating API keys with users
use App\Constants\Status;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ApiKeyController extends Controller
{
    // Helper function to get available permissions
    private function getAvailablePermissions(): array
    {
        return [
            'coin_credit' => trans('Allow Crediting Coins'),
            'coin_debit' => trans('Allow Debiting Coins'),
            'coin_balance_read' => trans('Allow Reading Coin Balances'),
            // 'user_info_read' => trans('Allow Reading Basic User Info'),
        ];
    }

    public function index(Request $request)
    {
        $pageTitle = trans('Manage API Keys');
        $query = ApiKey::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('api_key', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('username', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }
        if ($request->filled('status') && in_array((int)$request->status, [Status::ENABLE, Status::DISABLE])) {
            $query->where('status', (int)$request->status);
        }

        $apiKeys = $query->paginate(getPaginate());
        // View path: admin.api_key.index - This view needs to be created.
        return view('admin.api_key.index', compact('pageTitle', 'apiKeys'));
    }

    public function create()
    {
        $pageTitle = trans('Create New API Key');
        $apiKey = new ApiKey();
        $apiKey->status = Status::ENABLE; // Default to enabled
        $users = User::where('status', Status::USER_ACTIVE)->orderBy('username')->get(['id', 'username', 'email']);
        $availablePermissions = $this->getAvailablePermissions();
        // View path: admin.api_key.form - This view needs to be created.
        return view('admin.api_key.form', compact('pageTitle', 'apiKey', 'users', 'availablePermissions'));
    }

    public function store(Request $request)
    {
        $availablePermissionKeys = array_keys($this->getAvailablePermissions());
        $request->validate([
            'name' => 'required|string|max:191',
            'user_id' => 'nullable|exists:users,id',
            'status' => 'required|in:' . Status::ENABLE . ',' . Status::DISABLE,
            'allowed_ips' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if (!empty($value)) {
                    $ips = array_map('trim', explode(',', $value));
                    foreach ($ips as $ip) {
                        if (!empty($ip) && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
                            $fail(trans("The {$attribute} contains an invalid IP address: {$ip}"));
                            return;
                        }
                    }
                }
            }],
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in($availablePermissionKeys)],
            'expires_at' => 'nullable|date|after:now',
        ]);

        $newApiKeyString = ApiKey::generateApiKey();
        $newSecretKeyString = ApiKey::generateSecretKey();

        $apiKey = new ApiKey();
        $apiKey->name = $request->name;
        $apiKey->user_id = $request->user_id;
        $apiKey->api_key = $newApiKeyString;
        $apiKey->secret_key = $newSecretKeyString;
        $apiKey->status = $request->status;
        $apiKey->allowed_ips = $request->allowed_ips;
        $apiKey->permissions = $request->permissions ?? [];
        $apiKey->expires_at = $request->expires_at;
        $apiKey->save();

        $notify[] = ['success', trans('API Key created successfully. The Secret Key is shown below and will not be displayed again. Please copy and store it securely.')];

        return redirect()->route('admin.api.key.edit', $apiKey->id)
                         ->withNotify($notify)
                         ->with('new_api_key_generated_details', [
                             'name' => $apiKey->name,
                             'api_key' => $newApiKeyString,
                             'secret_key' => $newSecretKeyString,
                         ]);
    }

    public function edit($id)
    {
        $pageTitle = trans('Edit API Key');
        $apiKey = ApiKey::with('user')->findOrFail($id);
        $users = User::where('status', Status::USER_ACTIVE)->orderBy('username')->get(['id', 'username', 'email']);
        $availablePermissions = $this->getAvailablePermissions();
        return view('admin.api_key.form', compact('pageTitle', 'apiKey', 'users', 'availablePermissions'));
    }

    public function update(Request $request, $id)
    {
        $apiKey = ApiKey::findOrFail($id);
        $availablePermissionKeys = array_keys($this->getAvailablePermissions());

        $request->validate([
            'name' => 'required|string|max:191',
            'user_id' => 'nullable|exists:users,id',
            'status' => 'required|in:' . Status::ENABLE . ',' . Status::DISABLE,
            'allowed_ips' => ['nullable', 'string', function ($attribute, $value, $fail) {
                 if (!empty($value)) {
                    $ips = array_map('trim', explode(',', $value));
                    foreach ($ips as $ip) {
                        if (!empty($ip) && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
                            $fail(trans("The {$attribute} contains an invalid IP address: {$ip}"));
                            return;
                        }
                    }
                }
            }],
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in($availablePermissionKeys)],
            'expires_at' => 'nullable|date|after_or_equal:today',
        ]);

        $apiKey->name = $request->name;
        $apiKey->user_id = $request->user_id;
        $apiKey->status = $request->status;
        $apiKey->allowed_ips = $request->allowed_ips;
        $apiKey->permissions = $request->permissions ?? [];
        $apiKey->expires_at = $request->expires_at;

        $apiKey->save();

        $notify[] = ['success', trans('API Key updated successfully.')];
        return redirect()->route('admin.api.key.index')->withNotify($notify);
    }

    public function toggleStatus($id)
    {
        $apiKey = ApiKey::findOrFail($id);
        $apiKey->status = ($apiKey->status == Status::ENABLE) ? Status::DISABLE : Status::ENABLE;
        $apiKey->save();
        $notify[] = ['success', trans('API Key status updated.')];
        return back()->withNotify($notify);
    }

    public function revoke($id)
    {
        $apiKey = ApiKey::findOrFail($id);

        if ($apiKey->status == Status::DISABLE && Str::contains($apiKey->name, '(Revoked')) {
            $notify[] = ['info', trans('This API Key has already been revoked.')];
        } else {
            $apiKey->status = Status::DISABLE;
            if (!Str::contains($apiKey->name, '(Revoked')) {
                 $apiKey->name = $apiKey->name . ' (Revoked ' . now()->toUserTimezone()->toDateTimeString() . ')';
            }
            // Invalidate the actual key values to prevent any further use even if status was somehow re-enabled.
            $apiKey->api_key = $apiKey->api_key . '_revoked_' . time();
            $apiKey->secret_key = ApiKey::generateSecretKey(); // Generate a new dummy secret
            $apiKey->save();
            $notify[] = ['warning', trans('API Key has been revoked and can no longer be used.')];
        }
        return back()->withNotify($notify);
    }

    public function destroy($id)
    {
        $apiKey = ApiKey::findOrFail($id);
        $apiKeyName = $apiKey->name;

        // Before deleting, ensure related logs are handled (e.g., FK set to null if not cascading)
        // This depends on your database schema for api_transaction_logs.
        // If api_key_id in api_transaction_logs is onDelete('cascade'), then logs will be deleted.
        // If onDelete('set null'), then they will be updated.
        // If no constraint, you might want to manually update or delete them:
        // \App\Models\ApiTransactionLog::where('api_key_id', $id)->delete(); // or ->update(['api_key_id' => null]);

        $apiKey->delete();

        $notify[] = ['success', trans("API Key ':name' permanently deleted.", ['name' => $apiKeyName])];
        return back()->withNotify($notify);
    }

    public function regenerateSecret(Request $request, $id)
    {
        $apiKey = ApiKey::findOrFail($id);
        $newSecretKey = ApiKey::generateSecretKey();
        $apiKey->secret_key = $newSecretKey;
        $apiKey->save();

        $notify[] = ['success', trans('Secret Key has been regenerated. Please copy the new secret key immediately as it will not be shown again.')];
        return redirect()->route('admin.api.key.edit', $apiKey->id)
                         ->withNotify($notify)
                         ->with('new_api_key_generated_details', [
                             'name' => $apiKey->name,
                             'api_key' => $apiKey->api_key,
                             'secret_key' => $newSecretKey,
                         ]);
    }
}
