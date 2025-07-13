@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10 ">
                <div class="card-body p-0">
                    {{-- Search/Filter Form --}}
                    <div class="p-3 bg--white">
                        <form action="{{ route('admin.product.index') }}" method="GET" class="form-inline">
                            <div class="row gy-4">
                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <div class="form-group">
                                        <label>@lang('Search')</label>
                                        <input type="text" name="search" class="form-control"
                                               value="{{ request()->search }}" placeholder="@lang('Name, Slug, Description')">
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-sm-12">
                                    <div class="form-group">
                                        <label>@lang('Status')</label>
                                        <select name="status" class="form-control">
                                            <option value="">@lang('All')</option>
                                            <option value="{{ Status::ENABLE }}" @selected(request()->status == Status::ENABLE)>@lang('Enabled')</option>
                                            <option value="{{ Status::DISABLE }}" @selected(request()->status == Status::DISABLE)>@lang('Disabled')</option>
                                        </select>
                                    </div>
                                </div>
                                {{-- Add other filters like category if implemented --}}
                                <div class="col-lg-3 col-md-6 col-sm-12">
                                    <div class="form-group">
                                        <label>@lang('Is Digital?')</label>
                                        <select name="is_digital" class="form-control">
                                            <option value="">@lang('All')</option>
                                            <option value="1" @selected(request()->is_digital == '1')>@lang('Yes')</option>
                                            <option value="0" @selected(request()->is_digital == '0')>@lang('No')</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-6 col-sm-12 align-self-end">
                                    <button class="btn btn--primary w-100 h-45" type="submit"><i class="fas fa-filter"></i> @lang('Filter')</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                            <tr>
                                <th>@lang('S.N.')</th>
                                <th>@lang('Image')</th>
                                <th>@lang('Name')</th>
                                <th>@lang('Price')</th>
                                <th>@lang('Stock')</th>
                                <th>@lang('Type')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Actions')</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td>{{ $loop->iteration + $products->firstItem() - 1 }}</td>
                                    <td>
                                        <div class="customer-details d-block">
                                            <a href="javascript:void(0)" class="thumb">
                                                <img src="{{ $product->image_url ?? getImage(getFilePath('product') .'/placeholder.png') }}" alt="@lang('image')">
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.product.edit', $product->id) }}">{{ __($product->name) }}</a>
                                        <br>
                                        <small>{{ $product->slug }}</small>
                                    </td>
                                    <td>{{ showAmount($product->price) }} {{ __(gs('cur_text')) }}</td>
                                    <td>
                                        @if($product->stock == -1)
                                            <span class="badge badge--success">@lang('Unlimited')</span>
                                        @elseif($product->stock > 0)
                                            <span class="badge badge--primary">{{ $product->stock }}</span>
                                        @else
                                            <span class="badge badge--danger">@lang('Out of Stock')</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($product->is_digital)
                                            <span class="badge badge--info">@lang('Digital')</span>
                                        @else
                                            <span class="badge badge--secondary">@lang('Physical')</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php echo $product->statusBadge; @endphp
                                    </td>
                                    <td>
                                        <div class="button--group">
                                            <a href="{{ route('admin.product.edit', $product->id) }}"
                                               class="btn btn-sm btn-outline--primary">
                                                <i class="la la-pencil"></i> @lang('Edit')
                                            </a>

                                            <form action="{{ route('admin.product.toggle.status', $product->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline--{{ $product->status == Status::ENABLE ? 'danger' : 'success' }}">
                                                    <i class="la la-eye{{ $product->status == Status::ENABLE ? '-slash' : '' }}"></i> @lang($product->status == Status::ENABLE ? 'Disable' : 'Enable')
                                                </button>
                                            </form>

                                            <form action="{{ route('admin.product.delete', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('@lang('Are you sure you want to delete this product? This action cannot be undone.')');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline--danger">
                                                    <i class="la la-trash"></i> @lang('Delete')
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage ?? 'No products found.') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table><!-- table end -->
                    </div>
                </div>
                @if ($products->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($products) }}
                    </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>

    {{-- Add button fixed at the bottom right or top right --}}
    <a href="{{ route('admin.product.create') }}" class="btn btn-lg btn--primary position-fixed bottom-0 end-0 m-3" style="z-index: 1050;">
        <i class="las la-plus"></i> @lang('Add New Product')
    </a>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.product.create') }}" class="btn btn-sm btn-outline--primary"><i class="las la-plus"></i>@lang('Add New')</a>
    {{-- You can add more buttons here, like import/export if needed --}}
@endpush

@push('style')
<style>
    .button--group button, .button--group a {
        margin-right: 5px;
        margin-bottom: 5px;
    }
    .form-inline .form-group {
        margin-bottom: 1rem; /* Ensure spacing for filters */
    }
    .form-inline .align-self-end {
        margin-top: 1.75rem; /* Align filter buttons with labels */
    }
</style>
@endpush
