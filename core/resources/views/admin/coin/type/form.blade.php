@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ $coinType->exists ? route('admin.coin.type.update', $coinType) : route('admin.coin.type.store') }}" method="POST">
                        @csrf
                        @if($coinType->exists)
                            @method('PUT')
                        @endif
                        <div class="form-group">
                            <label for="name">@lang('Name')</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $coinType->name) }}">
                        </div>
                        <div class="form-group">
                            <label for="code">@lang('Code')</label>
                            <input type="text" class="form-control" id="code" name="code" value="{{ old('code', $coinType->code) }}">
                        </div>
                        <div class="form-group">
                            <label for="symbol">@lang('Symbol')</label>
                            <input type="text" class="form-control" id="symbol" name="symbol" value="{{ old('symbol', $coinType->symbol) }}">
                        </div>
                        <div class="form-group">
                            <input type="checkbox" id="is_base_coin" name="is_base_coin" {{ old('is_base_coin', $coinType->is_base_coin) ? 'checked' : '' }}>
                            <label for="is_base_coin">@lang('Is Base Coin')</label>
                        </div>
                        <div class="form-group">
                            <label for="base_coin_value_multiplier">@lang('Base Coin Value Multiplier')</label>
                            <input type="text" class="form-control" id="base_coin_value_multiplier" name="base_coin_value_multiplier" value="{{ old('base_coin_value_multiplier', $coinType->base_coin_value_multiplier) }}">
                        </div>
                        <div class="form-group">
                            <label for="description">@lang('Description')</label>
                            <textarea class="form-control" id="description" name="description">{{ old('description', $coinType->description) }}</textarea>
                        </div>
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
