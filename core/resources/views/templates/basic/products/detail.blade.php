@extends($activeTemplate . 'layouts.frontend')

@section('content')
<div class="product-details-section py-120">
    <div class="container">
        <div class="row gy-5">
            <div class="col-lg-6">
                {{-- Product Image Gallery --}}
                <div class="product-gallery-wrapper">
                    <div class="product-gallery-slider">
                        @if($product->image) {{-- Main (featured) image first --}}
                            <div class="product-gallery-slider__item">
                                <img src="{{ $product->featured_image_url }}" alt="{{ __($product->name) }}" class="w-100">
                            </div>
                        @endif
                        @foreach ($product->images->where('is_featured', false)->sortBy('sort_order') as $image)
                            <div class="product-gallery-slider__item">
                                <img src="{{ $image->image_url }}" alt="@lang('Product gallery image')" class="w-100">
                            </div>
                        @endforeach
                        @if(!$product->image && $product->images->isEmpty()) {{-- Fallback if no images at all --}}
                             <div class="product-gallery-slider__item">
                                <img src="{{ getImage(getFilePath('default') . '/placeholder.png') }}" alt="@lang('Placeholder image')" class="w-100">
                            </div>
                        @endif
                    </div>
                    @if(($product->image && $product->images->where('is_featured', false)->count() > 0) || (!$product->image && $product->images->count() > 1))
                    <div class="product-gallery-thumb-slider mt-3">
                        @if($product->image)
                            <div class="product-gallery-thumb-slider__item">
                                <img src="{{ getImage(getFilePath('product') . '/' . $product->image, getFileSize('product')) }}" alt="{{ __($product->name) }}" class="w-100">
                            </div>
                        @endif
                        @foreach ($product->images->where('is_featured', false)->sortBy('sort_order') as $image)
                            <div class="product-gallery-thumb-slider__item">
                                <img src="{{ getImage(getFilePath('product') . '/' . $image->image_path, getFileSize('product')) }}" alt="@lang('Product gallery thumb')" class="w-100">
                            </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-6">
                <div class="product-details-content">
                    <h3 class="product-details-content__title">{{ __($product->name) }}</h3>

                    <div class="product-details-content__price mt-3">
                        @if($baseCoin)
                            <span class="price h4">{{ showAmount($product->price, $baseCoin->meta['precision'] ?? 8) }} {{ __($baseCoin->symbol) }}</span>
                            <br><small class="text-muted">(@lang('Price in') {{__($baseCoin->name)}})</small>
                        @else
                            <span class="price h4">{{ showAmount($product->price) }} {{ __(gs('cur_text')) }}</span>
                            <br><small class="text-danger">@lang('Base coin not set, price in site currency.')</small>
                        @endif
                    </div>

                    <div class="product-details-content__stock-status mt-2">
                        @if($product->stock == -1)
                            <span class="badge bg--success text--white">@lang('In Stock (Unlimited)')</span>
                        @elseif($product->stock > 0)
                            <span class="badge bg--primary text--white">@lang('In Stock:') {{ $product->stock }} @lang('units')</span>
                        @else
                            <span class="badge bg--danger text--white">@lang('Out of Stock')</span>
                        @endif
                        @if($product->is_digital)
                            <span class="badge bg--info text--white ms-2">@lang('Digital Product')</span>
                        @endif
                    </div>

                    <div class="product-details-content__description mt-3">
                        <p>{!! ($product->description) !!}</p> {{-- Use {!! !!} if description contains HTML, ensure it's sanitized if from user input --}}
                    </div>

                    {{-- Categories and Tags --}}
                    @if($product->categories->count() > 0)
                    <div class="product-details-content__meta mt-3">
                        <strong>@lang('Categories:')</strong>
                        @foreach($product->categories as $category)
                            <a href="{{ route('products.index', ['category' => $category->slug]) }}" class="badge bg--secondary text--white ms-1">{{ __($category->name) }}</a>
                        @endforeach
                    </div>
                    @endif
                    @if($product->tags->count() > 0)
                    <div class="product-details-content__meta mt-2">
                        <strong>@lang('Tags:')</strong>
                        @foreach($product->tags as $tag)
                            <a href="{{ route('products.index', ['tag' => $tag->slug]) }}" class="badge bg--light text--dark ms-1">{{ __($tag->name) }}</a>
                        @endforeach
                    </div>
                    @endif

                    {{-- Purchase Actions --}}
                    <div class="product-details-content__actions mt-4">
                        @if($product->stock != 0) {{-- Not out of stock --}}
                            {{-- Buy with Coins Button/Form --}}
                            @if($baseCoin && auth()->check())
                                <form action="{{ route('user.product.purchase.with_coins', $product->slug) }}" method="POST" id="purchaseWithCoinForm"> {{-- This route needs to be created --}}
                                    @csrf
                                    {{-- Add quantity input if applicable --}}
                                    {{-- <input type="number" name="quantity" value="1" min="1" @if($product->stock != -1) max="{{$product->stock}}" @endif class="form-control form--control d-inline-block w-auto me-2"> --}}
                                    <button type="submit" class="btn btn--success btn--lg w-100 mb-2"
                                            @if($userBaseCoinBalance < $product->price) disabled title="@lang('Insufficient coin balance')" @endif>
                                        <i class="las la-coins"></i>
                                        @lang('Buy with') {{ __($baseCoin->name) }}
                                        (@lang('Your Balance:') {{ showAmount($userBaseCoinBalance, $baseCoin->meta['precision'] ?? 8) }} {{ $baseCoin->symbol }})
                                    </button>
                                    @if($userBaseCoinBalance < $product->price)
                                    <small class="text-danger d-block text-center">@lang('You do not have enough') {{__($baseCoin->name)}} @lang('to buy this product.')
                                        <a href="{{route('user.coin.wallet.purchase.form')}}">@lang('Buy Coins Now!')</a>
                                    </small>
                                    @endif
                                </form>
                            @elseif(auth()->check() && !$baseCoin)
                                <p class="text-warning">@lang('Coin payment is currently unavailable because the base coin is not configured.')</p>
                            @endif

                            {{-- Pay with Gateway Button/Form (if direct product purchase via gateway is enabled) --}}
                            {{-- This would typically add to a cart or go to a checkout page --}}
                            {{-- For now, we focus on coin purchase. Gateway purchase can be added later if needed. --}}
                            {{--
                            <form action="{{ route('product.add_to_cart', $product->id) }}" method="POST"> // Example route
                                @csrf
                                <button type="submit" class="btn btn--primary btn--lg w-100">
                                    <i class="las la-shopping-cart"></i> @lang('Add to Cart / Pay with Gateway')
                                </button>
                            </form>
                            --}}
                        @else
                             <button type="button" class="btn btn--danger btn--lg w-100" disabled>
                                <i class="las la-times-circle"></i> @lang('Out of Stock')
                            </button>
                        @endif
                         @guest
                            <p class="mt-3 text-center">@lang('Please') <a href="{{ route('user.login', ['redirect_to' => url()->current()]) }}">@lang('login')</a> @lang('or') <a href="{{ route('user.register') }}">@lang('register')</a> @lang('to purchase with coins.')</p>
                        @endguest
                    </div>
                </div>
            </div>
        </div>

        {{-- Linked Lotteries Section --}}
        @if($product->lotteries->count() > 0)
        <div class="row mt-5">
            <div class="col-lg-12">
                <h4 class="mb-3">@lang('Lotteries Featuring This Product as Prize')</h4>
                <div class="table-responsive">
                    <table class="table table--responsive--md custom--table">
                        <thead>
                            <tr>
                                <th>@lang('Lottery Name')</th>
                                <th>@lang('Draw Date')</th>
                                <th>@lang('Ticket Price')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($product->lotteries as $lottery)
                            <tr>
                                <td data-label="@lang('Lottery Name')">
                                    <a href="{{ route('lottery.details', [$lottery->id, $lottery->slug]) }}">{{ __($lottery->name) }}</a>
                                </td>
                                <td data-label="@lang('Draw Date')">{{ showDateTime($lottery->draw_date, 'd M Y H:i A') }}</td>
                                <td data-label="@lang('Ticket Price')">{{ showAmount($lottery->price) }} {{ __(gs('cur_text')) }}</td>
                                <td data-label="@lang('Status')">@php echo $lottery->drawnBadge; @endphp</td>
                                <td data-label="@lang('Action')">
                                    <a href="{{ route('lottery.details', [$lottery->id, $lottery->slug]) }}" class="btn btn--sm btn--outline-base">@lang('View Lottery')</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- Optional: Related Products Section --}}
        {{-- @if(isset($relatedProducts) && $relatedProducts->count() > 0)
        <div class="row mt-5">
            <div class="col-lg-12">
                <h4 class="mb-3">@lang('Related Products')</h4>
                <div class="row gy-4 justify-content-center">
                    @foreach($relatedProducts as $relatedProduct)
                    <div class="col-md-6 col-lg-3">
                        // Your product item card here, similar to products.index
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif --}}

    </div>
