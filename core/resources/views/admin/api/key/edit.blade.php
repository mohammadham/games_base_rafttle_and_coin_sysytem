@extends('admin.layouts.app')

@section('panel')
    @include('admin.api.key.form', ['apiKey' => $apiKey, 'users' => $users, 'availablePermissions' => $availablePermissions])
@endsection
