<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $table = 'product_images';

    protected $fillable = [
        'product_id',
        'image_path', // This will store the filename (e.g., image.jpg)
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the product that owns the image.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Accessor for the full image URL.
     * Assumes images are stored in a 'product' directory configured via getFilePath('product').
     *
     * @return string|null
     */
    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            // getFilePath('product') should return the base path for product images.
            // getFileSize('product') might be used by getImage for specific versions, or can be omitted if not needed.
            return getImage(getFilePath('product') . '/' . $this->image_path); // Simplified, ensure getFileSize is used if needed by getImage
        }
        // You might want a default placeholder if no image_path is set,
        // though typically an image record wouldn't exist without an image_path.
        return null;
    }
}