</div>
@endsection

@push('style-lib')
    {{-- Slick slider for product gallery --}}
    <link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'css/slick.css') }}">
@endpush

@push('script-lib')
    <script src="{{ asset($activeTemplateTrue . 'js/slick.min.js') }}"></script>
@endpush

@push('script')
<script>
    (function($){
        "use strict";
        // Product Gallery Slider
        $('.product-gallery-slider').slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            arrows: false,
            fade: true,
            asNavFor: '.product-gallery-thumb-slider'
        });
        $('.product-gallery-thumb-slider').slick({
            slidesToShow: 4, // Show 4 thumbs, adjust as needed
            slidesToScroll: 1,
            asNavFor: '.product-gallery-slider',
            dots: false,
            centerMode: false, // Can be true if you have enough images
            focusOnSelect: true,
            arrows: true,
            prevArrow: '<button type="button" class="slick-prev"><i class="las la-angle-left"></i></button>',
            nextArrow: '<button type="button" class="slick-next"><i class="las la-angle-right"></i></button>',
            responsive: [
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 576,
                    settings: {
                        slidesToShow: 2
                    }
                }
            ]
        });

        // Confirmation for coin purchase
        $('#purchaseWithCoinForm').on('submit', function(e){
            if(!confirm("@lang('Are you sure you want to purchase this product using your coin balance?')")){
                e.preventDefault();
            }
        });

    })(jQuery);
