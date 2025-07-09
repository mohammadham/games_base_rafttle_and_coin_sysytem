<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Constants\Status;
use App\Traits\GlobalStatus; // Assuming this trait is available and used for status
use Illuminate\Support\Str;  // For Str::slug

class ProductCategory extends Model
{
    use HasFactory, GlobalStatus; // Using GlobalStatus for status related functionalities

    protected $table = 'product_categories';

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'description',
        'image',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
        'parent_id' => 'integer',
    ];

    /**
     * Get the full URL for the category image.
     */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            // Assuming 'category' is a defined file path in getFilePath() helper
            return getImage(getFilePath('category') . '/' . $this->image, getFileSize('category'));
        }
        // Default placeholder if no image
        return getImage(getFilePath('default') . '/placeholder_category.png'); // Ensure placeholder exists
    }

    /**
     * Get the parent category.
     */
    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    /**
     * Get all descendant categories (children, grandchildren, etc.).
     * This is a recursive relationship.
     */
    public function descendants()
    {
        return $this->children()->with('descendants'); // Eager load descendants recursively
    }

    /**
     * Get all ancestor categories (parent, grandparent, etc.).
     * This is a recursive relationship upwards.
     */
    public function ancestors()
    {
        return $this->parent()->with('ancestors');
    }


    /**
     * Get the products associated with this category.
     */
    public function products()
    {
        // Using 'category_product' as the pivot table name, which is Laravel's convention
        // for alphabetical order of the two related model names (Category, Product).
        return $this->belongsToMany(Product::class, 'category_product', 'category_id', 'product_id');
    }

    /**
     * Scope a query to only include active categories.
     * (If GlobalStatus trait doesn't provide this specific active() scope)
     */
    // public function scopeActive($query)
    // {
    //     return $query->where('status', Status::ENABLE);
    // }

    /**
     * Generate a unique slug for the category.
     */
    public static function generateUniqueSlug(string $name, $currentId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;
        $query = static::where('slug', $slug);
        if ($currentId !== null) {
            $query->where('id', '!=', $currentId);
        }
        while ($query->exists()) {
            $slug = $originalSlug . '-' . $count++;
            $query = static::where('slug', $slug); // Re-init query for new slug
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
        static::creating(function (self $category) {
            if (empty($category->slug)) {
                $category->slug = self::generateUniqueSlug($category->name);
            }
        });

        static::updating(function (self $category) {
            // If name is changed, and slug was auto-generated or you want it to always update
            if ($category->isDirty('name') /* && was_auto_generated_or_always_update_condition */) {
                // $category->slug = self::generateUniqueSlug($category->name, $category->id);
            }
        });

        static::deleting(function(self $category) {
            // Prevent deleting a category that has child categories, unless cascade is handled or desired.
            if ($category->children()->count() > 0) {
                // Option 1: Prevent deletion
                 throw new \RuntimeException(trans("Cannot delete category '{$category->name}' because it has subcategories. Please delete or reassign subcategories first."));
                // Option 2: Re-assign children to this category's parent (or make them top-level)
                // foreach($category->children as $child){
                //     $child->parent_id = $category->parent_id;
                //     $child->save();
                // }
            }
            // Detach all products from this category before deleting the category itself.
            // This is often handled by onDelete('cascade') on the foreign key in the pivot table,
            // but doing it here ensures it happens if DB constraints are not set or fail.
            $category->products()->detach();
        });
    }
}
