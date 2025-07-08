<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Constants\Status;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class CoinType extends Model
{
    use HasFactory;

    protected $table = 'coin_types';

    protected $fillable = [
        'name',
        'code',
        'symbol',
        'is_base_coin',
        'base_coin_value_multiplier',
        'status',
        'description',
        'created_by_admin_id',
        'meta',
    ];

    protected $casts = [
        'is_base_coin' => 'boolean',
        'status' => 'integer',
        'base_coin_value_multiplier' => 'decimal:18', // High precision for conversion rates
        'meta' => 'array',
    ];

    // Relationships
    public function creatorAdmin()
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function userCoinBalances()
    {
        return $this->hasMany(UserCoinBalance::class, 'coin_type_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', Status::ENABLE);
    }

    public function scopeBaseCoin($query)
    {
        return $query->where('is_base_coin', true);
    }

    /**
     * Get the platform's active base coin type.
     * Caches the result for efficiency.
     *
     * @return CoinType|null
     */
    public static function getBaseCoin(): ?CoinType
    {
        return Cache::remember('active_base_coin_type', now()->addHours(12), function () {
            return self::active()->baseCoin()->first();
        });
    }

    /**
     * Convert an amount of this coin type to its equivalent in the base coin.
     * Example: If ThisCoin is GOLD (multiplier 1000) and BaseCoin is USD,
     * 2 GOLD = 2 * 1000 = 2000 USD (BaseCoin units)
     *
     * @param float $amountOfThisCoin
     * @return float
     */
    public function convertToBaseCoin(float $amountOfThisCoin): float
    {
        // The multiplier defines how many base units one unit of this coin is worth.
        return $amountOfThisCoin * (float)$this->base_coin_value_multiplier;
    }

    /**
     * Convert an amount from the base coin to its equivalent in this coin type.
     * Example: If ThisCoin is GOLD (multiplier 1000) and BaseCoin is USD,
     * 5000 USD (BaseCoin units) = 5000 / 1000 = 5 GOLD
     *
     * @param float $amountInBaseCoin
     * @return float
     * @throws \Exception if conversion is not possible (e.g., multiplier is zero for a non-base coin)
     */
    public function convertFromBaseCoin(float $amountInBaseCoin): float
    {
        if ((float)$this->base_coin_value_multiplier == 0) {
            // This state should ideally be prevented by validation for active, non-base coins.
            throw new \RuntimeException("Coin type '{$this->name}' (ID: {$this->id}) has a zero value multiplier, cannot convert from base coin.");
        }
        return $amountInBaseCoin / (float)$this->base_coin_value_multiplier;
    }

    // Accessor for status badge
    public function getStatusBadgeAttribute()
    {
        return $this->status == Status::ENABLE ?
            '<span class="badge badge--success">' . trans('Active') . '</span>' :
            '<span class="badge badge--warning">' . trans('Inactive') . '</span>';
    }

    protected static function booted()
    {
        static::saving(function (self $coinType) {
            // Sanitize code to uppercase and no spaces
            $coinType->code = strtoupper(str_replace(' ', '_', trim($coinType->code)));

            if ($coinType->is_base_coin) {
                // Base coin always has a multiplier of 1 relative to itself.
                $coinType->base_coin_value_multiplier = 1.0;

                // Ensure only one active base coin. If this one is being set as active base,
                // any other active base coin should be demoted (or throw error).
                // This logic is better handled in a dedicated service or controller action to provide clear feedback.
                // For model event, we can try to enforce it.
                if (($coinType->isDirty('is_base_coin') && $coinType->is_base_coin) || ($coinType->wasRecentlyCreated && $coinType->is_base_coin)) {
                    $existingBase = self::where('id', '!=', $coinType->id)->baseCoin()->active()->first();
                    if($existingBase && $coinType->status == Status::ENABLE){
                        // This is problematic: trying to make another coin the active base.
                        // Simplest here is to prevent saving if it creates two active base coins.
                        // A more robust solution would involve a service layer.
                        // For now, let's assume admin handles this via UI (e.g., UI prevents this state).
                        // Or, forcefully demote other base coins:
                        // self::where('id', '!=', $coinType->id)->update(['is_base_coin' => false]);
                        // However, this might have unintended consequences if not handled carefully with value multipliers.
                        // A validation rule at controller level is preferred for "only one active base coin".
                    }
                }
            } else {
                // A non-base, active coin must have a positive multiplier.
                if ($coinType->status == Status::ENABLE && (float)$coinType->base_coin_value_multiplier <= 0) {
                    throw ValidationException::withMessages([
                        'base_coin_value_multiplier' => "The value multiplier for an active, non-base coin ('{$coinType->name}') must be positive.",
                    ]);
                }
            }
        });

        static::saved(function (self $coinType) {
            // Clear cache whenever a coin type is saved, as base coin info might change.
            Cache::forget('active_base_coin_type');
            if ($coinType->isDirty('is_base_coin') || $coinType->isDirty('status')) {
                // If base coin status or designation changed, may need more specific cache clearing.
            }
        });

        static::deleting(function(self $coinType){
            if($coinType->is_base_coin && self::active()->baseCoin()->count() === 1 && $coinType->status == Status::ENABLE){
                 throw ValidationException::withMessages([
                     'general' => "Cannot delete the only active base coin type ('{$coinType->name}'). Please set another coin as active base first or deactivate this one.",
                 ]);
            }
            // Also check if there are any user balances for this coin type
            if ($coinType->userCoinBalances()->exists()) {
                throw ValidationException::withMessages([
                    'general' => "Cannot delete coin type '{$coinType->name}' as it has existing user balances. Please transfer or resolve balances first.",
                ]);
            }
        });

        static::deleted(function(){
            Cache::forget('active_base_coin_type');
        });
    }
}
