<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str; // For Str::slug

class Tag extends Model
{
    use HasFactory;

    protected $table = 'tags';

    protected $fillable = [
        'name',
        'slug',
    ];

    // No timestamps by default for pivot table, but tags table itself has them.

    /**
     * Get the products associated with this tag.
     */
    public function products()
    {
        // Using 'product_tag' as the pivot table name, which is Laravel's convention
        // for alphabetical order of the two related model names (Product, Tag).
        return $this->belongsToMany(Product::class, 'product_tag', 'tag_id', 'product_id');
    }

    /**
     * Generate a unique slug for the tag.
     * Typically called within a saving event or manually before save.
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
        static::creating(function (self $tag) {
            if (empty($tag->slug)) {
                $tag->slug = self::generateUniqueSlug($tag->name);
            }
            // Ensure name is unique as well, or handle it via validation at controller level
            // $existingTag = self::where('name', $tag->name)->first();
            // if($existingTag) {
            //    throw new \Exception("Tag with name '{$tag->name}' already exists.");
            // }
        });

        static::updating(function (self $tag) {
            if ($tag->isDirty('name') && (empty($tag->getOriginal('slug')) || $tag->slug === Str::slug($tag->getOriginal('name')) ) ) {
                 // Only update slug if it was auto-generated from the old name or is empty
                $tag->slug = self::generateUniqueSlug($tag->name, $tag->id);
            }
        });

        static::deleting(function(self $tag) {
            // Detach all products from this tag before deleting the tag itself.
            $tag->products()->detach();
        });
    }
}
