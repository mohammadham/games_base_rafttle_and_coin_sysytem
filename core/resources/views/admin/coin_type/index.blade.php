@extends('admin.layouts.app') {{-- Assuming 'admin.layouts.app' is your main admin layout --}}

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10">
                <div class="card-body p-0">
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('S.N.')</th>
                                    <th>@lang('Name')</th>
                                    <th>@lang('Code')</th>
                                    <th>@lang('Symbol')</th>
                                    <th>@lang('Base Coin')</th>
                                    <th>@lang('Value Multiplier (to Base)')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($coinTypes as $coinType)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ __($coinType->name) }}</td>
                                        <td><span class="fw-bold">{{ $coinType->code }}</span></td>
                                        <td>{{ $coinType->symbol }}</td>
                                        <td>
                                            @if ($coinType->is_base_coin)
                                                <span class="badge badge--success">@lang('Yes')</span>
                                            @else
                                                <span class="badge badge--dark">@lang('No')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if (!$coinType->is_base_coin)
                                                {{ showAmount($coinType->base_coin_value_multiplier, 18, exceptZeros:true) }}
                                            @else
                                                @lang('N/A')
                                            @endif
                                        </td>
                                        <td>
                                            @php echo $coinType->statusBadge; @endphp
                                        </td>
                                        <td>
                                            <div class="button--group">
                                                <a href="{{ route('admin.coin.type.edit', $coinType->id) }}"
                                                   class="btn btn-sm btn-outline--primary">
                                                    <i class="la la-pencil"></i> @lang('Edit')
                                                </a>

                                                <form action="{{ route('admin.coin.type.toggle.status', $coinType->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @if ($coinType->status == Status::ENABLE)
                                                        <button type="submit" class="btn btn-sm btn-outline--danger"
                                                                @if($coinType->is_base_coin && \App\Models\CoinType::active()->baseCoin()->count() <= 1)
                                                                    disabled title="@lang('Cannot deactivate the only active base coin.')"
                                                                @endif
                                                                >
                                                            <i class="la la-eye-slash"></i> @lang('Disable')
                                                        </button>
                                                    @else
                                                        <button type="submit" class="btn btn-sm btn-outline--success"
                                                                @if($coinType->is_base_coin && \App\Models\CoinType::active()->baseCoin()->where('id', '!=', $coinType->id)->exists())
                                                                    disabled title="@lang('Another base coin is already active.')"
                                                                @endif
                                                            >
                                                            <i class="la la-eye"></i> @lang('Enable')
                                                        </button>
                                                    @endif
                                                </form>

                                                {{-- Delete Button - Use with caution --}}
                                                {{-- Consider a confirmation modal --}}
                                                @if (!$coinType->is_base_coin && !$coinType->userCoinBalances()->exists()) {{-- Example condition for allowing delete --}}
                                                <form action="{{ route('admin.coin.type.delete', $coinType->id) }}" method="POST" class="d-inline" onsubmit="return confirm('@lang('Are you sure you want to delete this coin type? This action cannot be undone.')');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline--danger">
                                                        <i class="la la-trash"></i> @lang('Delete')
                                                    </button>
                                                </form>
                                                @else
                                                     <button class="btn btn-sm btn-outline--danger" disabled title="@if($coinType->is_base_coin) @lang('Base coin cannot be deleted.') @elseif($coinType->userCoinBalances()->exists()) @lang('Cannot delete: Coin has user balances.') @endif">
                                                        <i class="la la-trash"></i> @lang('Delete')
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage ?? 'No coin types found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($coinTypes->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($coinTypes) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Add button fixed at the bottom right or top right --}}
    <a href="{{ route('admin.coin.type.create') }}" class="btn btn-lg btn--primary position-fixed bottom-0 end-0 m-3" style="z-index: 1050;">
        <i class="las la-plus"></i> @lang('Add New Coin Type')
    </a>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.coin.type.create') }}" class="btn btn-sm btn-outline--primary">
        <i class="las la-plus"></i>@lang('Add New')
    </a>
@endpush

{{-- Add any specific styles or scripts if needed --}}
@push('style')
<style>
    .button--group button, .button--group a {
        margin-right: 5px; /* Add some space between buttons */
        margin-bottom: 5px; /* Add some space if they wrap */
    }
</style>
@endpush
