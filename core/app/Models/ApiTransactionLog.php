<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiTransactionLog extends Model
{
    use HasFactory;

    protected $table = 'api_transaction_logs';

    // Logs are typically append-only, so we don't use updated_at.
    const UPDATED_AT = null;

    protected $fillable = [
        'api_key_id',
        'endpoint_url',
        'method',
        'request_payload',
        'request_headers',
        'response_payload',
        'response_http_code',
        'ip_address',
        'user_platform_id',
        'game_transaction_id',
        'platform_transaction_id',
        'coin_type',
        'amount',
        'action_type',
        'status', // 0 for fail, 1 for success
        'error_message',
        'error_code',
        'duration_ms',
        // 'created_at' is handled by default or set in migration to useCurrent()
    ];

    protected $casts = [
        'request_payload' => 'array', // Store as JSON, cast to array
        'request_headers' => 'array', // Store as JSON, cast to array
        'response_payload' => 'array',// Store as JSON, cast to array
        'status' => 'integer',
        'amount' => 'decimal:8', // Match precision with migration
        'duration_ms' => 'integer',
        'response_http_code' => 'integer',
    ];

    /**
     * Get the API key that owns this log.
     */
    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class, 'api_key_id');
    }

    /**
     * Get the user associated with this API transaction log, if applicable.
     * This assumes user_platform_id can be mapped back to a User model.
     * This relationship might be more complex or indirect.
     */
    // public function user()
    // {
    //     // Example: if user_platform_id is the primary key in users table
    //     // return $this->belongsTo(User::class, 'user_platform_id', 'platform_id_field_in_users_table');
    // }
}
