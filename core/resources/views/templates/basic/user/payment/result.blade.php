@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-10">
                <!-- Status Card -->
                <div class="card custom--card payment-result-card mb-4">
                    <div class="card-body text-center py-5">
                        <div class="payment-result-icon mb-4">
                            <i class="{{ $statusInfo['icon'] }} text-{{ $statusInfo['color'] }}" style="font-size: 80px;"></i>
                        </div>
                        <h3 class="text-{{ $statusInfo['color'] }} mb-3">{{ $statusInfo['title'] }}</h3>
                        <p class="text-muted mb-0">{{ $statusInfo['message'] }}</p>
                    </div>
                </div>

                <!-- Transaction Details Card -->
                <div class="card custom--card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="las la-file-invoice me-2"></i>@lang('جزئیات تراکنش')
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-borderless">
                                <tbody>
                                    <tr>
                                        <td class="text-muted">@lang('شماره تراکنش')</td>
                                        <td class="text-end fw-bold">
                                            <span class="badge badge--primary">{{ $deposit->trx }}</span>
                                        </td>
                                    </tr>

                                    @if($deposit->ref_id)
                                    <tr>
                                        <td class="text-muted">@lang('شماره پیگیری بانکی')</td>
                                        <td class="text-end fw-bold text-success">{{ $deposit->ref_id }}</td>
                                    </tr>
                                    @endif

                                    @if($deposit->card_pan)
                                    <tr>
                                        <td class="text-muted">@lang('شماره کارت')</td>
                                        <td class="text-end">
                                            <code dir="ltr">{{ $deposit->card_pan }}</code>
                                        </td>
                                    </tr>
                                    @endif

                                    <tr>
                                        <td class="text-muted">@lang('درگاه پرداخت')</td>
                                        <td class="text-end">
                                            <span class="text--base">{{ __(@$deposit->gateway->name ?? 'نامشخص') }}</span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="text-muted">@lang('تاریخ و زمان')</td>
                                        <td class="text-end">
                                            {{ showDateTime($deposit->created_at, 'Y/m/d H:i') }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="text-muted">@lang('وضعیت')</td>
                                        <td class="text-end">
                                            @php echo $deposit->statusBadge @endphp
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Amount Details Card -->
                <div class="card custom--card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="las la-money-bill-wave me-2"></i>@lang('جزئیات مبلغ')
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-borderless">
                                <tbody>
                                    <tr>
                                        <td class="text-muted">@lang('مبلغ اصلی')</td>
                                        <td class="text-end">{{ showAmount($deposit->amount) }}</td>
                                    </tr>

                                    <tr>
                                        <td class="text-muted">@lang('کارمزد')</td>
                                        <td class="text-end text-danger">+ {{ showAmount($deposit->charge) }}</td>
                                    </tr>

                                    <tr class="border-top">
                                        <td class="text-muted fw-bold">@lang('جمع کل') ({{ gs('cur_text') }})</td>
                                        <td class="text-end fw-bold">{{ showAmount($deposit->amount + $deposit->charge) }}</td>
                                    </tr>

                                    @if($deposit->exchange_rate_used && $deposit->exchange_rate_used > 0)
                                    <tr>
                                        <td class="text-muted">@lang('نرخ تبدیل')</td>
                                        <td class="text-end">
                                            <small>1 {{ gs('cur_text') }} = {{ number_format($deposit->exchange_rate_used, 0) }} {{ $deposit->method_currency }}</small>
                                        </td>
                                    </tr>
                                    @endif

                                    <tr class="bg-light">
                                        <td class="text-muted fw-bold">@lang('مبلغ پرداختی') ({{ $deposit->method_currency }})</td>
                                        <td class="text-end fw-bold text-success" style="font-size: 1.2em;">
                                            {{ number_format($deposit->final_amount, 0) }}
                                            @if($deposit->method_currency == 'IRT')
                                                @lang('تومان')
                                            @elseif($deposit->method_currency == 'IRR')
                                                @lang('ریال')
                                            @else
                                                {{ $deposit->method_currency }}
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    @if($status == 'success')
                        <a href="{{ route('user.lottery.purchased') }}" class="btn btn--base">
                            <i class="las la-ticket-alt me-1"></i>@lang('مشاهده بلیت‌های خریداری شده')
                        </a>
                    @else
                        <a href="{{ route('user.deposit.index') }}" class="btn btn--base">
                            <i class="las la-redo-alt me-1"></i>@lang('تلاش مجدد')
                        </a>
                    @endif
                    
                    <a href="{{ route('user.deposit.history') }}" class="btn btn--secondary">
                        <i class="las la-history me-1"></i>@lang('تاریخچه پرداخت‌ها')
                    </a>

                    <a href="{{ route('home') }}" class="btn btn-outline-secondary">
                        <i class="las la-home me-1"></i>@lang('صفحه اصلی')
                    </a>
                </div>

                @if($status == 'success' && $deposit->ref_id)
                <!-- Print Button -->
                <div class="text-center mt-4">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">
                        <i class="las la-print me-1"></i>@lang('چاپ رسید')
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('style')
<style>
    .payment-result-card {
        border-radius: 15px;
        overflow: hidden;
    }
    
    .payment-result-icon {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .table-borderless td {
        padding: 12px 8px;
    }
    
    @media print {
        .btn, button, .sidebar, header, footer, nav {
            display: none !important;
        }
        
        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
        
        body {
            background: white !important;
        }
    }
</style>
@endpush
