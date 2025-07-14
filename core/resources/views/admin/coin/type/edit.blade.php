@extends('admin.layouts.app')

@section('panel')
    @include('admin.coin.type.form', ['coinType' => $coinType])
@endsection
