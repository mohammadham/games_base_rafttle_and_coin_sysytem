<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Constants\Status; // Assuming Status constants are defined here for order statuses
use App\Traits\GlobalStatus; // If you use this for status badges/scopes

class Order extends Model
{
    use HasFactory;
    // use GlobalStatus; // Uncomment if you want to use its status badge/scope features for the 'status' field

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'order_number',
        'trx',
        'total_amount_base_coin',
        'item_count',
        'status',
        'payment_method',
        'payment_via',
        'shipping_address', // Store as JSON
        'customer_note',
        'admin_note',
        'paid_at',
        'processing_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'refunded_at',
    ];

    protected $casts = [
        'total_amount_base_coin' => 'decimal:8',
        'item_count' => 'integer',
        'status' => 'integer',
        'shipping_address' => 'array',
        'paid_at' => 'datetime',
        'processing_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    /**
     * Booted method to hook into Eloquent model events.
     */
    protected static function booted()
    {
        static::creating(function (self $order) {
            if (empty($order->order_number)) {
                // Generate a unique, user-friendly order number
                // Example: Prefix + Timestamp + Random part
                $prefix = 'ORD-';
                do {
                    $order->order_number = $prefix . strtoupper(Str::random(8)) . time();
                } while (static::where('order_number', $order->order_number)->exists());
            }
            if (empty($order->status)) {
                $order->status = Status::ORDER_PENDING; // Default status
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    // You might want a relationship to the payment transaction (Deposit or CoinTransaction)
    // This can be a polymorphic one if payment can be from different sources, or a direct one if always one type.
    // Example: if 'trx' refers to CoinTransaction's 'trx'
    public function coinTransaction()
    {
        return $this->hasOne(CoinTransaction::class, 'trx', 'trx');
    }
    // Or if 'trx' refers to Deposit's 'trx' for gateway payments
    public function depositTransaction()
    {
        return $this->hasOne(Deposit::class, 'trx', 'trx');
    }


    // Accessor for a user-friendly status display
    public function getStatusTextAttribute()
    {
        switch ($this->status) {
            case Status::ORDER_PENDING:
                return trans('Pending');
            case Status::ORDER_PAID:
                return trans('Paid');
            case Status::ORDER_PROCESSING:
                return trans('Processing');
            case Status::ORDER_SHIPPED:
                return trans('Shipped');
            case Status::ORDER_COMPLETED:
                return trans('Completed');
            case Status::ORDER_CANCELLED:
                return trans('Cancelled');
            case Status::ORDER_REFUNDED:
                return trans('Refunded');
            default:
                return trans('Unknown');
        }
    }

    public function getStatusBadgeAttribute()
    {
        $className = 'badge badge--';
        switch ($this->status) {
            case Status::ORDER_PENDING:
                $className .= 'warning';
                break;
            case Status::ORDER_PAID:
            case Status::ORDER_PROCESSING:
                $className .= 'info';
                break;
            case Status::ORDER_COMPLETED:
            case Status::ORDER_SHIPPED: // Assuming shipped is also a success type state for display
                $className .= 'success';
                break;
            case Status::ORDER_CANCELLED:
            case Status::ORDER_REFUNDED:
                $className .= 'danger';
                break;
            default:
                $className .= 'dark';
                break;
        }
        return '<span class="' . $className . '">' . $this->status_text . '</span>';
    }

    // Helper to calculate total items if not stored directly or needed dynamically
    // public function getTotalItemsAttribute() {
    //     return $this->items()->sum('quantity');
    // }
}
