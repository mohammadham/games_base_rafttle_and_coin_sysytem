<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinTransaction extends Model
{
    use HasFactory;

    protected $table = 'coin_transactions';

    /**
     * Indicates if the model should be timestamped.
     * For transaction logs, often only `created_at` is needed.
     *
     * @var bool
     */
    public $timestamps = false; // We only use created_at (defined in migration as useCurrent)

    protected $fillable = [
        'user_id',
        'coin_type_id',
        'related_transactionable_id',
        'related_transactionable_type',
        'game_api_key_id',
        'amount',
        'post_balance',
        'charge',
        'trx_type', // '+' or '-'
        'remark',
        'details', // Can be JSON or text
        'trx',     // Unique transaction identifier for this log entry
        'created_by_admin_id',
        'created_at', // Allow mass assignment if you set it manually sometimes
    ];

    protected $casts = [
        'amount' => 'decimal:8',         // Match precision from migration
        'post_balance' => 'decimal:8',   // Match precision from migration
        'charge' => 'decimal:8',         // Match precision from migration
        'details' => 'array',            // If storing JSON in 'details'
        'created_at' => 'datetime',      // Ensure it's treated as a Carbon instance
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = $model->freshTimestamp();
            }
            if (empty($model->trx)) {
                $model->trx = getTrx(); // Ensure getTrx() is available and generates a unique ID
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function coinType()
    {
        return $this->belongsTo(CoinType::class, 'coin_type_id');
    }

    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class, 'game_api_key_id');
    }

    public function createdByAdmin()
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    /**
     * Get the parent model of the related transaction (polymorphic relationship).
     * Examples: an Order, a Deposit, an ApiTransactionLog, etc.
     * This allows a coin transaction to be linked to the specific event that caused it.
     */
    public function relatedTransactionable()
    {
        return $this->morphTo();
    }
}
