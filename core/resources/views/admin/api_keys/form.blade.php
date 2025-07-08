@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                 <div class="card-header">
                    <h5 class="card-title mb-0">@lang($pageTitle)</h5>
                </div>
                <div class="card-body">
                    <form action="{{ $apiKey->exists ? route('admin.game.api.keys.update', $apiKey->id) : route('admin.game.api.keys.store') }}" method="POST">
                        @csrf
                        @if($apiKey->exists)
                            @method('PUT')
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">@lang('Key Name / Description')</label>
                                    <input type="text" class="form-control" name="name" value="{{ old('name', $apiKey->name) }}" required placeholder="@lang('E.g., My Game - Production')">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">@lang('User (Owner)')</label>
                                    <select name="user_id" class="form-control select2-basic" required data-placeholder="@lang('Select User')">
                                        <option value="" disabled selected>@lang('Select an Option')</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" @selected(old('user_id', $apiKey->user_id) == $user->id)>
                                                {{ $user->username }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        @if($apiKey->exists)
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>@lang('API Key')</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="api_key_display" value="{{ $apiKey->api_key }}" readonly>
                                        <button type="button" class="input-group-text copy-btn" data-copytarget="#api_key_display"><i class="las la-copy"></i></button>
                                    </div>
                                    <small class="text-muted">@lang('This is the public API Key used to identify the client.')</small>
                                </div>
                            </div>
                             {{-- Secret Key is not shown again after creation unless regenerated --}}
                            <div class="col-md-12">
                                 <div class="form-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="regenerate_secret" id="regenerate_secret" value="1">
                                        <label class="form-check-label" for="regenerate_secret">@lang('Regenerate Secret Key?')</label>
                                    </div>
                                    <small class="text--danger">@lang('Warning: Regenerating the secret key will invalidate the current one. The new secret key will be shown once on the API Keys list page after saving.')</small>
                                 </div>
                            </div>
                        </div>
                        @endif


                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Allowed IPs (Optional)')</label>
                                    <input type="text" class="form-control" name="allowed_ips" value="{{ old('allowed_ips', $apiKey->exists ? (is_array($apiKey->allowed_ips) ? implode(',', $apiKey->allowed_ips) : $apiKey->allowed_ips) : '') }}" placeholder="e.g., 192.168.1.1, 203.0.113.0/24">
                                    <small class="form-text text-muted">@lang('Comma-separated list of IP addresses or CIDR notations. Leave blank to allow all IPs.')</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">@lang('Status')</label>
                                    <select name="status" class="form-control" required>
                                        <option value="{{ Status::ENABLE }}" @selected(old('status', $apiKey->status) == Status::ENABLE)>@lang('Enabled')</option>
                                        <option value="{{ Status::DISABLE }}" @selected(old('status', $apiKey->status) == Status::DISABLE)>@lang('Disabled')</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Expires At (Optional)')</label>
                                    {{-- Use datepicker if available, otherwise text with format hint --}}
                                    <input type="text" class="form-control datepicker-here" data-language="en" data-date-format="yyyy-mm-dd" name="expires_at"
                                           value="{{ old('expires_at', $apiKey->expires_at ? \Carbon\Carbon::parse($apiKey->expires_at)->format('Y-m-d') : '') }}"
                                           placeholder="YYYY-MM-DD" autocomplete="off">
                                    <small class="form-text text-muted">@lang('Leave blank if the key should not expire.')</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>@lang('Permissions (Optional)')</label>
                            <div class="row">
                                @foreach($permissionsList as $permissionCode => $permissionName)
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permissionCode }}" id="perm_{{ $permissionCode }}"
                                               @if(is_array(old('permissions', $apiKey->permissions ?? [])) && in_array($permissionCode, old('permissions', $apiKey->permissions ?? []))) checked @endif>
                                        <label class="form-check-label" for="perm_{{ $permissionCode }}">{{ __($permissionName) }} (<code>{{ $permissionCode }}</code>)</label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <small class="form-text text-muted">@lang('Select the actions this API key is allowed to perform.')</small>
                        </div>


                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-lib')
    {{-- Assuming datepicker is loaded globally or via a specific ViserGo push stack --}}
    {{-- <script src="{{ asset('assets/admin/js/vendor/datepicker.min.js') }}"></script> --}}
    {{-- <script src="{{ asset('assets/admin/js/vendor/datepicker.en.js') }}"></script> --}}
@endpush

@push('script')
<script>
    (function($){
        "use strict";
        // Initialize datepicker if it's a class like '.datepicker-here'
        if($.fn.datepicker && $('.datepicker-here').length > 0){
             $('.datepicker-here').datepicker({
                autoClose: true,
                dateFormat: "yyyy-mm-dd"
            });
        }

        // For copying API Key
        $('.copy-btn').on('click', function() {
            var target = $(this).data('copytarget');
            var copyText = $(target);

            if(copyText.length === 0) return;

            copyText.select();
            document.execCommand("copy");

            $(this).tooltip({title: "Copied!", placement: "top"}).tooltip('show');
            setTimeout(() => $(this).tooltip('hide'), 1000);
        });

        // Initialize select2 if not already handled by global scripts
        if ($.fn.select2) {
            $('.select2-basic').select2({
                dropdownParent: $('.card-body') // Adjust if form is in a modal
            });
        }

    })(jQuery);
</script>
@endpush

@push('style')
<style>
    /* Add any specific styles if needed */
</style>
@endpush
