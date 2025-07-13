@extends($activeTemplate . 'layouts.frontend')
@section('content')
    @if (!$cartItems->isEmpty())
        <div class="cart-section py-120">
            <div class="container">
                <div class="row">
                    <div class="col-xl-8">
                        @foreach ($cartItems as $item)
                            <div class="cart-item">
                                <div class="cart-item__wrapper">
                                    <div class="cart-item__thumb">
                                        <img src="{{ getImage(getFilePath('raffle') . '/thumb_' . @$item->lottery->slider_images[0], getFileThumbSize('raffle')) }}" alt="" />
                                    </div>
                                    <div class="cart-item__content">
                                        <div class="wrapper">
                                            <a class="competition-item__link " href="{{ route('lottery.details', [@$item->lottery_id, @$item->lottery->slug]) }}">
                                                <h4 class="cart-item__title">
                                                    {{ $item->lottery->name }}
                                                </h4>
                                            </a>
                                            <p class="price">
                                                <span class="new-price {{ checkPickedLottery(@$item->lottery, @$item->choosen_tickets) ? '' : 'text-decoration-line-through' }}">
                                                    {{ showAmount($item->lottery->price * $item->quantity) }}
                                                </span>
                                            </p>
                                        </div>
                                        <div class="cart-item__right">
                                            <h5 class="cart-item__number">{{ $item->quantity }} @lang('tickets')</h5>
                                            <div class="d-flex justify-content-end gap-2 mt-2">
                                                <button class="view-ticket-btn btn btn--base btn--sm" data-ticket="{{ json_encode(@$item->choosen_tickets) }}"
                                                        data-picked-ticket="{{ json_encode(pickedTicket(@$item->lottery, @$item->choosen_tickets)) }}">
                                                    <i class="las la-eye "></i>
                                                </button>
                                                <button class="clear-btn btn btn--danger btn--sm" data-cartid="{{ $item->id }}">
                                                    <i class="las la-trash "></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>
                    <div class="col-xl-4">
                        <div class="order-summery">

                            <div class="order-summery__one d-flex justify-content-between">
                                <h6 class="order-summery__title-two">@lang('Grand Total :')</h6>
                                <span class="order-summery__number-two">{{ showAmount($totalPrice) }} {{ __(gs('cur_text')) }}</span>
                            </div>

                            {{-- Payment Options --}}
                            <div class="checkout mt-3">
                                <h6 class="mb-3 text-center">@lang('Choose Payment Method'):</h6>

                                {{-- Option 1: Pay with Payment Gateway (Current Flow) --}}
                                <a href="{{ route('user.lottery.cart.items') }}" class="btn btn--primary--outline d-block w-100 mb-3">
                                    <i class="las la-credit-card"></i> @lang('Pay with Payment Gateway')
                                </a>

                                {{-- Option 2: Pay with Coins --}}
                                @php
                                    $baseCoin = \App\Models\CoinType::getBaseCoin();
                                    $userBaseCoinBalance = 0;
                                    $totalPriceInBaseCoinForDisplay = $totalPrice; // Default if no conversion needed or baseCoin not found

                                    if ($baseCoin) {
                                        $balanceRecord = auth()->user() ? auth()->user()->coinBalances()->where('coin_type_id', $baseCoin->id)->first() : null;
                                        if ($balanceRecord) {
                                            $userBaseCoinBalance = $balanceRecord->balance;
                                        }
                                        // Assume $totalPrice is in the site's main currency.
                                        // If site's main currency IS the base coin, no conversion needed for display.
                                        // If not, we need to convert $totalPrice to base coin for comparison and display.
                                        // This conversion logic might be complex if site currency itself is a CoinType or needs a rate.
                                        // For simplicity, if gs('cur_text') is different from $baseCoin->code, we'd need a conversion rate.
                                        // Let's assume for now $totalPriceInBaseCoin is passed from controller or calculated here if possible.
                                        // This is a placeholder, actual calculation needs to be robust.
                                        // If $totalPrice is already in base_coin equivalent, then $totalPriceInBaseCoinForDisplay = $totalPrice
                                        // If $baseCoin->code == gs('cur_text'), then $totalPriceInBaseCoinForDisplay = $totalPrice
                                        // Otherwise, it needs conversion: $totalPriceInBaseCoinForDisplay = $baseCoin->convertFromBaseCoin($totalPrice * some_rate_if_totalPrice_is_not_already_base_equivalent)
                                        // This variable $totalPriceInBaseCoin should be passed from the controller after proper calculation.
                                        // For the disabled check, it's critical.
                                    }
                                @endphp

                                @if($baseCoin && isset($totalPriceInBaseCoin)) {{-- $totalPriceInBaseCoin must be passed from controller --}}
                                <form action="{{ route('user.lottery.cart.purchase_with_coins') }}" method="POST" class="w-100" onsubmit="return confirm('@lang('Are you sure you want to pay using your coin balance?')');">
                                    @csrf
                                    <input type="hidden" name="total_cart_price_in_site_currency" value="{{ $totalPrice }}">
                                    <button type="submit" class="btn btn--success d-block w-100"
                                            @if($userBaseCoinBalance < $totalPriceInBaseCoin) disabled title="@lang('Insufficient coin balance.')" @endif>
                                        <i class="las la-coins"></i>
                                        @lang('Pay with') {{ __($baseCoin->name) }}
                                        (@lang('Available:') {{ showAmount($userBaseCoinBalance, $baseCoin->meta['precision'] ?? 8) }} {{ $baseCoin->symbol }})
                                    </button>
                                    @if($userBaseCoinBalance < $totalPriceInBaseCoin)
                                        <small class="text-danger d-block mt-1 text-center">
                                            @lang('Insufficient balance.') @lang('Required:') {{ showAmount($totalPriceInBaseCoin, $baseCoin->meta['precision'] ?? 8) }} {{ $baseCoin->symbol }}
                                        </small>
                                    @endif
                                </form>
                                @else
                                    <button type="button" class="btn btn--success d-block w-100" disabled title="@lang('Coin payment option is currently unavailable or base coin not set.')">
                                        <i class="las la-coins"></i> @lang('Pay with Coins')
                                    </button>
                                    @if(!$baseCoin)
                                     <small class="text-warning d-block mt-1 text-center">@lang('Base coin not configured.')</small>
                                    @endif
                                     @if($baseCoin && !isset($totalPriceInBaseCoin))
                                     <small class="text-warning d-block mt-1 text-center">@lang('Price in base coin not available.')</small>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade custom--modal fade-in-scale" id="ticketModal" tabindex="-1" aria-labelledby="ticketModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title" id="ticketModalLabel">
                            <span class="title">@lang('CHOOSEN')</span>
                            <span class="title-style">@lang('TICKETS')</span>
                        </h2>
                    </div>
                    <div class="modal-body">
                        <div class="ticket-number-wrapper">
                            <ul class="ticket-number-list justify-content-center ticket-number-list-modal">
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="cart-section py-120">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xl-8">
                        <div class="empty-message-area">
                            <img src="{{ getImage($activeTemplateTrue . 'images/empty.png') }}" alt="img">
                            <h6 class="text--danger">@lang('No cart data found yet!')</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('style')
    <style>
        .custom--modal .modal-header {
            justify-content: center;
        }

        .custom--modal .modal-footer {
            justify-content: center;
        }
    </style>
