<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id', // This can be null if the original product is deleted
        'product_name_snapshot',
        'product_image_snapshot',
        // 'product_sku_snapshot',
        'quantity',
        'price_per_unit_base_coin',
        'total_price_base_coin',
        'attributes_snapshot', // Store as JSON
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_per_unit_base_coin' => 'decimal:8',
        'total_price_base_coin' => 'decimal:8',
        'attributes_snapshot' => 'array',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product associated with this order item.
     * This relationship might return null if the product was deleted
     * and the foreign key was set to null.
     */
    public function product()
    {
        return $this->belongsTo(Product::class)->withDefault([
            'name' => $this->product_name_snapshot ?? trans('Product (Deleted)'), // Fallback name
            'image_url' => $this->product_image_snapshot ? getImage(getFilePath('product') . '/' . $this->product_image_snapshot) : getImage(getFilePath('default') . '/placeholder.png'),
        ]);
    }

    // You can add an accessor for a user-friendly display of attributes
    // public function getFormattedAttributesAttribute(){
    //     if(is_array($this->attributes_snapshot) && !empty($this->attributes_snapshot)){
    //         // Format the array into a string
    //         // e.g., "Color: Red, Size: XL"
    //         // ...
    //     }
    //     return null;
    // }
}
