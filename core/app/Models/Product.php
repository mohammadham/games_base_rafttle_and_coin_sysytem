<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Constants\Status;
use App\Traits\GlobalStatus; // Assuming this trait handles status scoping and badges
use Illuminate\Support\Str;  // For Str::slug

class Product extends Model
{
    use HasFactory, GlobalStatus; // Using GlobalStatus for status related functionalities

    protected $table = 'products';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'price',
        'stock',
        'is_digital',
        'digital_good_delivery_info',
        'status',
        // 'category_id', // Uncomment if using categories
        'meta_data',
    ];

    protected $casts = [
        // Adjust precision for 'price' based on your site's currency settings (e.g., 2 for USD/EUR, 0 for JPY, 8 for crypto-like values)
        // Using 8 decimal places for price as a general example, similar to other financial fields in ViserGo.
        'price' => 'decimal:8',
        'stock' => 'integer',
        'is_digital' => 'boolean',
        'status' => 'integer', // Handled by GlobalStatus trait if it casts to int for Status::ENABLE/DISABLE
        'meta_data' => 'array',
    ];

    /**
     * Get the full URL for the product image.
     * Assumes 'product' is a defined file path in getFilePath() helper.
     *
     * @return string|null
     */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return getImage(getFilePath('product') . '/' . $this->image, getFileSize('product'));
        }
        // Provide a default placeholder image if no image is set for the product
        // Ensure 'placeholder.png' exists in your 'assets/images/default/' or adjust path
        return getImage(getFilePath('default') . '/placeholder.png');
    }

    // If GlobalStatus trait doesn't provide active() scope, define it here:
    // public function scopeActive($query)
    // {
    //     return $query->where('status', Status::ENABLE);
    // }

    /**
     * Scope a query to only include products that are in stock.
     * In stock means unlimited stock (stock = -1) or stock > 0.
     */
    public function scopeInStock($query)
    {
        return $query->where(function($q) {
            $q->where('stock', '=', -1)
              ->orWhere('stock', '>', 0);
        });
    }

    /**
     * Generate a unique slug for the product when creating or updating.
     * Typically called within a saving event or manually before save.
     *
     * @param string $name The name of the product to generate a slug from.
     * @param int|null $currentId The ID of the current product if updating, to exclude itself from unique check.
     * @return string The generated unique slug.
     */
    public static function generateUniqueSlug(string $name, $currentId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        // Build the query to check for existing slugs
        $query = static::where('slug', $slug);
        if ($currentId !== null) {
            $query->where('id', '!=', $currentId);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $count++;
            // Re-build the query for the new slug attempt
            $query = static::where('slug', $slug);
            if ($currentId !== null) {
                $query->where('id', '!=', $currentId);
            }
        }
        return $slug;
    }

    /**
     * Booted method to hook into Eloquent model events.
     */
    protected static function booted()
    {
        static::creating(function (self $product) {
            if (empty($product->slug)) {
                $product->slug = self::generateUniqueSlug($product->name);
            }
        });

        static::updating(function (self $product) {
            // If name is changed, regenerate slug only if it's not manually set or if you want it to always update
            if ($product->isDirty('name') && empty($product->getOriginal('slug'))) { // Or some other condition
                 // $product->slug = self::generateUniqueSlug($product->name, $product->id);
            }
            // Or if you always want to update slug based on name if name changes:
            // if ($product->isDirty('name')) {
            //     $product->slug = self::generateUniqueSlug($product->name, $product->id);
            // }
        });
    }

    // Optional: Relationship to a Product Category model
    // public function category()
    // {
    //    return $this->belongsTo(ProductCategory::class, 'category_id');
    // }

    // Optional: Relationship to orders or cart items if you create those models
    // public function orderItems()
    // {
    //     return $this->hasMany(OrderItem::class); // Assuming OrderItem model
    // }
}
