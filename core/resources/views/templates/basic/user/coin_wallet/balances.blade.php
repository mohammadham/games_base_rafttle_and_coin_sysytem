@extends(activeTemplate() . 'layouts.master')
@section('content')
<div class="container pt-80 pb-80">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card custom--card">
                <div class="card-header">
                    <h5 class="card-title">{{ __($pageTitle) }}</h5>
                </div>
                <div class="card-body">
                    @if($baseCoin && $coinBalances->count() > 0)
                        <div class="alert alert-info bg--info-light border--info-light">
                            <i class="fas fa-info-circle"></i> @lang('Your total coin portfolio value is approximately:')
                            <strong>{{ showAmount($totalValueInBaseCoin, $baseCoin->is_base_coin ? gs('amount_precision', 2) : 8) }} {{ __($baseCoin->symbol ?: $baseCoin->code) }}</strong>
                            (@lang('based on current values relative to the base coin')).
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Coin')</th>
                                    <th>@lang('Balance')</th>
                                    <th>@lang('Symbol / Code')</th>
                                    <th>@lang('Value (vs Base Coin)')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($coinBalances as $balance)
                                    @if($balance->coinType) {{-- Ensure coinType is loaded and not null --}}
                                    <tr>
                                        <td>
                                            @if($balance->coinType->meta['icon_class'] ?? null)
                                                <i class="{{ $balance->coinType->meta['icon_class'] }}" style="color:{{ $balance->coinType->meta['display_color'] ?? 'inherit' }}; font-size: 1.2em; margin-right: 5px;"></i>
                                            @else
                                                <i class="las la-coins" style="font-size: 1.2em; margin-right: 5px;"></i> {{-- Default icon --}}
                                            @endif
                                            <span class="fw-bold">{{ __($balance->coinType->name) }}</span>
                                        </td>
                                        <td><span class="fw-bold">{{ showAmount($balance->balance, 8) }}</span></td>
                                        <td>{{ __($balance->coinType->symbol ?: $balance->coinType->code) }}</td>
                                        <td>
                                            @if($balance->coinType->is_base_coin)
                                                <span class="badge badge--primary">@lang('Base Coin')</span>
                                            @elseif($baseCoin)
                                                1 {{ __($balance->coinType->code) }} &cong; {{ showAmount($balance->coinType->base_coin_value_multiplier, 8) }} {{ __($baseCoin->code) }}
                                            @else
                                                @lang('N/A - Base coin not set')
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">@lang('You do not have a balance for any active coin type yet.')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($coinBalances->hasPages())
                        <div class="mt-3 pagination--sm">
                            {{ paginateLinks($coinBalances) }}
                        </div>
                    @endif

                    <div class="mt-4">
                         <a href="{{ route('user.deposit.index') }}" class="btn btn--base"><i class="las la-wallet"></i> @lang('Buy Coins / Deposit Funds')</a>
                         {{-- Add other actions like transfer if implemented --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
    .alert-info-light {
        color: #0c5460;
        background-color: #d1ecf1;
        border-color: #bee5eb;
    }
    .border--info-light{
        border: 1px solid #bee5eb !important;
    }
</style>
@endpush
