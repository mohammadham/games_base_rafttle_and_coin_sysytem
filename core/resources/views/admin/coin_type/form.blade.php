@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ isset($coinType) && $coinType->id ? route('admin.coin.type.update', $coinType->id) : route('admin.coin.type.store') }}" method="POST">
                        @csrf
                        @if(isset($coinType) && $coinType->id)
                            {{-- For updates, Laravels form method spoofing is not strictly needed if route is POST, but good practice if you use PUT/PATCH routes --}}
                            {{-- <input type="hidden" name="_method" value="POST"> --}}
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">@lang('Coin Name')</label>
                                    <input type="text" name="name" class="form-control" id="name" value="{{ old('name', $coinType->name ?? '') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="code">@lang('Coin Code')</label>
                                    <input type="text" name="code" class="form-control" id="code" value="{{ old('code', $coinType->code ?? '') }}" placeholder="@lang('E.g., GOLD, SILVER, MAIN (Unique, A-Z, 0-9, _, -)')" required {{ (isset($coinType) && $coinType->id) ? 'readonly' : '' }}>
                                    @if(isset($coinType) && $coinType->id)
                                        <small class="form-text text-muted">@lang('Coin code cannot be changed after creation.')</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="symbol">@lang('Symbol')</label>
                                    <input type="text" name="symbol" class="form-control" id="symbol" value="{{ old('symbol', $coinType->symbol ?? '') }}" placeholder="@lang('E.g., GC, $, €')">
                                </div>
                            </div>
                             <div class="col-md-3">
                                <div class="form-group">
                                    <label for="status">@lang('Status')</label>
                                    <select name="status" id="status" class="form-control" required>
                                        <option value="{{ Status::ENABLE }}" {{ old('status', $coinType->status ?? Status::ENABLE) == Status::ENABLE ? 'selected' : '' }}>@lang('Enabled')</option>
                                        <option value="{{ Status::DISABLE }}" {{ old('status', $coinType->status ?? '') == Status::DISABLE ? 'selected' : '' }}>@lang('Disabled')</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                     <label for="is_base_coin" class="fw-bold">@lang('Is Base Coin?')</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_base_coin" id="is_base_coin" value="1"
                                               {{ old('is_base_coin', $coinType->is_base_coin ?? false) ? 'checked' : '' }}
                                               @if(isset($coinType) && $coinType->id && $coinType->is_base_coin && \App\Models\CoinType::active()->baseCoin()->count() <= 1)
                                                   onchange="this.checked=true;alert('@lang('Cannot uncheck: This is the only active base coin. To change the base coin, set another coin as base first.')');"
                                               @endif
                                        >
                                        <label class="form-check-label" for="is_base_coin">@lang('Set as Base Coin')</label>
                                    </div>
                                    <small class="form-text text-muted">@lang('Only one active base coin is allowed.')</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group" id="base_coin_value_multiplier_wrapper" style="{{ old('is_base_coin', $coinType->is_base_coin ?? false) ? 'display:none;' : '' }}">
                            <label for="base_coin_value_multiplier">@lang('Value Multiplier (1 This Coin = X Base Coin)')</label>
                            <div class="input-group">
                                <input type="number" name="base_coin_value_multiplier" class="form-control" id="base_coin_value_multiplier" value="{{ old('base_coin_value_multiplier', showAmount($coinType->base_coin_value_multiplier ?? 1.00, 18, exceptZeros:true)) }}" step="any" placeholder="e.g., 1000 or 0.01">
                                <span class="input-group-text" id="baseCoinSymbolDisplay">@lang('of Base Coin')</span>
                            </div>
                            <small class="form-text text-muted">@lang('Define how many units of the base coin are equivalent to 1 unit of THIS coin. E.g., if Base is USD and this coin is Gold, and 1 Gold = 1000 USD, enter 1000.')</small>
                        </div>

                        <div class="form-group">
                            <label for="description">@lang('Description')</label>
                            <textarea name="description" class="form-control" id="description" rows="3">{{ old('description', $coinType->description ?? '') }}</textarea>
                        </div>

                        <h5 class="mt-4 mb-2">@lang('Meta Data (Optional)')</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="meta_color">@lang('Display Color')</label>
                                    <input type="color" name="meta[color]" class="form-control form-control-color" id="meta_color" value="{{ old('meta.color', $coinType->meta['color'] ?? '#000000') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="meta_icon_class">@lang('Icon Class (e.g., las la-coin)')</label>
                                    <input type="text" name="meta[icon_class]" class="form-control" id="meta_icon_class" value="{{ old('meta.icon_class', $coinType->meta['icon_class'] ?? '') }}">
                                </div>
                            </div>
                             <div class="col-md-4">
                                <div class="form-group">
                                    <label for="meta_precision">@lang('Display Precision (Decimals)')</label>
                                    <input type="number" name="meta[precision]" class="form-control" id="meta_precision" value="{{ old('meta.precision', $coinType->meta['precision'] ?? 2) }}" min="0" max="18">
                                     <small class="form-text text-muted">@lang('How many decimal places to show for this coin in UI.')</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn--primary w-100 h-45">
                                @if(isset($coinType) && $coinType->id)
                                    @lang('Update Coin Type')
                                @else
                                    @lang('Create Coin Type')
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
    <a href="{{ route('admin.coin.type.index') }}" class="btn btn-sm btn-outline--primary">
        <i class="las la-undo"></i> @lang('Back to List')
    </a>
@endpush

@push('script')
<script>
    (function($){
        "use strict";

        var baseCoinSymbol = "{{ \App\Models\CoinType::getBaseCoin()->symbol ?? gs('cur_sym') }}"; // Default to site currency symbol if no base coin
        $('#baseCoinSymbolDisplay').text("@lang('of') " + baseCoinSymbol);

        $('#is_base_coin').on('change', function() {
            if ($(this).is(':checked')) {
                $('#base_coin_value_multiplier_wrapper').hide();
                $('#base_coin_value_multiplier').val('1.0'); // Base coin is 1 of itself
            } else {
                $('#base_coin_value_multiplier_wrapper').show();
            }
        }).trigger('change'); // Trigger on page load

        // Prevent unchecking the base coin if it's the only active one (client-side validation)
        // Server-side validation is still the primary guard.
        var initialIsBase = {{ old('is_base_coin', $coinType->is_base_coin ?? false) ? 'true' : 'false' }};
        var isOnlyActiveBase = {{ (isset($coinType) && $coinType->id && $coinType->is_base_coin && $coinType->status == Status::ENABLE && \App\Models\CoinType::active()->baseCoin()->count() <= 1) ? 'true' : 'false' }};

        $('form').on('submit', function(e){
            if (initialIsBase && isOnlyActiveBase && !$('#is_base_coin').is(':checked')) {
                alert("@lang('Cannot change: This is the only active base coin. To change the base coin, set another coin as active base first.')");
                e.preventDefault();
                return false;
            }
            if (initialIsBase && isOnlyActiveBase && $('#status').val() == "{{ Status::DISABLE }}") {
                 alert("@lang('Cannot deactivate: This is the only active base coin. To change the base coin, set another coin as active base first.')");
                e.preventDefault();
                return false;
            }
        });


    })(jQuery);
</script>
@endpush
