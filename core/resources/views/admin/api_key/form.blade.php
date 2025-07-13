@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    {{-- Display newly generated API Key and Secret Key if available in session --}}
                    @if (session('new_api_key_generated_details'))
                        @php $details = session('new_api_key_generated_details'); @endphp
                        <div class="alert alert-success" role="alert">
                            <h4 class="alert-heading">@lang('API Key Created Successfully!')</h4>
                            <p>@lang('API Key Name:') <strong>{{ $details['name'] }}</strong></p>
                            <p>@lang('API Key:') <code>{{ $details['api_key'] }}</code></p>
                            <p class="mb-0">@lang('Secret Key:') <code>{{ $details['secret_key'] }}</code></p>
                            <hr>
                            <p class="mb-0">@lang('Please copy the API Key and Secret Key now. The Secret Key will not be shown again for security reasons.')</p>
                        </div>
                    @endif
                     {{-- Display regenerated Secret Key if available in session (after regeneration action) --}}
                    @if (session('regenerated_secret_details'))
                        @php $details = session('regenerated_secret_details'); @endphp
                        <div class="alert alert-warning" role="alert"> {{-- Using warning for regenerated --}}
                            <h4 class="alert-heading">@lang('Secret Key Regenerated Successfully!')</h4>
                            <p>@lang('API Key Name:') <strong>{{ $details['name'] }}</strong></p>
                            <p>@lang('API Key:') <code>{{ $details['api_key'] }}</code> ( @lang('Unchanged') )</p>
                            <p class="mb-0">@lang('New Secret Key:') <code>{{ $details['secret_key'] }}</code></p>
                            <hr>
                            <p class="mb-0">@lang('Please copy the new Secret Key now. It will not be shown again.')</p>
                        </div>
                    @endif


                    <form action="{{ $apiKey->exists ? route('admin.api.key.update', $apiKey->id) : route('admin.api.key.store') }}" method="POST">
                        @csrf
                        {{-- @if($apiKey->exists)
                            @method('PUT') // Or use POST and handle in controller if preferred for simplicity
                        @endif --}}

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">@lang('Key Name / Description')</label>
                                    <input type="text" name="name" class="form-control" id="name" value="{{ old('name', $apiKey->name) }}" required placeholder="@lang('E.g., My Game Production Key')">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="user_id">@lang('Associate with User (Optional)')</label>
                                    <select name="user_id" id="user_id" class="form-control select2-basic" data-placeholder="@lang('Select User')">
                                        <option value="">@lang('None / System Key')</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('user_id', $apiKey->user_id) == $user->id ? 'selected' : '' }}>
                                                {{ $user->username }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        @if($apiKey->exists)
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('API Key')</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="{{ $apiKey->api_key }}" readonly>
                                        <button type="button" class="input-group-text copy-to-clipboard" data-text-to-copy="{{ $apiKey->api_key }}"><i class="fa fa-copy"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Secret Key')</label>
                                     <div class="input-group">
                                        <input type="text" class="form-control" value="@lang('******** (Hidden for security) ********')" readonly>
                                        @if($apiKey->exists)
                                        <a href="#regenerateSecretModal" data-bs-toggle="modal" data-route="{{ route('admin.api.key.regenerate.secret', $apiKey->id) }}" class="btn btn--warning regenerate-secret-btn">@lang('Regenerate')</a>
                                        @endif
                                    </div>
                                    <small class="text-muted">@lang('Secret Key is only shown once upon creation. You can regenerate it if lost, but the old one will stop working.')</small>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">@lang('Status')</label>
                                    <select name="status" id="status" class="form-control" required>
                                        <option value="{{ Status::ENABLE }}" {{ old('status', $apiKey->status) == Status::ENABLE ? 'selected' : '' }}>@lang('Active')</option>
                                        <option value="{{ Status::DISABLE }}" {{ old('status', $apiKey->status) == Status::DISABLE ? 'selected' : '' }}>@lang('Inactive / Revoked')</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="expires_at">@lang('Expires At (Optional)')</label>
                                    <input type="datetime-local" name="expires_at" class="form-control" id="expires_at" value="{{ old('expires_at', $apiKey->expires_at ? \Carbon\Carbon::parse($apiKey->expires_at)->format('Y-m-d\TH:i') : '') }}">
                                    <small class="text-muted">@lang('Leave blank for no expiration.')</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="allowed_ips">@lang('Allowed IPs (Comma-separated, Optional)')</label>
                            <input type="text" name="allowed_ips" class="form-control" id="allowed_ips" value="{{ old('allowed_ips', is_array($apiKey->allowed_ips) ? implode(',', $apiKey->allowed_ips) : $apiKey->allowed_ips) }}" placeholder="@lang('E.g., 192.168.1.1, 203.0.113.0/24')">
                            <small class="text-muted">@lang('Leave blank to allow any IP. Supports individual IPs and CIDR notation (e.g., 192.168.1.0/24) - CIDR validation may need custom logic if not covered by FILTER_VALIDATE_IP directly for ranges.')</small>
                        </div>

                        <div class="form-group">
                            <label>@lang('Permissions')</label>
                            <div class="row">
                                @foreach($availablePermissions as $key => $value)
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $key }}" id="perm_{{ $key }}"
                                               {{ (is_array(old('permissions', $apiKey->permissions ?? [])) && in_array($key, old('permissions', $apiKey->permissions ?? []))) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="perm_{{ $key }}">
                                            {{ __($value) }} (<code>{{$key}}</code>)
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                             <small class="text-muted">@lang('If no permissions are selected, the API key might have default access or no access depending on middleware implementation.')</small>
                        </div>


                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn--primary w-100 h-45">
                                @if($apiKey->exists)
                                    @lang('Update API Key')
                                @else
                                    @lang('Create API Key')
                                @endif
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Regenerate Secret Modal --}}
    <div class="modal fade" id="regenerateSecretModal" tabindex="-1" role="dialog" aria-labelledby="regenerateSecretModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="regenerateSecretModalLabel">@lang('Regenerate Secret Key')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" id="regenerateSecretForm"> {{-- Action will be set by JS --}}
                    @csrf
                    <div class="modal-body">
                        <p>@lang('Are you sure you want to regenerate the secret key for this API key? The old secret key will stop working immediately. This action cannot be undone.')</p>
                        <p><strong>@lang('The new secret key will be displayed on the next page after successful regeneration.')</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-bs-dismiss="modal">@lang('Cancel')</button>
                        <button type="submit" class="btn btn--warning">@lang('Yes, Regenerate Secret Key')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.api.key.index') }}" class="btn btn-sm btn-outline--primary">
        <i class="las la-undo"></i> @lang('Back to List')
    </a>
@endpush

@push('script')
<script>
    (function($){
        "use strict";
        // For Select2 if you use it for users dropdown
        // $('.select2-basic').select2({
        //     dropdownParent: $('.card-body') // Adjust if modal or other parent
        // });

        $('.copy-to-clipboard').on('click', function(){
            var textToCopy = $(this).data('text-to-copy');
            var tempTextarea = $('<textarea>');
            $('body').append(tempTextarea);
            tempTextarea.val(textToCopy).select();
            try {
                document.execCommand('copy');
                notify('success', '@lang('API Key copied to clipboard!')');
            } catch (err) {
                notify('error', '@lang('Failed to copy API Key.')');
            }
            tempTextarea.remove();
        });

        $('.regenerate-secret-btn').on('click', function(e){
            var route = $(this).data('route');
            $('#regenerateSecretForm').attr('action', route);
        });

    })(jQuery);
</script>
@endpush
