@extends($activeTemplate . 'layouts.master')
@section('content')
<div class="container pt-80 pb-80">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card custom--card">
                <div class="card-header">
                    <h5 class="card-title">{{ __($pageTitle) }}</h5>
                </div>
                <div class="card-body">
                    @if(!$baseCoin)
                        <div class="alert alert-danger" role="alert">
                            @lang('Base coin is not configured. Please contact support.')
                        </div>
                    @elseif(count($gateways) == 0)
                        <div class="alert alert-warning" role="alert">
                            @lang('No payment gateways are currently available for purchasing coins. Please try again later or contact support.')
                        </div>
                    @else
                        <form action="{{ route('user.gateway.payment.initiate') }}" method="POST" id="coinPurchaseForm">
                            @csrf
                            <div class="form-group mb-3">
                                <label for="amount" class="form-label">@lang('Amount to Add (in') {{ __($baseCoin->name) }} - {{ $baseCoin->symbol }})</label>
                                <div class="input-group">
                                    <input type="number" name="amount" id="amount" class="form-control form--control"
                                           value="{{ old('amount') }}" step="any" min="1" {{-- Adjust min based on gateway/site policy --}}
                                           placeholder="@lang('Enter amount')" required>
                                    <span class="input-group-text">{{ __($baseCoin->symbol) }}</span>
                                </div>
                                {{-- You can add a note here about min/max deposit if available from general settings or gateway currency --}}
                                {{-- <small class="form-text text-muted">@lang('Minimum:') {{ showAmount($minDeposit, $baseCoin->symbol) }}</small> --}}
                            </div>

                            <div class="form-group mb-3">
                                <label for="gateway_code" class="form-label">@lang('Select Payment Gateway')</label>
                                <select name="gateway_code" id="gateway_code" class="form-select form--control" required>
                                    <option value="">@lang('Select One')</option>
                                    @foreach($gateways as $gateway)
                                        <option value="{{ $gateway['code'] }}" data-gateway-name="{{ __($gateway['name']) }}">
                                            {{ __($gateway['name']) }}
                                            @if($gateway['note']) <small>({{ __($gateway['note']) }})</small> @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Hidden field for currency, assuming purchase is for base coin --}}
                            <input type="hidden" name="currency" value="{{ $baseCoin->code }}">


                            {{-- Display calculated charges and total payable amount (Optional - JS can do this dynamically) --}}
                            {{-- This part would require fetching gateway currency details via JS or having them preloaded --}}
                            {{-- For simplicity, we'll let the PaymentController handle charge calculation for now --}}
                            {{--
                            <div id="paymentSummary" class="mt-3" style="display: none;">
                                <p>@lang('Amount'): <span id="summaryAmount"></span> {{ __($baseCoin->symbol) }}</p>
                                <p>@lang('Charge'): <span id="summaryCharge"></span> {{ __($baseCoin->symbol) }}</p>
                                <p>@lang('Total Payable'): <span id="summaryPayable"></span> {{ __($baseCoin->symbol) }}</p>
                                <p><small>@lang('You will be redirected to') <span id="summaryGatewayName"></span>.</small></p>
                            </div>
                            --}}

                            <div class="form-group">
                                <button type="submit" class="btn btn--base w-100">
                                    @lang('Proceed to Payment') <i class="las la-arrow-right"></i>
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('user.home') }}" class="btn btn-sm btn-outline--base">
        <i class="las la-tachometer-alt"></i> @lang('Dashboard')
    </a>
    <a href="{{ route('user.coin.wallet.balances') }}" class="btn btn-sm btn-outline--primary">
        <i class="las la-wallet"></i> @lang('My Coin Balances')
    </a>
@endpush

@push('script')
<script>
    (function($){
        "use strict";
        // Optional: Script to update payment summary dynamically if needed
        // $('#amount, #gateway_code').on('change keyup', function() {
        //     let amount = parseFloat($('#amount').val()) || 0;
        //     let gatewayCode = $('#gateway_code').val();
        //     let gatewayName = $('#gateway_code option:selected').data('gateway-name');

        //     if(amount > 0 && gatewayCode) {
        //         // Here you would ideally make an AJAX call to get charge info for the selected gateway and amount
        //         // For now, just showing basic info
        //         $('#summaryAmount').text(amount.toFixed({{ $baseCoin->meta['precision'] ?? 2 }}));
        //         // $('#summaryCharge').text('...'); // Fetch charge
        //         // $('#summaryPayable').text('...'); // Calculate total
        //         $('#summaryGatewayName').text(gatewayName);
        //         $('#paymentSummary').slideDown();
        //     } else {
        //         $('#paymentSummary').slideUp();
        //     }
        // });
    })(jQuery);
</script>
@endpush
