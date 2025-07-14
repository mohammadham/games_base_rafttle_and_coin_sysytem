@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.gateway.zarinpal.update') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="merchant_id">@lang('Merchant ID')</label>
                            <input type="text" class="form-control" id="merchant_id" name="merchant_id" value="{{ $gateway->extra->merchant_id ?? '' }}">
                        </div>
                        <div class="form-group">
                            <input type="checkbox" id="sandbox_mode" name="sandbox_mode" {{ $gateway->extra->sandbox_mode ? 'checked' : '' }}>
                            <label for="sandbox_mode">@lang('Sandbox Mode')</label>
                        </div>
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
