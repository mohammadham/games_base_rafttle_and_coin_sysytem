@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Date')</th>
                                    <th>@lang('API Key')</th>
                                    <th>@lang('Endpoint')</th>
                                    <th>@lang('IP Address')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td>{{ showDateTime($log->created_at) }}</td>
                                        <td>{{ $log->apiKey->name ?? 'N/A' }}</td>
                                        <td>{{ $log->endpoint_url }}</td>
                                        <td>{{ $log->ip_address }}</td>
                                        <td>
                                            @if ($log->response_http_code >= 200 && $log->response_http_code < 300)
                                                <span class="badge badge--success">{{ $log->response_http_code }}</span>
                                            @else
                                                <span class="badge badge--danger">{{ $log->response_http_code }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline--primary detailBtn"
                                                    data-log='{{ json_encode($log) }}'>
                                                <i class="la la-eye"></i> @lang('Detail')
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($logs->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($logs) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div id="detailModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('API Log Detail')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            @lang('Request Payload')
                            <pre class="mb-0"></pre>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            @lang('Response Payload')
                            <pre class="mb-0"></pre>
                        </li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--dark" data-bs-dismiss="modal">@lang('Close')</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function($){
            'use strict';
            $('.detailBtn').on('click', function() {
                var modal = $('#detailModal');
                var log = $(this).data('log');
                modal.find('.modal-body pre').first().text(JSON.stringify(JSON.parse(log.request_payload), null, 4));
                modal.find('.modal-body pre').last().text(JSON.stringify(JSON.parse(log.response_payload), null, 4));
                modal.modal('show');
            });
        })(jQuery);
    </script>
@endpush
