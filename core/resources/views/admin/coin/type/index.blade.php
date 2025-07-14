@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Name')</th>
                                    <th>@lang('Code')</th>
                                    <th>@lang('Symbol')</th>
                                    <th>@lang('Base Coin')</th>
                                    <th>@lang('Multiplier')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($coinTypes as $coinType)
                                    <tr>
                                        <td>{{ $coinType->name }}</td>
                                        <td>{{ $coinType->code }}</td>
                                        <td>{{ $coinType->symbol }}</td>
                                        <td>
                                            @if ($coinType->is_base_coin)
                                                <span class="badge badge--success">@lang('Yes')</span>
                                            @else
                                                <span class="badge badge--danger">@lang('No')</span>
                                            @endif
                                        </td>
                                        <td>{{ $coinType->base_coin_value_multiplier }}</td>
                                        <td>
                                            @if ($coinType->status)
                                                <span class="badge badge--success">@lang('Enabled')</span>
                                            @else
                                                <span class="badge badge--danger">@lang('Disabled')</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.coin.type.edit', $coinType) }}" class="btn btn-sm btn-outline--primary">
                                                <i class="la la-pencil"></i> @lang('Edit')
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
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
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.coin.type.create') }}" class="btn btn-sm btn-outline--primary"><i class="las la-plus"></i>@lang('Add New')</a>
@endpush
