@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10 ">
                <div class="card-body p-0">
                     {{-- Search/Filter Form --}}
                    <div class="p-3 bg--white">
                        <form action="{{ route('admin.coin.transaction.index') }}" method="GET">
                            <div class="row गाय-4">
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label>@lang('Search')</label>
                                        <input type="text" name="search" class="form-control" value="{{ $selectedFilters['search'] ?? '' }}" placeholder="@lang('TRX, User, Coin, Details')">
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label>@lang('User')</label>
                                        <select name="user_id" class="form-control select2-basic" data-placeholder="@lang('Any User')">
                                            <option value="">@lang('Any')</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ (@$selectedFilters['user_id'] == $user->id) ? 'selected' : '' }}>
                                                    {{ $user->username }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label>@lang('Coin Type')</label>
                                        <select name="coin_type_id" class="form-control select2-basic" data-placeholder="@lang('Any Coin')">
                                            <option value="">@lang('Any')</option>
                                            @foreach($coinTypes as $coinType)
                                                <option value="{{ $coinType->id }}" {{ (@$selectedFilters['coin_type_id'] == $coinType->id) ? 'selected' : '' }}>
                                                    {{ __($coinType->name) }} ({{ $coinType->code }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label>@lang('TRX Type')</label>
                                        <select name="trx_type" class="form-control">
                                            <option value="">@lang('All')</option>
                                            <option value="+" {{ @$selectedFilters['trx_type'] == '+' ? 'selected' : '' }}>@lang('Credit (+)')</option>
                                            <option value="-" {{ @$selectedFilters['trx_type'] == '-' ? 'selected' : '' }}>@lang('Debit (-)')</option>
                                        </select>
                                    </div>
                                </div>
                                 <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label>@lang('Remark')</label>
                                        <input type="text" name="remark" class="form-control" value="{{ $selectedFilters['remark'] ?? '' }}" placeholder="@lang('E.g., api_credit, item_purchase')">
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label>@lang('Start Date')</label>
                                        <input type="date" name="start_date" class="form-control" value="{{ $selectedFilters['start_date'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label>@lang('End Date')</label>
                                        <input type="date" name="end_date" class="form-control" value="{{ $selectedFilters['end_date'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 col-sm-12 align-self-end">
                                    <button class="btn btn--primary w-100 h-45" type="submit"><i class="fas fa-filter"></i> @lang('Filter')</button>
                                </div>
                                <div class="col-lg-2 col-md-4 col-sm-12 align-self-end">
                                    <a href="{{ route('admin.coin.transaction.index') }}" class="btn btn--outline-secondary w-100 h-45"><i class="las la-undo-alt"></i> @lang('Reset')</a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                            <tr>
                                <th>@lang('S.N.')</th>
                                <th>@lang('TRX')</th>
                                <th>@lang('User')</th>
                                <th>@lang('Coin Type')</th>
                                <th>@lang('Amount')</th>
                                <th>@lang('Post Balance')</th>
                                <th>@lang('Type')</th>
                                <th>@lang('Remark')</th>
                                <th>@lang('Details')</th>
                                <th>@lang('Date')</th>
                                {{-- <th>@lang('API Key')</th> --}}
                                {{-- <th>@lang('Admin')</th> --}}
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($transactions as $trx)
                                <tr>
                                    <td>{{ $loop->iteration + $transactions->firstItem() - 1 }}</td>
                                    <td><span class="fw-bold">{{ $trx->trx }}</span></td>
                                    <td>
                                        @if($trx->user)
                                            <a href="{{ route('admin.users.detail', $trx->user_id) }}">{{ $trx->user->username }}</a>
                                        @else
                                            @lang('N/A')
                                        @endif
                                    </td>
                                    <td>
                                        @if($trx->coinType)
                                            <span class="fw-bold" title="{{ __($trx->coinType->name) }}">{{ $trx->coinType->code }}</span>
                                        @else
                                            @lang('N/A')
                                        @endif
                                    </td>
                                    <td class="fw-bold @if($trx->trx_type == '+') text--success @else text--danger @endif">
                                        {{ $trx->trx_type }} {{ showAmount($trx->amount < 0 ? $trx->amount * -1 : $trx->amount, $trx->coinType->meta['precision'] ?? 8) }}
                                    </td>
                                    <td>{{ showAmount($trx->post_balance, $trx->coinType->meta['precision'] ?? 8) }}</td>
                                    <td>
                                        @if($trx->trx_type == '+')
                                            <span class="badge badge--success">@lang('Credit')</span>
                                        @else
                                            <span class="badge badge--danger">@lang('Debit')</span>
                                        @endif
                                    </td>
                                    <td>{{ __($trx->remark) }}</td>
                                    <td title="{{ is_array($trx->details) ? json_encode($trx->details, JSON_PRETTY_PRINT) : $trx->details }}">
                                        {{ Str::limit(is_array($trx->details) ? ($trx->details['message'] ?? json_encode($trx->details)) : $trx->details, 30) }}
                                    </td>
                                    <td>
                                        {{ showDateTime($trx->created_at) }}<br>{{ diffForHumans($trx->created_at) }}
                                    </td>
                                    {{--
                                    <td>
                                        @if($trx->apiKey)
                                            <span title="{{ $trx->apiKey->name }}">API: {{ $trx->apiKey->user ? $trx->apiKey->user->username : 'System' }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($trx->createdByAdmin)
                                            {{ $trx->createdByAdmin->username }}
                                        @endif
                                    </td>
                                    --}}
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage ?? 'No coin transactions found.') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table><!-- table end -->
                    </div>
                </div>
                @if ($transactions->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($transactions) }}
                    </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    {{-- Can add export buttons here later if needed --}}
    {{-- <button class="btn btn-sm btn-outline--info"><i class="las la-download"></i>@lang('Export CSV')</button> --}}
@endpush

@push('script')
<script>
    (function($){
        "use strict";
        // Initialize select2 for filter dropdowns if not already initialized globally
        // $('.select2-basic').select2({
        //     dropdownParent: $('.card-body') // Adjust if needed
        // });
    })(jQuery);
</script>
@endpush
