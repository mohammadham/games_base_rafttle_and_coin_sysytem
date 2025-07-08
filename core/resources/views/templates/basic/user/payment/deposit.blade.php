@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="container pt-80 pb-80">
        <div class="row justify-content-center">
            <div class="col-xxl-10">
                <form action="{{-- Will be set by JS --}}" method="post" class="deposit-form">
                    @csrf
                    <input type="hidden" name="currency" id="deposit_currency_input"> {{-- Added ID for easier JS targeting --}}
                    <input type="hidden" name="gateway_code" id="gateway_code_input"> {{-- For new flow --}}
                    <input type="hidden" name="gateway" id="gateway_input"> {{-- For old flow --}}

                    <div class="gateway-card card custom--card">
                        <div class="card-body">
                            <h5 class="card-title">@lang('Payment Method')</h5>
                            <div class="row justify-content-center gy-sm-4 gy-3">
                                <div class="col-xl-6">
                                    <div class="payment-system-list is-scrollable gateway-option-list">
                                        @foreach ($existingGatewayCurrency as $data)
                                            <label for="ogw-{{ titleToKey($data->name) }}" class="payment-item @if ($loop->index > 4 && (!isset($newGateways) || count($newGateways) == 0)) d-none @endif gateway-option">
                                                <div class="payment-item__info">
                                                    <span class="payment-item__check"></span>
                                                    <span class="payment-item__name">{{ __($data->name) }}</span>
                                                </div>
                                                <div class="payment-item__thumb">
                                                    <img class="payment-item__thumb-img" src="{{ $data->method->image ? getImage(getFilePath('gateway') . '/' . $data->method->image) : asset($activeTemplateTrue.'images/payment-placeholder.png') }}" alt="@lang('payment-thumb')">
                                                </div>
                                                <input class="payment-item__radio gateway-input" id="ogw-{{ titleToKey($data->name) }}" hidden
                                                       data-gateway='@json($data)' type="radio" name="selected_gateway_radio"
                                                       value="{{ $data->method_code }}" @checked(old('selected_gateway_radio', $loop->first && (!isset($newGateways) || count($newGateways) == 0)) == $data->method_code)
                                                       data-min-amount="{{ showAmount($data->min_amount) }}" data-max-amount="{{ showAmount($data->max_amount) }}"
                                                       data-flow="old" data-currency="{{ $data->currency }}"
                                                       data-crypto="{{ $data->method->crypto ?? 0 }}">
                                            </label>
                                        @endforeach

                                        @isset($newGateways)
                                            @foreach ($newGateways as $idx => $data)
                                                @php $gatewayData = (object) $data; @endphp
                                                <label for="ngw-{{ titleToKey($gatewayData->name) }}" class="payment-item @if (count($existingGatewayCurrency) + $idx > 4) d-none @endif gateway-option">
                                                    <div class="payment-item__info">
                                                        <span class="payment-item__check"></span>
                                                        <span class="payment-item__name">{{ __($gatewayData->name) }} @if($gatewayData->note ?? null) <small class="text-muted">({{ __($gatewayData->note) }})</small> @endif</span>
                                                    </div>
                                                    <div class="payment-item__thumb">
                                                        <img class="payment-item__thumb-img" src="{{ $gatewayData->image ?? asset($activeTemplateTrue.'images/payment-placeholder.png') }}" alt="@lang('payment-thumb')">
                                                    </div>
                                                    <input class="payment-item__radio gateway-input" id="ngw-{{ titleToKey($gatewayData->name) }}" hidden
                                                           data-gateway='@json($gatewayData)' type="radio" name="selected_gateway_radio"
                                                           value="{{ $gatewayData->code }}" @checked(old('selected_gateway_radio', $loop->first && count($existingGatewayCurrency) == 0) == $gatewayData->code)
                                                           data-min-amount="{{ showAmount($gatewayData->min_amount ?? 0) }}"
                                                           data-max-amount="{{ showAmount($gatewayData->max_amount ?? PHP_INT_MAX) }}" {{-- Use a very large number if no max --}}
                                                           data-flow="new" data-currency="{{ $gatewayData->currency ?? gs('cur_text') }}"
                                                           data-percent-charge="{{ $gatewayData->percent_charge ?? 0 }}"
                                                           data-fixed-charge="{{ $gatewayData->fixed_charge ?? 0 }}"
                                                           data-rate="{{ $gatewayData->rate ?? 1 }}" {{-- Rate for new gateways, if applicable --}}
                                                           data-crypto="0"> {{-- New gateways are not crypto by default --}}
                                                </label>
                                            @endforeach
                                        @endisset

                                        @php
                                            $totalGateways = (isset($existingGatewayCurrency) ? $existingGatewayCurrency->count() : 0) + (isset($newGateways) ? count($newGateways) : 0);
                                        @endphp
                                        @if ($totalGateways > 5) {{-- Adjusted condition to show button if more than 5 total --}}
                                            <button type="button" class="payment-item__btn more-gateway-option">
                                                <p class="payment-item__btn-text">@lang('Show All Payment Options')</p>
                                                <span class="payment-item__btn__icon"><i class="fas fa-chevron-down"></i></span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="payment-system-list bg-color-lg p-3">
                                        <div class="deposit-info">
                                            <div class="deposit-info__title">
                                                <p class="text mb-0">@lang('Amount')</p>
                                            </div>
                                            <div class="deposit-info__input">
                                                <div class="deposit-info__input-group input-group">
                                                    <span class="deposit-info__input-group-text">{{ gs('cur_sym') }}</span>
                                                    <input type="text" class="form-control form--control amount" name="amount" value="{{ session('cartItemPriceTotal') }}" autocomplete="off" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="deposit-info">
                                            <div class="deposit-info__title">
                                                <p class="text has-icon"> @lang('Limit')
                                                    <span></span>
                                                </p>
                                            </div>
                                            <div class="deposit-info__input">
                                                <p class="text"><span class="gateway-limit">@lang('0.00')</span>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="deposit-info">
                                            <div class="deposit-info__title">
                                                <p class="text has-icon">@lang('Processing Charge')
                                                    <span data-bs-toggle="tooltip" title="@lang('Processing charge for payment gateways')" class="proccessing-fee-info"><i class="las la-info-circle"></i> </span>
                                                </p>
                                            </div>
                                            <div class="deposit-info__input">
                                                <p class="text"><span class="processing-fee">@lang('0.00')</span>
                                                    {{ __(gs('cur_text')) }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="deposit-info total-amount pt-3">
                                            <div class="deposit-info__title">
                                                <p class="text">@lang('Total')</p>
                                            </div>
                                            <div class="deposit-info__input">
                                                <p class="text"><span class="final-amount">@lang('0.00')</span>
                                                    {{ __(gs('cur_text')) }}</p>
                                            </div>
                                        </div>

                                        <div class="deposit-info gateway-conversion d-none total-amount pt-2">
                                            <div class="deposit-info__title">
                                                <p class="text">@lang('Conversion')
                                                </p>
                                            </div>
                                            <div class="deposit-info__input">
                                                <p class="text"></p>
                                            </div>
                                        </div>
                                        <div class="deposit-info conversion-currency d-none total-amount pt-2">
                                            <div class="deposit-info__title">
                                                <p class="text">
                                                    @lang('In') <span class="gateway-currency"></span>
                                                </p>
                                            </div>
                                            <div class="deposit-info__input">
                                                <p class="text">
                                                    <span class="in-currency"></span>
                                                </p>

                                            </div>
                                        </div>
                                        <div class="d-none crypto-message mb-3">
                                            @lang('Conversion with') <span class="gateway-currency"></span> @lang('and final value will Show on next step')
                                        </div>
                                        <button type="submit" class="btn btn--base w-100" disabled>
                                            @lang('Confirm Payment')
                                        </button>
                                        <div class="info-text pt-3">
                                            <p class="text">@lang('Ensuring your funds grow safely through our secure deposit process with world-class payment options.')</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        "use strict";
        (function($) {

            var amount = parseFloat($('.amount').val() || 0);
            var gateway, minAmount, maxAmount;


            $('.amount').on('input', function(e) {
                amount = parseFloat($(this).val());
                if (!amount) {
                    amount = 0;
                }
                calculation();
            });

            $('.gateway-input').on('change', function(e) {
                gatewayChange();
            });

            function gatewayChange() {
                let gatewayElement = $('.gateway-input:checked');
                let methodCode = gatewayElement.val();

                gateway = gatewayElement.data('gateway');
                minAmount = gatewayElement.data('min-amount');
                maxAmount = gatewayElement.data('max-amount');

                let processingFeeInfo =
                    `${parseFloat(gateway.percent_charge).toFixed(2)}% with ${parseFloat(gateway.fixed_charge).toFixed(2)} {{ __(gs('cur_text')) }} charge for payment gateway processing fees`
                $(".proccessing-fee-info").attr("data-bs-original-title", processingFeeInfo);
                calculation();
            }

            gatewayChange();

            $(".more-gateway-option").on("click", function(e) {
                let paymentList = $(".gateway-option-list");
                paymentList.find(".gateway-option").removeClass("d-none");
                $(this).addClass('d-none');
                paymentList.animate({
                    scrollTop: (paymentList.height() - 60)
                }, 'slow');
            });

            function calculation() {
                if (!gateway) return;
                $(".gateway-limit").text(minAmount + " - " + maxAmount);

                let percentCharge = 0;
                let fixedCharge = 0;
                let totalPercentCharge = 0;

                if (amount) {
                    percentCharge = parseFloat(gateway.percent_charge);
                    fixedCharge = parseFloat(gateway.fixed_charge);
                    totalPercentCharge = parseFloat(amount / 100 * percentCharge);
                }

                let totalCharge = parseFloat(totalPercentCharge + fixedCharge);
                let totalAmount = parseFloat((amount || 0) + totalPercentCharge + fixedCharge);

                $(".final-amount").text(totalAmount.toFixed(2));
                $(".processing-fee").text(totalCharge.toFixed(2));
                $("input[name=currency]").val(gateway.currency);
                $(".gateway-currency").text(gateway.currency);

                if (amount < Number(gateway.min_amount) || amount > Number(gateway.max_amount)) {
                    $(".deposit-form button[type=submit]").attr('disabled', true);
                } else {
                    $(".deposit-form button[type=submit]").removeAttr('disabled');
                }

                if (gateway.currency != "{{ gs('cur_text') }}" && gateway.method.crypto != 1) {
                    $('.deposit-form').addClass('adjust-height')

                    $(".gateway-conversion, .conversion-currency").removeClass('d-none');
                    $(".gateway-conversion").find('.deposit-info__input .text').html(
                        `1 {{ __(gs('cur_text')) }} = <span class="rate">${parseFloat(gateway.rate).toFixed(2)}</span>  <span class="method_currency">${gateway.currency}</span>`
                    );
                    $('.in-currency').text(parseFloat(totalAmount * gateway.rate).toFixed(gateway.method.crypto == 1 ? 8 : 2))
                } else {
                    $(".gateway-conversion, .conversion-currency").addClass('d-none');
                    $('.deposit-form').removeClass('adjust-height')
                }

                if (gateway.method.crypto == 1) {
                    $('.crypto-message').removeClass('d-none');
                } else {
                    $('.crypto-message').addClass('d-none');
                }
            }

            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
            $('.gateway-input').change();
        })(jQuery);
    </script>
@endpush
