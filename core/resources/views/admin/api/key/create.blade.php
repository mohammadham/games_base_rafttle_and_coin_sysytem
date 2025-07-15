@extends('admin.layouts.app')

@section('panel')
    @include('admin.api.key.form', ['apiKey' => new \App\Models\ApiKey(), 'users' => \App\Models\User::all(), 'availablePermissions' => $availablePermissions])
@endsection
