<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Constants\Status;
use App\Rules\FileTypeValidate; // For image validation

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
        // Add categories here if implemented: $categories = ProductCategory::all();
        return view('admin.product.form', compact('pageTitle', 'product' /*, 'categories'*/));
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
            // 'category_id' => 'nullable|exists:product_categories,id',
            'meta_data'   => 'nullable|array', // If you have specific meta fields, validate them: 'meta_data.some_key' => 'rule'
        ]);

        $product = new Product();
        $this->saveProductData($product, $request);

        $notify[] = ['success', trans('Product created successfully.')];
        return redirect()->route('admin.product.index')->withNotify($notify);
    }

    public function edit($id)
    {
        $pageTitle = trans('Edit Product');
        $product = Product::findOrFail($id);
        // $categories = ProductCategory::all();
        return view('admin.product.form', compact('pageTitle', 'product' /*, 'categories'*/));
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
            // 'category_id' => 'nullable|exists:product_categories,id',
            'meta_data'   => 'nullable|array',
        ]);

        $this->saveProductData($product, $request);

        $notify[] = ['success', trans('Product updated successfully.')];
        return redirect()->route('admin.product.index')->withNotify($notify);
    }

    private function saveProductData(Product $product, Request $request)
    {
        $product->name = $request->name;
        $product->slug = $request->slug ? Str::slug($request->slug) : Product::generateUniqueSlug($request->name, $product->id);
        $product->description = $request->description;
        $product->price = $request->price;
        $product->stock = $request->stock;
        $product->is_digital = $request->boolean('is_digital');
        $product->digital_good_delivery_info = $request->is_digital ? $request->digital_good_delivery_info : null;
        $product->status = $request->status;
        // $product->category_id = $request->category_id;
        $product->meta_data = $request->meta_data ?? [];

        if ($request->hasFile('image')) {
            try {
                $oldImage = $product->image;
                $product->image = fileUploader($request->image, getFilePath('product'), getFileSize('product'), $oldImage);
            } catch (\Exception $e) {
                $notify[] = ['error', trans('Could not upload image.')];
                // Redirect back with error, or handle more gracefully
                return redirect()->back()->withNotify($notify)->withInput()->throwResponse();
            }
        }
        $product->save();
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
