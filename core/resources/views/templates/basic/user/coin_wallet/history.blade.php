@extends($activeTemplate . 'layouts.master')
@section('content')
<div class="container pt-80 pb-80">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card custom--card">
                <div class="card-header">
                    <h5 class="card-title">{{ __($pageTitle) }}</h5>
                </div>
                <div class="card-body">
                    {{-- Filter Form --}}
                    <form action="{{ route('user.coin.wallet.history') }}" method="GET" class="mb-4 crypto-search-form">
                        <div class="row gy-3">
                            <div class="col-lg-3 col-sm-6">
                                <label class="form-label">@lang('Search Term')</label>
                                <input type="text" name="search" class="form-control form--control" value="{{ request()->search }}" placeholder="@lang('TRX ID, Details, Remark')">
                            </div>
                            <div class="col-lg-2 col-sm-6">
                                <label class="form-label">@lang('Coin Type')</label>
                                <select name="coin_code" class="form-select form--control select2-basic" data-placeholder="@lang('Any Coin')">
                                    <option value="">@lang('Any Coin')</option>
                                    @foreach($coinTypes as $coinType)
                                        <option value="{{ $coinType->code }}" @selected(request()->coin_code == $coinType->code)>
                                            {{ __($coinType->name) }} ({{ $coinType->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-sm-6">
                                <label class="form-label">@lang('Transaction Type')</label>
                                <select name="trx_type" class="form-select form--control">
                                    <option value="">@lang('All Types')</option>
                                    <option value="+" @selected(request()->trx_type == '+')>@lang('Credit (+)')</option>
                                    <option value="-" @selected(request()->trx_type == '-')>@lang('Debit (-)')</option>
                                </select>
                            </div>
                             <div class="col-lg-2 col-sm-6">
                                <label class="form-label">@lang('Remark')</label>
                                 <select name="remark" class="form-select form--control select2-basic" data-placeholder="@lang('Any Remark')">
                                    <option value="">@lang('Any Remark')</option>
                                    @foreach($remarks as $remarkValue)
                                        <option value="{{ $remarkValue }}" @selected(request()->remark == $remarkValue)>
                                            {{ __(snakeCaseToTitle($remarkValue)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <label class="form-label">@lang('Date From')</label>
                                <input type="date" name="date_from" class="form-control form--control" value="{{ request()->date_from }}">
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <label class="form-label">@lang('Date To')</label>
                                <input type="date" name="date_to" class="form-control form--control" value="{{ request()->date_to }}">
                            </div>
                            <div class="col-lg-3 col-sm-6 d-flex align-items-end">
                                <button class="btn btn--base w-100" type="submit"><i class="fas fa-filter"></i> @lang('Filter')</button>
                            </div>
                             <div class="col-lg-3 col-sm-6 d-flex align-items-end">
                                 <a href="{{ route('user.coin.wallet.history') }}" class="btn btn--outline-base w-100"><i class="las la-undo-alt"></i> @lang('Reset')</a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table--responsive--md custom--table">
                            <thead>
                                <tr>
                                    <th>@lang('TRX ID')</th>
                                    <th>@lang('Date')</th>
                                    <th>@lang('Coin Type')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Post Balance')</th>
                                    <th>@lang('Type')</th>
                                    <th>@lang('Remark')</th>
                                    <th>@lang('Details')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($coinTransactions as $trx)
                                    <tr>
                                        <td data-label="@lang('TRX ID')"><strong>{{ $trx->trx }}</strong></td>
                                        <td data-label="@lang('Date')">
                                            {{ showDateTime($trx->created_at, 'd M, Y h:i A') }}<br>
                                            <small>{{ diffForHumans($trx->created_at) }}</small>
                                        </td>
                                        <td data-label="@lang('Coin Type')">
                                            @if($trx->coinType)
                                            <span class="fw-bold" title="{{ __($trx->coinType->name) }}">
                                                {{ $trx->coinType->code }}
                                                @if($trx->coinType->symbol) ({{ $trx->coinType->symbol }}) @endif
                                            </span>
                                            @else
                                            <span class="text-muted">@lang('N/A')</span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Amount')" class="fw-bold @if($trx->trx_type == '+') text--success @else text--danger @endif">
                                            {{ $trx->trx_type }} {{ showAmount(abs($trx->amount), $trx->coinType->meta['precision'] ?? 8) }}
                                        </td>
                                        <td data-label="@lang('Post Balance')">
                                            {{ showAmount($trx->post_balance, $trx->coinType->meta['precision'] ?? 8) }}
                                        </td>
                                        <td data-label="@lang('Type')">
                                            @if($trx->trx_type == '+')
                                                <span class="badge badge--success">@lang('Credit')</span>
                                            @else
                                                <span class="badge badge--danger">@lang('Debit')</span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Remark')">{{ __(snakeCaseToTitle($trx->remark)) }}</td>
                                        <td data-label="@lang('Details')" title="{{ is_array($trx->details) ? json_encode($trx->details, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : $trx->details }}">
                                            {{ Str::limit(is_array($trx->details) ? ($trx->details['message'] ?? ($trx->details['reason'] ?? ($trx->details[0] ?? json_encode($trx->details)))) : $trx->details, 50) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center">{{ __($emptyMessage ?? 'No coin transactions found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($coinTransactions->hasPages())
                        <div class="mt-3 pagination-md">
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
    {{-- <link rel="stylesheet" href="{{asset($activeTemplateTrue.'css/vendor/select2.min.css')}}"> --}}
@endpush

@push('script-lib')
    {{-- <script src="{{asset($activeTemplateTrue.'js/vendor/select2.min.js')}}"></script> --}}
@endpush
@push('script')
<script>
    (function ($) {
        "use strict";
        // $('.select2-basic').select2({
        //     // theme: "bootstrap-5",
        //     // dropdownParent: $(this).parent()
        // });
    })(jQuery);
</script>
@endpush
