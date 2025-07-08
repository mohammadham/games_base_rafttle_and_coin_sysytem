@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10">
                <div class="card-body p-0">
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Name')</th>
                                    <th>@lang('Code')</th>
                                    <th>@lang('Symbol')</th>
                                    <th>@lang('Value (vs Base Coin)')</th>
                                    <th>@lang('Base Coin?')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($coinTypes as $coinType)
                                    <tr>
                                        <td>
                                            <span class="fw-bold">{{ __($coinType->name) }}</span>
                                            @if($coinType->meta['icon_class'] ?? null)
                                                <i class="{{ $coinType->meta['icon_class'] }}" style="color:{{ $coinType->meta['display_color'] ?? 'inherit' }};"></i>
                                            @endif
                                        </td>
                                        <td><span class="fw-bold">{{ $coinType->code }}</span></td>
                                        <td>{{ $coinType->symbol }}</td>
                                        <td>
                                            @if($coinType->is_base_coin)
                                                N/A (Is Base Coin)
                                            @else
                                                {{ showAmount($coinType->base_coin_value_multiplier, 8) }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($coinType->is_base_coin)
                                                <span class="badge badge--primary">@lang('Yes')</span>
                                            @else
                                                <span class="badge badge--dark">@lang('No')</span>
                                            @endif
                                        </td>
                                        <td> @php echo $coinType->statusBadge; @endphp </td>
                                        <td>
                                            <div class="button--group">
                                                <a href="{{ route('admin.coin.types.edit', $coinType->id) }}"
                                                   class="btn btn-sm btn-outline--primary">
                                                    <i class="la la-pencil"></i> @lang('Edit')
                                                </a>
                                                @if ($coinType->status == Status::DISABLE)
                                                    <button class="btn btn-sm btn-outline--success ms-1 confirmationBtn"
                                                            data-action="{{ route('admin.coin.types.status.toggle', $coinType->id) }}"
                                                            data-question="@lang('Are you sure to enable this coin type?')">
                                                        <i class="la la-eye"></i> @lang('Enable')
                                                    </button>
                                                @else
                                                    <button class="btn btn-sm btn-outline--danger ms-1 confirmationBtn"
                                                            data-action="{{ route('admin.coin.types.status.toggle', $coinType->id) }}"
                                                            data-question="@lang('Are you sure to disable this coin type? Please ensure it is not the only active base coin if other active coin types exist, or that it has no user balances if you intend to remove it later.')">
                                                        <i class="la la-eye-slash"></i> @lang('Disable')
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table><!-- table end -->
                    </div>
                </div>
                @if ($coinTypes->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($coinTypes) }}
                    </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>

    {{-- Confirmation Modal --}}
    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.coin.types.create') }}" class="btn btn-sm btn-outline--primary"><i class="las la-plus"></i>@lang('Add New Coin Type')</a>
    <form action="{{ route('admin.coin.types.index') }}" method="GET" class="form-inline float-sm-end ms-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control bg--white" placeholder="@lang('Search by Name, Code, Symbol')" value="{{ request()->search }}">
            <button class="btn btn--primary input-group-text" type="submit"><i class="fa fa-search"></i></button>
        </div>
    </form>
@endpush
