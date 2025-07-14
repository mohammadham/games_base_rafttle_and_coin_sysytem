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
                                    <th>@lang('API Key')</th>
                                    <th>@lang('User')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($apiKeys as $apiKey)
                                    <tr>
                                        <td>{{ $apiKey->name }}</td>
                                        <td><code>{{ $apiKey->api_key }}</code></td>
                                        <td>
                                            @if($apiKey->user)
                                                <a href="{{ route('admin.users.detail', $apiKey->user_id) }}">{{ $apiKey->user->username }}</a>
                                            @else
                                                @lang('N/A')
                                            @endif
                                        </td>
                                        <td>
                                            @if ($apiKey->status)
                                                <span class="badge badge--success">@lang('Enabled')</span>
                                            @else
                                                <span class="badge badge--danger">@lang('Disabled')</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.api.key.edit', $apiKey) }}" class="btn btn-sm btn-outline--primary">
                                                <i class="la la-pencil"></i> @lang('Edit')
                                            </a>
                                            <button class="btn btn-sm btn-outline--danger confirmationBtn"
                                                    data-action="{{ route('admin.api.key.destroy', $apiKey) }}"
                                                    data-question="@lang('Are you sure you want to delete this API key?')">
                                                <i class="la la-trash"></i> @lang('Delete')
                                            </button>
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
                @if ($apiKeys->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($apiKeys) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.api.key.create') }}" class="btn btn-sm btn-outline--primary"><i class="las la-plus"></i>@lang('Add New')</a>
@endpush
