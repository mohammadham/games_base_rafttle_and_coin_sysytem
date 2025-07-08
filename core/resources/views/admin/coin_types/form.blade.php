@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">@lang($pageTitle)</h5>
                </div>
                <div class="card-body">
                    <form action="{{ $coinType->exists ? route('admin.coin.types.update', $coinType->id) : route('admin.coin.types.store') }}" method="POST">
                        @csrf
                        @if($coinType->exists)
                            @method('PUT')
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">@lang('Coin Name')</label>
                                    <input type="text" class="form-control" name="name" value="{{ old('name', $coinType->name) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">@lang('Coin Code')</label>
                                    <input type="text" class="form-control" name="code" value="{{ old('code', $coinType->code) }}" placeholder="@lang('E.g., GOLD, CREDIT. Uppercase, alpha-numeric, underscores.')" required>
                                    <small class="form-text text-muted">@lang('Unique, uppercase, alpha-numeric characters and underscores only.')</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Symbol')</label>
                                    <input type="text" class="form-control" name="symbol" value="{{ old('symbol', $coinType->symbol) }}">
                                </div>
                            </div>
                             <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">@lang('Status')</label>
                                    <select name="status" class="form-control" required>
                                        <option value="{{ Status::ENABLE }}" @selected(old('status', $coinType->status) == Status::ENABLE)>@lang('Enabled')</option>
                                        <option value="{{ Status::DISABLE }}" @selected(old('status', $coinType->status) == Status::DISABLE)>@lang('Disabled')</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_base_coin" id="is_base_coin" value="1"
                                               @if(old('is_base_coin', $coinType->is_base_coin)) checked @endif
                                               >
                                        <label class="form-check-label" for="is_base_coin">@lang('Is Base Coin?')</label>
                                    </div>
                                    <small class="form-text text-muted d-block">@lang('If checked, this coin will be the base for value conversions. Multiplier will be set to 1. Ensure only one active base coin exists.')</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required-if-not-base">@lang('Value Multiplier (1 ThisCoin = X BaseCoin)')</label>
                                    <input type="number" step="any" class="form-control" name="base_coin_value_multiplier"
                                           value="{{ old('base_coin_value_multiplier', $coinType->exists ? showAmount($coinType->base_coin_value_multiplier, 18) : '1.00000000') }}"
                                           placeholder="@lang('E.g., 1000 if 1 ThisCoin = 1000 BaseCoin units')"
                                           @if(old('is_base_coin', $coinType->is_base_coin)) disabled @endif>
                                    <small class="form-text text-muted">@lang('Define how many units of the BASE coin one unit of THIS coin is worth. Ignored if "Is Base Coin" is checked (will be 1). Must be positive for non-base coins.')</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>@lang('Description')</label>
                            <textarea rows="3" class="form-control" name="description">{{ old('description', $coinType->description) }}</textarea>
                        </div>

                        <h5 class="mt-4 mb-2">@lang('Meta Data (Optional)')</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Icon Class')</label>
                                    <input type="text" class="form-control" name="meta[icon_class]" value="{{ old('meta.icon_class', $coinType->meta['icon_class'] ?? '') }}" placeholder="e.g., las la-coins">
                                     <small class="form-text text-muted">@lang('Example: <i class="las la-coins"></i>. Provide the full class name.')</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Display Color')</label>
                                    <div class="input-group">
                                        <span class="input-group-text form-control-color-addon"><i></i></span>
                                        <input type="text" class="form-control form-control-color" name="meta[display_color]" value="{{ old('meta.display_color', $coinType->meta['display_color'] ?? '#000000') }}">
                                    </div>
                                     <small class="form-text text-muted">@lang('Choose a color for display purposes.')</small>
                                </div>
                            </div>
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

@push('style-lib')
    {{-- Add color picker CSS if not already global --}}
@endpush

@push('script-lib')
    {{-- Add color picker JS if not already global --}}
@endpush

@push('script')
<script>
    (function($){
        "use strict";

        // Initialize color picker if you are using a library for it
        // Example for bootstrap-colorpicker:
        // $('.form-control-color').colorpicker({
        //     format: 'hex'
        // });
        // For native HTML5 color picker, the input-group-text can show the color
        $('.form-control-color').on('input', function() {
            $(this).closest('.input-group').find('.form-control-color-addon i').css('background-color', $(this).val());
        }).trigger('input');


        function toggleMultiplierState(isBase) {
            const multiplierInput = $('input[name="base_coin_value_multiplier"]');
            const multiplierLabel = $('label[for="base_coin_value_multiplier"], label.required-if-not-base'); // Adjust selector if needed

            if (isBase) {
                multiplierInput.val('1.000000000000000000').prop('disabled', true).prop('required', false);
                multiplierLabel.removeClass('required');
            } else {
                multiplierInput.prop('disabled', false).prop('required', true);
                multiplierLabel.addClass('required');
                // Do not clear the value if it was previously set, let user manage it or old() handle it
                 if(multiplierInput.val() === '1.000000000000000000' || multiplierInput.val() === '1'){
                    // If it's the default "1", prompt user to set a real value or clear it
                    // multiplierInput.val(''); // Or set a common non-1 default like 0.1
                 }
            }
        }

        $('#is_base_coin').on('change', function() {
            toggleMultiplierState($(this).is(':checked'));
        });

        // Initial state based on checkbox
        toggleMultiplierState($('#is_base_coin').is(':checked'));

    })(jQuery);
</script>
@endpush

@push('style')
<style>
    .form-control-color-addon i {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 1px solid #ced4da;
        border-radius: .25rem;
    }
    label.required::after, label.required-if-not-base.required::after {
        content: '*';
        color: red;
        margin-left: 2px;
    }
</style>
@endpush