</script>
@endpush

@push('style')
<style>
    .product-gallery-slider__item img,
    .product-gallery-thumb-slider__item img {
        width: 100%;
        border-radius: 5px;
    }
    .product-gallery-thumb-slider__item {
        padding: 0 5px;
        cursor: pointer;
    }
    .product-gallery-thumb-slider .slick-slide {
        opacity: 0.7;
    }
    .product-gallery-thumb-slider .slick-current {
        opacity: 1;
        border: 2px solid hsl(var(--base)); /* Highlight current thumb */
        border-radius: 5px;
    }
    .product-details-content__price .price {
        color: hsl(var(--base));
        font-weight: 700;
    }
    .product-details-content__description {
        line-height: 1.8;
    }
    .product-details-content__meta strong {
        margin-right: 5px;
    }
</style>
@endpush
```

**توضیحات فایل Blade:**

*   **گالری تصاویر:** از کتابخانه Slick Slider (که به نظر می‌رسد در قالب شما موجود است) برای نمایش گالری تصاویر محصول با یک تصویر بزرگ و تصاویر کوچک بندانگشتی برای ناوبری استفاده شده است.
*   **اطلاعات محصول:** نام، قیمت (به کوین پایه)، وضعیت موجودی، توضیحات، دسته‌بندی‌ها و برچسب‌ها نمایش داده می‌شوند.
*   **دکمه "خرید با کوین":**
    *   این دکمه به صورت یک فرم است که به مسیر `user.product.purchase.with_coins` (که باید بعداً ایجاد شود) POST می‌کند.
    *   موجودی کوین پایه کاربر نمایش داده می‌شود.
    *   اگر کاربر لاگین نکرده باشد، لینک ورود/ثبت‌نام نمایش داده می‌شود.
    *   اگر موجودی کاربر کافی نباشد، دکمه غیرفعال شده و پیام مناسب به همراه لینک به صفحه خرید کوین نمایش داده می‌شود.
*   **بخش "قرعه‌کشی‌های مرتبط":** لیستی از قرعه‌کشی‌هایی که این محصول جایزه آن‌ها بوده است، نمایش داده می‌شود.
*   **استایل‌ها و اسکریپت‌ها:** استایل‌های اولیه برای گالری و اسکریپت Slick Slider اضافه شده‌اند.

اکنون این فایل را ایجاد می‌کنم.
