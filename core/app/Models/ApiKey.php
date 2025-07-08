<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Constants\Status; // For casting status and default value
use Illuminate\Support\Str; // For generating keys

class ApiKey extends Model
{
    use HasFactory;

    protected $table = 'api_keys'; // Ensure this matches the migration table name

    protected $fillable = [
        'user_id',
        // 'game_id', // Uncomment if you add this field in the migration
        'name',
        'api_key',
        'secret_key', // See security note below
        'status',
        'allowed_ips',
        'permissions',
        'usage_count',
        'last_used_at',
        'expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * Typically, you'd want to hide the secret_key from direct API responses.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'secret_key',
    ];

    protected $casts = [
        'status' => 'integer', // Status::ENABLE is 1, Status::DISABLE is 0
        // 'allowed_ips' will be handled by accessor/mutator for comma-separated string
        'permissions' => 'array', // Stored as JSON in DB
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        // Ensure the User model exists at App\Models\User
        return $this->belongsTo(User::class, 'user_id');
    }

    // public function game()
    // {
    //     // Ensure a Game model exists if you use game_id
    //     return $this->belongsTo(Game::class);
    // }

    // Accessor for status badge (consistent with ViserGo's style)
    public function getStatusBadgeAttribute()
    {
        $html = '';
        if ($this->status == Status::ENABLE) {
            $html = '<span class="badge badge--success">' . trans('Active') . '</span>';
        } else {
            $html = '<span class="badge badge--warning">' . trans('Inactive') . '</span>'; // Using warning for inactive
        }
        return $html;
    }

    // Mutator for allowed_ips to handle comma-separated string storage
    public function setAllowedIpsAttribute($value)
    {
        if (is_array($value)) {
            // Filter out empty values and trim whitespace before imploding
            $this->attributes['allowed_ips'] = implode(',', array_filter(array_map('trim', $value)));
        } elseif (is_string($value)) {
            // If it's already a string, assume it's correctly formatted or clear it
            $this->attributes['allowed_ips'] = trim($value) === '' ? null : trim($value);
        } else {
            $this->attributes['allowed_ips'] = null;
        }
    }

    // Accessor for allowed_ips to return an array
    public function getAllowedIpsAttribute($value)
    {
        if (is_string($value) && !empty($value)) {
            return array_filter(array_map('trim', explode(',', $value)));
        }
        return []; // Return empty array if null or empty string
    }

    /**
     * Generate a unique API key.
     * Uses a more robust method for uniqueness.
     * @return string
     */
    public static function generateApiKey(): string
    {
        do {
            // Generate a candidate key (e.g., 32 bytes hex encoded = 64 chars)
            $key = bin2hex(random_bytes(32));
        } while (self::where('api_key', $key)->exists()); // Check for collision
        return $key;
    }

    /**
     * Generate a unique Secret key.
     * Note: This key should be shown to the user only once and ideally not stored raw,
     * or if stored, it must be encrypted at rest. Hashing is an option if the raw
     * secret is not needed by the server for signature verification (e.g. if client signs).
     * If server needs to verify signature using the secret, store it encrypted.
     *
     * @return string
     */
    public static function generateSecretKey(): string
    {
         // Generate a candidate key (e.g., 64 bytes hex encoded = 128 chars for strong secret)
        return bin2hex(random_bytes(64));
    }

    /**
     * Scope a query to only include active API keys.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', Status::ENABLE)
                     ->where(function ($q) {
                         $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                     });
    }
}
