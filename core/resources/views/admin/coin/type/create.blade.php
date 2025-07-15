@extends('admin.layouts.app')

@section('panel')
    @include('admin.coin.type.form', ['coinType' => new \App\Models\CoinType()])
@endsection