@endpush

@push('script')
    <script>
        $(document).ready(function() {
            $('.view-ticket-btn').on('click', function() {
                var choosenTickets = $(this).data('ticket');
                var pickedTickets = $(this).data('picked-ticket');
                $('.ticket-number-list-modal').empty();
                $.each(choosenTickets, function(index, choosenTicket) {

                    if (pickedTickets.includes(choosenTicket)) {
                        $('.ticket-number-list-modal').append(' <li class="ticket-number-item active">' + choosenTicket + '</li>')
                    } else {
                        $('.ticket-number-list-modal').append(' <li class="ticket-number-item selected">' + choosenTicket + '</li>')
                    }

                })
                $('#ticketModal').modal('show');
            })

            $('.clear-btn').on('click', function() {
                var cartId = $(this).data('cartid');
                var $this = $(this);

                deleteLottery(cartId, $this)
                    .then(res => {
                        if (res.data.success) {
                            $this.closest(".cart-item").addClass("d-none");
                            notify("success", res.data.success);

                            var total_price = res.data.total_price;
                            var totalPrice = showAmount(total_price);
                            $('.order-summery__number-two').text(totalPrice);

                            // Cart Update
                            updateCartCount()
                                .then(response => {
                                    if (response.cartItemsCount || response.cartItemsCount == 0) {
                                        $('.cart-number').text(response.cartItemsCount);
                                    } else {
                                        notify("error", "Cart count can not be updated");
                                    }
                                })
                                .catch(error => {
                                    notify("error", "An error occurred while updating the cart count");
                                });
                        } else {
                            notify("error", res.error);
                        }
                    })
                    .catch(error => {
                        notify("error", "An error occurred while deleting cart item");
                    });

                async function deleteLottery(cartId, $this) {
                    const response = await $.ajax({
                        url: "{{ route('cart.item.delete') }}",
                        method: 'GET',
                        data: {
                            _token: '{{ csrf_token() }}',
                            id: cartId,
                        },
                    });
                    return response;
                }

                async function updateCartCount() {
                    const response = await $.ajax({
                        url: "{{ route('cart.count') }}",
                    });
                    return response;
                }

                function showAmount(amount) {
                    return "$" + parseFloat(amount).toFixed(2);
                }

            })
        })
    </script>
@endpush
