<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory; // Added
use App\Models\Tag;             // Added
use App\Models\ProductImage;   // Added
use Illuminate\Http\Request;
use App\Constants\Status;
use App\Rules\FileTypeValidate; // For image validation
use Illuminate\Support\Str;     // For Str::slug

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = trans('Manage Products');
        $query = Product::orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($request->filled('status') && in_array((int)$request->status, [Status::ENABLE, Status::DISABLE])) {
            $query->where('status', (int)$request->status);
        }

        $products = $query->paginate(getPaginate());
        // View admin.product.index needs to be created
        return view('admin.product.index', compact('pageTitle', 'products'));
    }

    public function create()
    {
        $pageTitle = trans('Create New Product');
        $product = new Product();
        $product->status = Status::ENABLE; // Default status
        $product->stock = -1; // Default to unlimited stock

        $categories = ProductCategory::where('status', Status::ENABLE)->orderBy('name')->get();
        $allTags = Tag::orderBy('name')->get(); // For existing tags suggestion
        return view('admin.product.form', compact('pageTitle', 'product', 'categories', 'allTags'));
        // View admin.product.form needs to be created
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:191',
            'slug'        => 'nullable|string|alpha_dash|max:191|unique:products,slug',
            'description' => 'nullable|string',
            'image'       => ['nullable', 'image', new FileTypeValidate(['jpeg', 'jpg', 'png', 'gif'])],
            'price'       => 'required|numeric|gte:0',
            'stock'       => 'required|integer|gte:-1', // -1 for unlimited
            'is_digital'  => 'sometimes|boolean',
            'digital_good_delivery_info' => 'nullable|string|required_if:is_digital,true',
            'status'      => 'required|in:' . Status::ENABLE . ',' . Status::DISABLE,
            'categories'  => 'nullable|array',
            'categories.*'=> 'integer|exists:product_categories,id',
            'tags'        => 'nullable|array',
            'meta_data'   => 'nullable|array', // If you have specific meta fields, validate them: 'meta_data.some_key' => 'rule'
            'gallery_images.*' => ['nullable', 'image', new FileTypeValidate(['jpeg', 'jpg', 'png', 'gif'])],
        ]);

        $product = new Product();
        $this->saveProductData($product, $request);

        $notify[] = ['success', trans('Product created successfully.')];
        return redirect()->route('admin.product.index')->withNotify($notify);
    }

    public function edit($id)
    {
        $pageTitle = trans('Edit Product');
        $product = Product::with(['categories', 'tags', 'images'])->findOrFail($id); // Eager load relationships
        $categories = ProductCategory::where('status', Status::ENABLE)->orderBy('name')->get();
        $allTags = Tag::orderBy('name')->get();
        return view('admin.product.form', compact('pageTitle', 'product', 'categories', 'allTags'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $request->validate([
            'name'        => 'required|string|max:191',
            'slug'        => 'nullable|string|alpha_dash|max:191|unique:products,slug,' . $product->id,
            'description' => 'nullable|string',
            'image'       => ['nullable', 'image', new FileTypeValidate(['jpeg', 'jpg', 'png', 'gif'])],
            'price'       => 'required|numeric|gte:0',
            'stock'       => 'required|integer|gte:-1',
            'is_digital'  => 'sometimes|boolean',
            'digital_good_delivery_info' => 'nullable|string|required_if:is_digital,true',
            'status'      => 'required|in:' . Status::ENABLE . ',' . Status::DISABLE,
            'categories'  => 'nullable|array',
            'categories.*'=> 'integer|exists:product_categories,id',
            'tags'        => 'nullable|array',
            'meta_data'   => 'nullable|array',
            'gallery_images.*' => ['nullable', 'image', new FileTypeValidate(['jpeg', 'jpg', 'png', 'gif'])],
        ]);

        $this->saveProductData($product, $request);

        $notify[] = ['success', trans('Product updated successfully.')];
        return redirect()->route('admin.product.index')->withNotify($notify);
    }

    private function saveProductData(Product $product, Request $request)
    {
        $product->name = $request->name;
        // Generate slug if it's new or name changed and slug wasn't manually provided or is same as old auto-slug
        if (!$product->exists || ($request->filled('slug') && $request->slug !== $product->getOriginal('slug')) || (!$request->filled('slug') && $product->name !== $product->getOriginal('name'))) {
            $product->slug = $request->filled('slug') ? Str::slug($request->slug) : Product::generateUniqueSlug($request->name, $product->id);
        } elseif(empty($product->slug) && !$request->filled('slug')) { // Ensure slug is generated if empty on create
             $product->slug = Product::generateUniqueSlug($request->name);
        }


        $product->description = $request->description;
        $product->price = $request->price;
        $product->stock = $request->stock;
        $product->is_digital = $request->boolean('is_digital');
        $product->digital_good_delivery_info = $product->is_digital ? $request->digital_good_delivery_info : null;
        $product->status = $request->status;
        $product->meta_data = $request->meta_data ?? [];

        if ($request->hasFile('image')) {
            try {
                $oldImage = $product->image;
                $product->image = fileUploader($request->image, getFilePath('product'), getFileSize('product'), $oldImage);
            } catch (\Exception $e) {
                $notify[] = ['error', trans('Could not upload featured image.')];
                return redirect()->back()->withNotify($notify)->withInput()->throwResponse(); // Re-throw to stop execution
            }
        }
        $product->save(); // Save product first to get an ID if it's a new product

        // Handle Categories
        if ($request->has('categories')) {
            $product->categories()->sync($request->categories);
        } else {
            $product->categories()->detach();
        }

        // Handle Tags
        if ($request->has('tags')) {
            $tagIds = [];
            foreach ($request->tags as $tagName) {
                if(empty(trim($tagName))) continue;
                $tag = Tag::firstOrCreate(
                    ['slug' => Str::slug(trim($tagName))],
                    ['name' => trim($tagName)]
                );
                $tagIds[] = $tag->id;
            }
            $product->tags()->sync($tagIds);
        } else {
            $product->tags()->detach();
        }

        // Handle Gallery Images
        if ($request->hasFile('gallery_images')) {
            $sortOrder = ($product->images()->max('sort_order') ?? 0) + 1;
            foreach ($request->file('gallery_images') as $galleryFile) {
                try {
                    $imagePath = fileUploader($galleryFile, getFilePath('product'), getFileSize('product')); // Store gallery in same path
                    $product->images()->create([
                        'image_path' => $imagePath,
                        'sort_order' => $sortOrder++,
                        'is_featured' => false // New gallery images are not featured by default
                    ]);
                } catch (\Exception $e) {
                    logger()->error("Gallery image upload failed for product ID {$product->id}: " . $e->getMessage());
                    // Optionally add a notification for partial success/failure
                }
            }
        }

        // Handle featured image selection from gallery (if main image is not set)
        // The main 'image' field on products table takes precedence.
        // If 'image' is cleared, and 'featured_image_id' is set, then that gallery image becomes 'featured'.
        // This logic might be better handled with a specific radio button or clearer UI.
        // For now, if 'featured_image_id' is passed, we set that.
        if ($request->filled('featured_image_id')) {
            $product->images()->update(['is_featured' => false]);
            ProductImage::where('id', $request->featured_image_id)
                        ->where('product_id', $product->id)
                        ->update(['is_featured' => true]);
        } elseif (!$product->image && $product->images()->where('is_featured', true)->count() == 0 && $product->images()->count() > 0) {
            // If no main image and no featured gallery image, make the first gallery image featured.
            $firstGalleryImage = $product->images()->orderBy('sort_order')->first();
            if ($firstGalleryImage) {
                $firstGalleryImage->update(['is_featured' => true]);
            }
        }
    }

    public function toggleStatus($id)
    {
        $product = Product::findOrFail($id);
        $product->status = ($product->status == Status::ENABLE) ? Status::DISABLE : Status::ENABLE;
        $product->save();
        $notify[] = ['success', trans('Product status updated.')];
        return back()->withNotify($notify);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        // Optional: Check if product is part of any orders before deleting
        // if ($product->orderItems()->exists()) {
        //     $notify[] = ['error', 'Cannot delete product as it is part of existing orders. Consider disabling it instead.'];
        //     return back()->withNotify($notify);
        // }

        // Delete image if it exists
        if ($product->image) {
            fileManager()->remove(getFilePath('product') . '/' . $product->image);
        }

        $productName = $product->name;
        $product->delete();

        $notify[] = ['success', trans("Product ':name' deleted successfully.", ['name' => $productName])];
        return back()->withNotify($notify);
    }
}
