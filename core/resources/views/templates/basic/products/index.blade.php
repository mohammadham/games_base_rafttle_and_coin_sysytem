@extends($activeTemplate . 'layouts.frontend')

@section('content')
    <div class="py-120">
        <div class="container">
            <div class="row">
                {{-- Sidebar for Filters --}}
                <div class="col-lg-3">
                    <aside class="sidebar bg--body">
                        {{-- Search Filter --}}
                        <div class="widget">
                            <h5 class="widget__title">@lang('Search Products')</h5>
                            <form action="{{ route('products.index') }}" method="GET">
                                <div class="form-group">
                                    <input type="text" name="search" value="{{ request()->search }}" class="form-control form--control" placeholder="@lang('Enter keyword...')">
                                </div>
                                <button type="submit" class="btn btn--base btn--sm w-100">@lang('Search')</button>
                            </form>
                        </div>

                        {{-- Category Filter --}}
                        @if(isset($categories) && $categories->count() > 0)
                        <div class="widget widget_categories">
                            <h5 class="widget__title">@lang('Categories')</h5>
                            <ul>
                                <li>
                                    <a href="{{ route('products.index', ['search' => request()->search, 'tag' => request()->tag]) }}"
                                       class="{{ !request()->filled('category') ? 'active' : '' }}">
                                        @lang('All Categories')
                                        <span>({{ $products->total() > 0 ? $categories->sum('products_count') : 0 }})</span>
                                    </a>
                                </li>
                                @foreach ($categories as $category)
                                    <li>
                                        <a href="{{ route('products.index', ['category' => $category->slug, 'search' => request()->search, 'tag' => request()->tag]) }}"
                                           class="{{ request()->category == $category->slug ? 'active' : '' }}">
                                            {{ __($category->name) }}
                                            <span>({{ $category->products_count }})</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        {{-- Tag Filter --}}
                        @if(isset($tags) && $tags->count() > 0)
                        <div class="widget widget_tag_cloud">
                            <h5 class="widget__title">@lang('Tags')</h5>
                            <div class="widget__body">
                                <a href="{{ route('products.index', ['search' => request()->search, 'category' => request()->category]) }}"
                                   class="tag-cloud-link {{ !request()->filled('tag') ? 'active' : '' }}">
                                    @lang('All Tags')
                                </a>
                                @foreach ($tags as $tag)
                                    <a href="{{ route('products.index', ['tag' => $tag->slug, 'search' => request()->search, 'category' => request()->category]) }}"
                                       class="tag-cloud-link {{ request()->tag == $tag->slug ? 'active' : '' }}">
                                        {{ __($tag->name) }} ({{$tag->products_count}})
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        @endif
                         <div class="widget">
                             <a href="{{ route('products.index') }}" class="btn btn-sm btn--outline-base w-100">@lang('Clear All Filters')</a>
                        </div>
                    </aside>
                </div>

                {{-- Product Listing --}}
                <div class="col-lg-9 mt-lg-0 mt-5">
                    <div class="row gy-4 justify-content-center">
                        @forelse($products as $product)
                            <div class="col-md-6 col-lg-4">
                                <div class="product-item card custom--card h-100">
                                    <div class="product-item__thumb">
                                        <a href="{{ route('products.detail', $product->slug) }}"> {{-- This route needs to be created --}}
                                            <img src="{{ $product->featured_image_url }}" alt="{{ __($product->name) }}">
                                        </a>
                                    </div>
                                    <div class="product-item__content card-body">
                                        <h5 class="product-item__name">
                                            <a href="{{ route('products.detail', $product->slug) }}">{{ __($product->name) }}</a>
                                        </h5>
                                        <div class="product-item__price mt-2">
                                            @if($baseCoin)
                                                <span class="price">{{ showAmount($product->price, $baseCoin->meta['precision'] ?? 8) }} {{ __($baseCoin->symbol) }}</span>
                                                <small class="text-muted">(@lang('Price in') {{__($baseCoin->name)}})</small>
                                            @else
                                                <span class="price">{{ showAmount($product->price) }} {{ __(gs('cur_text')) }}</span>
                                                <small class="text-danger">@lang('Base coin not set, price in site currency.')</small>
                                            @endif
                                        </div>
                                        <div class="product-item__stock mt-1">
                                            @if($product->stock == -1)
                                                <span class="badge badge--success">@lang('In Stock (Unlimited)')</span>
                                            @elseif($product->stock > 0)
                                                <span class="badge badge--primary">@lang('In Stock:') {{ $product->stock }}</span>
                                            @else
                                                <span class="badge badge--danger">@lang('Out of Stock')</span>
                                            @endif
                                        </div>
                                        {{-- Short description or excerpt --}}
                                        <p class="mt-2 fs--14px">{{ Str::limit(strip_tags($product->description), 100) }}</p>

                                        <div class="mt-3">
                                             <a href="{{ route('products.detail', $product->slug) }}" class="btn btn--base btn--sm w-100">@lang('View Details')</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info text-center">
                                    {{ __($emptyMessage ?? 'No products found matching your criteria.') }}
                                </div>
                            </div>
                        @endforelse
                    </div>

                    @if ($products->hasPages())
                        <div class="mt-4 pagination-md">
                            {{ paginateLinks($products) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
<style>
    .widget_categories ul { list-style: none; padding-left: 0; }
    .widget_categories ul li a {
        display: flex;
        justify-content: space-between;
        padding: 8px 12px;
        border-radius: 4px;
        text-decoration: none;
        color: #555; /* Adjust color */
    }
    .widget_categories ul li a:hover,
    .widget_categories ul li a.active {
        background-color: {{ gs('base_color') }}; /* Use theme base color */
        color: #fff;
    }
    .widget_categories ul li a span { font-size: 0.9em; }

    .widget_tag_cloud .widget__body a {
        display: inline-block;
        padding: 5px 10px;
        margin: 0 5px 5px 0;
        background-color: #f0f0f0; /* Adjust color */
        border-radius: 4px;
        text-decoration: none;
        color: #333; /* Adjust color */
        font-size: 0.9em;
    }
    .widget_tag_cloud .widget__body a:hover,
    .widget_tag_cloud .widget__body a.active {
        background-color: {{ gs('base_color') }};
        color: #fff;
    }
    .product-item__thumb img {
        width: 100%;
        height: 200px; /* Or your preferred height */
        object-fit: cover;
    }
</style>
@endpush
```

**توضیحات فایل Blade:**

*   از লেআউট اصلی فرانت‌اند (`@extends($activeTemplate . 'layouts.frontend')`) ارث‌بری می‌کند.
*   **سایدبار فیلترها (سمت چپ):**
    *   فرم جستجو بر اساس کلمه کلیدی.
    *   لیست دسته‌بندی‌ها با تعداد محصولات در هر کدام (قابل کلیک برای فیلتر).
    *   لیست برچسب‌ها (Tag Cloud) با تعداد محصولات (قابل کلیک برای فیلتر).
    *   دکمه "پاک کردن فیلترها".
*   **بخش لیست محصولات (سمت راست):**
    *   محصولات به صورت گرید (۳ محصول در هر ردیف برای دسکتاپ) نمایش داده می‌شوند.
    *   هر محصول شامل: تصویر شاخص، نام (لینک به جزئیات)، قیمت (به کوین پایه)، وضعیت موجودی، و توضیحات کوتاه است.
    *   دکمه "مشاهده جزئیات" برای هر محصول.
    *   صفحه‌بندی برای محصولات.
*   **استایل‌های اولیه:** استایل‌های ساده‌ای برای ویجت‌های فیلتر و آیتم‌های محصول اضافه شده است.

اکنون این فایل را ایجاد می‌کنم.
