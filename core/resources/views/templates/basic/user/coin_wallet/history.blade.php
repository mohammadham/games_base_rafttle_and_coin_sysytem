@extends(activeTemplate() . 'layouts.master')
@section('content')
<div class="container pt-80 pb-80">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card custom--card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <h5 class="card-title mt-0">{{ __($pageTitle) }}</h5>
                    <div class="card-header-form">
                        <form action="{{ route('user.coin.wallet.history') }}" method="GET" class="form-inline">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control form--control" value="{{ request()->search ?? '' }}" placeholder="@lang('TRX ID / Details')">
                                <button class="input-group-text btn--base" type="submit"><i class="la la-search"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('user.coin.wallet.history') }}" method="GET" class="mb-4 card-body-form crypto-filter">
                        <div class="row align-items-end">
                            <div class="col-xl-3 col-sm-6 form-group">
                                <label class="form-label">@lang('Coin Type')</label>
                                <select name="coin_code" class="form-control form--control select2-basic" data-placeholder="@lang('Any Coin')">
                                    <option value="">@lang('Any Coin')</option>
                                    @foreach($coinTypes as $coinType)
                                        <option value="{{ $coinType->code }}" @selected(request()->coin_code == $coinType->code)>
                                            {{ __($coinType->name) }} ({{ $coinType->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xl-2 col-sm-6 form-group">
                                <label class="form-label">@lang('Transaction Type')</label>
                                <select name="trx_type" class="form-control form--control select2-basic" data-placeholder="@lang('Any Type')">
                                    <option value="">@lang('Any Type')</option>
                                    <option value="+" @selected(request()->trx_type == '+')>@lang('Credit')</option>
                                    <option value="-" @selected(request()->trx_type == '-')>@lang('Debit')</option>
                                </select>
                            </div>
                             <div class="col-xl-3 col-sm-6 form-group">
                                <label class="form-label">@lang('Remark')</label>
                                <select name="remark" class="form-control form--control select2-basic" data-placeholder="@lang('Any Remark')">
                                    <option value="">@lang('Any Remark')</option>
                                    @foreach($remarks as $remarkValue)
                                        <option value="{{ $remarkValue }}" @selected(request()->remark == $remarkValue)>{{ __(keyToTitle($remarkValue)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xl-2 col-sm-6 form-group">
                                <label class="form-label">@lang('Date')</label>
                                <input name="date" type="text" class="form-control form--control datepicker-here" data-range="true" data-multiple-dates-separator=" - " data-language="en" data-position="bottom left" placeholder="@lang('Start Date - End Date')" autocomplete="off" value="{{ request()->date }}">
                            </div>
                            <div class="col-xl-2 col-sm-6 form-group">
                                <button class="btn btn--base w-100" type="submit"><i class="las la-filter"></i> @lang('Filter')</button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Date')</th>
                                    <th>@lang('TRX')</th>
                                    <th>@lang('Coin')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Post Balance')</th>
                                    <th>@lang('Details')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($coinTransactions as $trx)
                                    <tr>
                                        <td>{{ showDateTime($trx->created_at, 'd M, Y h:i A') }}</td>
                                        <td><span class="fw-bold">{{ $trx->trx }}</span></td>
                                        <td>
                                            @if($trx->coinType)
                                                <span class="fw-bold">
                                                    @if($trx->coinType->meta['icon_class'] ?? null)
                                                        <i class="{{ $trx->coinType->meta['icon_class'] }}" style="color:{{ $trx->coinType->meta['display_color'] ?? 'inherit' }};"></i>
                                                    @endif
                                                    {{ __($trx->coinType->name) }}
                                                </span>
                                                <br>
                                                <small class="text-muted">({{ __($trx->coinType->code) }})</small>
                                            @else
                                                @lang('N/A')
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-bold @if($trx->trx_type == '+') text--success @else text--danger @endif">
                                                {{ $trx->trx_type }} {{ showAmount(abs($trx->amount), 8) }}
                                            </span>
                                        </td>
                                        <td>{{ showAmount($trx->post_balance, 8) }}</td>
                                        <td title="{{ __($trx->details) }}">{{ __(strLimit($trx->details, 40)) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($coinTransactions->hasPages())
                        <div class="mt-3 pagination--sm">
                            {{ paginateLinks($coinTransactions) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('style-lib')
    <link rel="stylesheet" href="{{asset($activeTemplateTrue.'css/vendor/datepicker.min.css')}}">
@endpush
@push('script-lib')
    <script src="{{asset($activeTemplateTrue.'js/vendor/datepicker.min.js')}}"></script>
    <script src="{{asset($activeTemplateTrue.'js/vendor/datepicker.en.js')}}"></script>
@endpush
@push('script')
    <script>
        (function($){
            "use strict";
            if($.fn.datepicker && $('.datepicker-here').length > 0){
                $('.datepicker-here').datepicker({
                    autoClose: true,
                    dateFormat: "yyyy-mm-dd", // Consistent with other date inputs
                    maxDate: new Date()
                });
            }
             // Initialize select2 if not already handled by global scripts
            if ($.fn.select2) {
                $('.select2-basic').each(function () {
                    $(this).select2({
                        dropdownParent: $(this).closest('.form-group') // or .card-body-form or specific parent
                    });
                });
            }
        })(jQuery);
    </script>
@endpush

@push('style')
<style>
    .form-label {
        font-size: 0.875rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
        color: #495057;
    }
    .select2-container .select2-selection--single {
        height: 45px !important;
        line-height: 45px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 43px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 43px !important;
    }
    .card-body-form .form-group{
        margin-bottom: 1rem; /* Add some bottom margin to form groups in filter */
    }
</style>
@endpush
