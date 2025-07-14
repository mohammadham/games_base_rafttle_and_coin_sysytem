<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiTransactionLog;
use Illuminate\Http\Request;

class ApiTransactionLogController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'API Transaction Logs';
        $query = ApiTransactionLog::with(['apiKey.user'])->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('endpoint_url', 'like', "%{$search}%")
                    ->orWhere('request_payload', 'like', "%{$search}%")
                    ->orWhere('response_payload', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('apiKey', function ($apiKeyQuery) use ($search) {
                        $apiKeyQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query->paginate(getPaginate());
        return view('admin.api.transaction.index', compact('pageTitle', 'logs'));
    }
}
