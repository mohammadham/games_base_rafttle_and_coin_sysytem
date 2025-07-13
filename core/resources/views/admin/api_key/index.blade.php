@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10 ">
                <div class="card-body p-0">
                    <div class="row ps-3 pt-3"> {{-- Search form row --}}
                        <div class="col">
                             <form action="{{ route('admin.api.key.index') }}" method="GET" class="form-inline float-sm-right bg--white">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="@lang('Search by Name, API Key, Username, Email')" value="{{ request()->search }}">
                                    <select name="status" class="form-control">
                                        <option value="">@lang('All Status')</option>
                                        <option value="{{ Status::ENABLE }}" {{ request()->status == Status::ENABLE ? 'selected' : '' }}>@lang('Active')</option>
                                        <option value="{{ Status::DISABLE }}" {{ request()->status == Status::DISABLE ? 'selected' : '' }}>@lang('Inactive/Revoked')</option>
                                    </select>
                                    <button class="btn btn--primary input-group-text" type="submit"><i class="fa fa-search"></i></button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                            <tr>
                                <th>@lang('S.N.')</th>
                                <th>@lang('Name')</th>
                                <th>@lang('API Key (Partial)')</th>
                                <th>@lang('Owner User')</th>
                                <th>@lang('Allowed IPs')</th>
                                <th>@lang('Permissions')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Last Used')</th>
                                <th>@lang('Expires At')</th>
                                <th>@lang('Actions')</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($apiKeys as $apiKey)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ __($apiKey->name) }}</td>
                                    <td>
                                        <span class="fw-bold" title="{{ $apiKey->api_key }}">
                                            {{ Str::mask($apiKey->api_key, '*', 4, -4) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($apiKey->user)
                                            <a href="{{ route('admin.users.detail', $apiKey->user_id) }}">{{ $apiKey->user->username }}</a>
                                        @else
                                            @lang('N/A')
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($apiKey->allowed_ips))
                                            {{ implode(', ', $apiKey->allowed_ips) }}
                                        @else
                                            @lang('Any IP')
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($apiKey->permissions))
                                            @foreach($apiKey->permissions as $permission)
                                                <span class="badge badge--info">{{ __($permission) }}</span>
                                            @endforeach
                                        @else
                                            @lang('All (Default)')
                                        @endif
                                    </td>
                                    <td>
                                        @php echo $apiKey->statusBadge; @endphp
                                    </td>
                                    <td>{{ $apiKey->last_used_at ? showDateTime($apiKey->last_used_at, 'Y-m-d H:i') : trans('Never') }}</td>
                                    <td>{{ $apiKey->expires_at ? showDateTime($apiKey->expires_at, 'Y-m-d') : trans('Never') }}</td>
                                    <td>
                                        <div class="button--group">
                                            <a href="{{ route('admin.api.key.edit', $apiKey->id) }}"
                                               class="btn btn-sm btn-outline--primary">
                                                <i class="la la-pencil"></i> @lang('Edit')
                                            </a>

                                            <form action="{{ route('admin.api.key.toggle.status', $apiKey->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline--{{ $apiKey->status == Status::ENABLE ? 'danger' : 'success' }}">
                                                    <i class="la la-eye{{ $apiKey->status == Status::ENABLE ? '-slash' : '' }}"></i> @lang($apiKey->status == Status::ENABLE ? 'Disable' : 'Enable')
                                                </button>
                                            </form>

                                            <form action="{{ route('admin.api.key.revoke', $apiKey->id) }}" method="POST" class="d-inline" onsubmit="return confirm('@lang('Are you sure you want to revoke this API key? It will become immediately unusable.')');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline--warning" {{ $apiKey->status == Status::DISABLE && Str::contains($apiKey->name, '(Revoked') ? 'disabled' : '' }}>
                                                    <i class="las la-ban"></i> @lang('Revoke')
                                                </button>
                                            </form>

                                            {{-- Hard Delete - Use with caution, consider if needed based on policy --}}
                                            {{--
                                            <form action="{{ route('admin.api.key.delete', $apiKey->id) }}" method="POST" class="d-inline" onsubmit="return confirm('@lang('Are you sure you want to PERMANENTLY DELETE this API key? This action cannot be undone.')');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline--danger">
                                                    <i class="la la-trash"></i> @lang('Delete')
                                                </button>
                                            </form>
                                            --}}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage ?? 'No API keys found.') }}</td>
                                </tr>
                            @endforelse

                            </tbody>
                        </table><!-- table end -->
                    </div>
                </div>
                @if ($apiKeys->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($apiKeys) }}
                    </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>
     {{-- Add button fixed at the bottom right or top right --}}
    <a href="{{ route('admin.api.key.create') }}" class="btn btn-lg btn--primary position-fixed bottom-0 end-0 m-3" style="z-index: 1050;">
        <i class="las la-plus"></i> @lang('Add New API Key')
    </a>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.api.key.create') }}" class="btn btn-sm btn-outline--primary"><i class="las la-plus"></i>@lang('Add New')</a>
@endpush

@push('style')
<style>
    .button--group button, .button--group a {
        margin-right: 5px;
        margin-bottom: 5px;
    }
</style>
@endpush
