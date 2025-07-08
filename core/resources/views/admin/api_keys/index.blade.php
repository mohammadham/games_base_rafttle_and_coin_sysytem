@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            @if(session('new_api_key_generated'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <h4 class="alert-heading">@lang('API Key Generated Successfully!')</h4>
                    <p>@lang('An API Key has been generated for:') <strong>{{ session('new_api_key_generated')['name'] }}</strong></p>
                    <hr>
                    <p class="mb-0"><strong>@lang('API Key:')</strong> <kbd>{{ session('new_api_key_generated')['api_key'] }}</kbd></p>
                    <p class="mb-0"><strong>@lang('Secret Key:')</strong> <kbd>{{ session('new_api_key_generated')['secret_key'] }}</kbd></p>
                    <p class="mt-2"><strong class="text--danger">@lang('Important: The Secret Key is shown only once. Please store it in a safe place. You will not be able to see it again.')</strong></p>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card b-radius--10">
                <div class="card-body p-0">
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Name')</th>
                                    <th>@lang('User (Owner)')</th>
                                    <th>@lang('API Key (Partial)')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Usage Count')</th>
                                    <th>@lang('Last Used')</th>
                                    <th>@lang('Expires At')</th>
                                    <th>@lang('Actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($apiKeys as $apiKey)
                                    <tr>
                                        <td>{{ $apiKey->name }}</td>
                                        <td>
                                            @if($apiKey->user)
                                                <a href="{{ route('admin.users.detail', $apiKey->user_id) }}">{{ $apiKey->user->username }}</a>
                                            @else
                                                <span class="text-muted">@lang('N/A')</span>
                                            @endif
                                        </td>
                                        <td><code>{{ Str::mask($apiKey->api_key, '*', 4, -4) }}</code></td>
                                        <td> @php echo $apiKey->statusBadge; @endphp </td>
                                        <td>{{ $apiKey->usage_count }}</td>
                                        <td>{{ $apiKey->last_used_at ? showDateTime($apiKey->last_used_at, 'Y-m-d H:i:s') : trans('Never') }}</td>
                                        <td>{{ $apiKey->expires_at ? showDateTime($apiKey->expires_at, 'Y-m-d') : trans('Never') }}</td>
                                        <td>
                                            <div class="button--group">
                                                <a href="{{ route('admin.game.api.keys.edit', $apiKey->id) }}"
                                                   class="btn btn-sm btn-outline--primary">
                                                    <i class="la la-pencil"></i> @lang('Edit')
                                                </a>
                                                <form action="{{ route('admin.game.api.keys.status.toggle', $apiKey->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @if ($apiKey->status == Status::DISABLE)
                                                        <button type="submit" class="btn btn-sm btn-outline--success ms-1">
                                                            <i class="la la-eye"></i> @lang('Enable')
                                                        </button>
                                                    @else
                                                        <button type="submit" class="btn btn-sm btn-outline--danger ms-1">
                                                            <i class="la la-eye-slash"></i> @lang('Disable')
                                                        </button>
                                                    @endif
                                                </form>
                                                {{-- Optional Delete Button --}}
                                                {{-- <button class="btn btn-sm btn-outline--danger ms-1 confirmationBtn"
                                                            data-action="{{ route('admin.game.api.keys.delete', $apiKey->id) }}" {{-- Define this route if needed --}}
                                                            {{-- data-question="@lang('Are you sure to delete this API key? This action cannot be undone and might affect active integrations.')">
                                                    <i class="la la-trash"></i> @lang('Delete')
                                                </button> --}}
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
                @if ($apiKeys->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($apiKeys) }}
                    </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>
    {{-- Confirmation MODAL for status toggle and delete --}}
    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.game.api.keys.create') }}" class="btn btn-sm btn-outline--primary"><i class="las la-plus"></i>@lang('Add New API Key')</a>
    <form action="{{ route('admin.game.api.keys.index') }}" method="GET" class="form-inline float-sm-end ms-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control bg--white" placeholder="@lang('Search by Name, API Key, User')" value="{{ request()->search ?? '' }}">
            <button class="btn btn--primary input-group-text" type="submit"><i class="fa fa-search"></i></button>
        </div>
    </form>
@endpush

@push('script')
<script>
    (function($){
        "use strict";
        // Note: confirmationBtn is a global component in ViserGo, ensure it's loaded.
        // If not, you'd need to implement the confirmation logic here or ensure the modal component is available.
    })(jQuery);
</script>
@endpush
