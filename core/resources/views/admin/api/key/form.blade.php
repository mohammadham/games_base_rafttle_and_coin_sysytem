@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ $apiKey->exists ? route('admin.api.key.update', $apiKey) : route('admin.api.key.store') }}" method="POST">
                        @csrf
                        @if($apiKey->exists)
                            @method('PUT')
                        @endif
                        <div class="form-group">
                            <label for="name">@lang('Name')</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $apiKey->name) }}">
                        </div>
                        <div class="form-group">
                            <label for="user_id">@lang('User')</label>
                            <select class="form-control" id="user_id" name="user_id">
                                <option value="">@lang('N/A')</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id', $apiKey->user_id) == $user->id ? 'selected' : '' }}>{{ $user->username }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="allowed_ips">@lang('Allowed IPs')</label>
                            <input type="text" class="form-control" id="allowed_ips" name="allowed_ips" value="{{ old('allowed_ips', $apiKey->allowed_ips) }}">
                            <small class="form-text text-muted">@lang('Comma-separated list of IP addresses.')</small>
                        </div>
                        <div class="form-group">
                            <label>@lang('Permissions')</label>
                            @foreach($availablePermissions as $key => $value)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $key }}" id="permission_{{ $key }}" {{ in_array($key, old('permissions', $apiKey->permissions ?? [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="permission_{{ $key }}">
                                        {{ $value }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-group">
                            <label for="expires_at">@lang('Expires At')</label>
                            <input type="text" class="form-control datepicker" id="expires_at" name="expires_at" value="{{ old('expires_at', $apiKey->expires_at) }}">
                        </div>
                        <div class="form-group">
                            <input type="checkbox" id="status" name="status" {{ old('status', $apiKey->status) ? 'checked' : '' }}>
                            <label for="status">@lang('Enabled')</label>
                        </div>
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-lib')
    <script src="{{ asset('assets/admin/js/vendor/datepicker.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/vendor/datepicker.en.js') }}"></script>
@endpush

@push('script')
    <script>
        (function($){
            'use strict';
            if(!$('.datepicker-here').val()){
                $('.datepicker-here').datepicker();
            }
        })(jQuery)
    </script>
@endpush
