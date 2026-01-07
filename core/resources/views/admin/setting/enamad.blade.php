@extends('admin.layouts.app')
@section('panel')
<div class="row mb-none-30">
    <div class="col-lg-12">
        <div class="card">
            <form method="post">
                @csrf
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-3">
                      <div class="form-group">
                        <label>@lang('Status')</label>
                        <input type="checkbox" data-width="100%" data-height="50" data-onstyle="-success" data-offstyle="-danger" data-bs-toggle="toggle" data-on="@lang('Enable')" data-off="@lang('Disabled')" @if(gs()->enamad_status) checked @endif name="enamad_status">
                      </div>
                    </div>
                  </div>
                    <div class="form-group">
                      <label>@lang('Enamad Script')</label>
                        <textarea class="form-control" rows="10" name="enamad_script">{{ gs()->enamad_script }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
