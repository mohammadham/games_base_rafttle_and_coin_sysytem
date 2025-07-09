@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ $product->exists ? route('admin.product.update', $product->id) : route('admin.product.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        {{-- For updates, Laravel's form method spoofing is not strictly needed if route is POST and controller handles it,
                             but good practice if you were to use PUT/PATCH routes directly.
                        @if($product->exists)
                            @method('POST') // or PUT
                        @endif
                        --}}

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="name">@lang('Product Name')</label>
                                    <input type="text" name="name" class="form-control" id="name" value="{{ old('name', $product->name) }}" required placeholder="@lang('Enter product name')">
                                </div>

                                <div class="form-group">
                                    <label for="slug">@lang('Slug (URL Friendly)')</label>
                                    <input type="text" name="slug" class="form-control" id="slug" value="{{ old('slug', $product->slug) }}" placeholder="@lang('Auto-generated if empty, or enter custom slug')">
                                     <small class="text-muted">@lang('Leave blank to auto-generate. Use only letters, numbers, dashes, and underscores.')</small>
                                </div>

                                <div class="form-group">
                                    <label for="description">@lang('Description')</label>
                                    <textarea name="description" class="form-control nicEdit" id="description" rows="5">{{ old('description', $product->description) }}</textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            @php $baseCoin = \App\Models\CoinType::getBaseCoin(); @endphp
                                            <label for="price">@lang('Price') (@lang('in') {{ $baseCoin ? __($baseCoin->name) . ' - ' . $baseCoin->symbol : gs('cur_text') }})</label>
                                            <input type="number" name="price" class="form-control" id="price" value="{{ old('price', showAmount($product->price, allowZeros:true)) }}" step="any" min="0" required placeholder="0.00">
                                            @if (!$baseCoin)
                                            <small class="text-danger">@lang('Warning: Base coin is not configured. Price will be in default site currency.')</small>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="stock">@lang('Stock Quantity')</label>
                                            <input type="number" name="stock" class="form-control" id="stock" value="{{ old('stock', $product->stock ?? -1) }}" step="1" min="-1" required>
                                            <small class="text-muted">@lang('Enter -1 for unlimited stock.')</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                     <label for="is_digital" class="fw-bold">@lang('Product Type')</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_digital" id="is_digital" value="1"
                                               {{ old('is_digital', $product->is_digital ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_digital">@lang('This is a Digital Product')</label>
                                    </div>
                                </div>

                                <div class="form-group" id="digital_good_delivery_info_wrapper" style="{{ old('is_digital', $product->is_digital ?? false) ? '' : 'display:none;' }}">
                                    <label for="digital_good_delivery_info">@lang('Digital Good Delivery Info/Instructions')</label>
                                    <textarea name="digital_good_delivery_info" class="form-control" id="digital_good_delivery_info" rows="3">{{ old('digital_good_delivery_info', $product->digital_good_delivery_info) }}</textarea>
                                    <small class="text-muted">@lang('E.g., Download link, license key format, instructions to user after purchase.')</small>
                                </div>

                                <div class="form-group">
                                    <label for="categories">@lang('Categories')</label>
                                    <select name="categories[]" id="categories" class="form-control select2-multi-select" multiple="multiple" data-placeholder="@lang('Select categories')">
                                        {{-- Loop through $categories passed from controller --}}
                                        @if(isset($categories))
                                            @foreach($categories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ (is_array(old('categories', $product->exists ? $product->categories->pluck('id')->toArray() : [])) && in_array($category->id, old('categories', $product->exists ? $product->categories->pluck('id')->toArray() : []))) ? 'selected' : '' }}>
                                                {{ __($category->name) }}
                                            </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="tags">@lang('Tags')</label>
                                    <select name="tags[]" id="tags" class="form-control select2-tags" multiple="multiple" data-placeholder="@lang('Add tags')">
                                        {{-- Loop through existing tags for product or allow new tags via select2-tags --}}
                                        @if(isset($allTags)) {{-- All available tags for dropdown --}}
                                            @foreach($allTags as $tag)
                                                <option value="{{ $tag->name }}"
                                                    {{ (is_array(old('tags', $product->exists ? $product->tags->pluck('name')->toArray() : [])) && in_array($tag->name, old('tags', $product->exists ? $product->tags->pluck('name')->toArray() : []))) ? 'selected' : '' }}>
                                                    {{ __($tag->name) }}
                                                </option>
                                            @endforeach
                                        @elseif($product->exists && $product->tags->count()) {{-- Tags already associated with product --}}
                                             @foreach($product->tags as $tag)
                                                <option value="{{ $tag->name }}" selected>{{ __($tag->name) }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <small class="text-muted">@lang('Type and press enter/comma to add new tags, or select existing ones.')</small>
                                </div>


                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="image">@lang('Featured Product Image')</label>
                                    <div class="image-upload" id="mainImageUpload">
                                        <div class="thumb">
                                            <div class="avatar-preview">
                                                <div class="profilePicPreview" style="background-image: url({{ $product->image_url ?? getImage(getFilePath('product') .'/placeholder.png') }})">
                                                    <button type="button" class="remove-image"><i class="fa fa-times"></i></button>
                                                </div>
                                            </div>
                                            <div class="avatar-edit">
                                                <input type="file" class="profilePicUpload" name="image" id="profilePicUpload1" accept=".png, .jpg, .jpeg, .gif">
                                                <label for="profilePicUpload1" class="bg--primary">@lang('Upload Image')</label>
                                                <small class="mt-2">@lang('Supported files'): <b>jpeg, jpg, png, gif</b>. @lang('Image will be resized into') {{ getFileSize('product') }}@lang('px'). </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Gallery Images Upload --}}
                                <div class="form-group">
                                    <label for="gallery_images">@lang('Gallery Images')</label>
                                    <input type="file" name="gallery_images[]" id="gallery_images" class="form-control" multiple accept=".png, .jpg, .jpeg, .gif">
                                    <small class="mt-2">@lang('Supported files'): <b>jpeg, jpg, png, gif</b>. @lang('You can select multiple images.')</small>
                                </div>

                                {{-- Display Existing Gallery Images --}}
                                @if($product->exists && $product->images->count() > 0)
                                <div class="mt-3">
                                    <h6>@lang('Current Gallery Images'):</h6>
                                    <div class="row gx-2 gy-2" id="existingGalleryImages">
                                        @foreach($product->images->sortBy('sort_order') as $galleryImage)
                                        <div class="col-4 position-relative existing-gallery-image-item" data-image-id="{{ $galleryImage->id }}">
                                            <img src="{{ $galleryImage->image_url }}" alt="@lang('Gallery image')" class="img-thumbnail">
                                            <button type="button" class="btn btn-sm btn--danger position-absolute top-0 end-0 remove-gallery-image-btn" title="@lang('Delete Image')"><i class="fa fa-times"></i></button>
                                            <div class="form-check position-absolute top-0 start-0 m-1 bg-white p-1 rounded">
                                                <input type="radio" name="featured_image_id" value="{{$galleryImage->id}}" title="@lang('Set as Featured')" @if($galleryImage->is_featured) checked @endif class="form-check-input set-featured-btn">
                                                <small>@lang('F')</small> {{-- Featured marker --}}
                                            </div>
                                            {{-- Hidden input for sort order if you implement drag-and-drop sorting --}}
                                            <input type="hidden" name="image_sort_order[{{ $galleryImage->id }}]" value="{{ $galleryImage->sort_order }}" class="image-sort-order-input">
                                        </div>
                                        @endforeach
                                    </div>
                                     <small class="text-muted">@lang('The image marked with (F) is the featured image for the gallery. The "Featured Product Image" above takes precedence if set.')</small>
                                </div>
                                @endif

                                <div class="form-group">
                                    <label for="status">@lang('Status')</label>
                                    <select name="status" id="status" class="form-control" required>
                                        <option value="{{ Status::ENABLE }}" {{ old('status', $product->status) == Status::ENABLE ? 'selected' : '' }}>@lang('Enabled')</option>
                                        <option value="{{ Status::DISABLE }}" {{ old('status', $product->status) == Status::DISABLE ? 'selected' : '' }}>@lang('Disabled')</option>
                                    </select>
                                </div>

                                {{-- Optional: Meta Data section if you want to add key-value pairs dynamically --}}
                                {{--
                                <div class="form-group">
                                    <label>@lang('Meta Data (JSON format or specific fields)')</label>
                                    <textarea name="meta_data_json" class="form-control" rows="3" placeholder='@lang('e.g., {"color": "Red", "size": "XL"}')'>{{ old('meta_data_json', $product->meta_data ? json_encode($product->meta_data, JSON_PRETTY_PRINT) : '') }}</textarea>
                                    <small>@lang('Alternatively, create specific input fields for meta data above.')</small>
                                </div>
                                --}}
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn--primary w-100 h-45">
                                @if($product->exists)
                                    @lang('Update Product')
                                @else
                                    @lang('Create Product')
                                @endif
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.product.index') }}" class="btn btn-sm btn-outline--primary">
        <i class="las la-undo"></i> @lang('Back to List')
    </a>
@endpush

@push('script')
<script>
    (function($){
        "use strict";

        // Toggle digital good delivery info based on checkbox
        $('#is_digital').on('change', function() {
            if ($(this).is(':checked')) {
                $('#digital_good_delivery_info_wrapper').slideDown();
                // $('textarea[name=digital_good_delivery_info]').prop('required', true); // Make required if digital
            } else {
                $('#digital_good_delivery_info_wrapper').slideUp();
                // $('textarea[name=digital_good_delivery_info]').prop('required', false);
            }
        }).trigger('change'); // Trigger on page load to set initial state

        // Auto-generate slug from name if slug field is empty (client-side helper, server-side generation is primary)
        $('#name').on('keyup', function() {
            if ($('#slug').val() === '') {
                // A simple slugify, Laravel's Str::slug on server is more robust
                // $('#slug').val($(this).val().toString().toLowerCase()
                //     .replace(/\s+/g, '-')           // Replace spaces with -
                //     .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
                //     .replace(/\-\-+/g, '-')         // Replace multiple - with single -
                //     .replace(/^-+/, '')             // Trim - from start of text
                //     .replace(/-+$/, ''));            // Trim - from end of text
            }
        });

        // Image preview logic (assuming ViserGo has a global function or you include one)
        function proPicURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var preview = $(input).closest('.image-upload').find('.profilePicPreview');
                    preview.css('background-image', 'url(' + e.target.result + ')');
                    preview.addClass('has-image');
                    preview.fadeIn(650);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        $(".profilePicUpload").on('change', function() {
            proPicURL(this);
        });

        $(".remove-image").on('click', function(){
            $(this).closest('.profilePicPreview').css('background-image', 'none');
            $(this).closest('.profilePicPreview').removeClass('has-image');
            $(this).closest('.image-upload').find('input[type="file"]').val('');
        });

    })(jQuery);
</script>
@endpush

@push('style')
<style>
    .profilePicPreview.has-image .remove-image {
        display: block !important;
    }
</style>
@endpush
