<?php

namespace App\Lib\PaymentGateway;

use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Get the name of the payment gateway.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Initiate a payment request.
     *
     * @param float $amount The amount to be charged.
     * @param string $currency The currency of the amount (e.g., "IRR", "IRT").
     * @param string $description A description for the payment.
     * @param string $callbackUrl The URL to redirect to after payment attempt.
     * @param array $additionalData Additional data specific to the gateway or transaction (e.g., user_id, order_id, mobile, email).
     * @return array Should return an array containing redirection URL and any other necessary data,
     *               or an error message/status.
     *               Example success: ['success' => true, 'redirect_url' => 'https://gateway.com/pay/xxx', 'payment_id' => 'some_internal_id_or_authority']
     *               Example error:   ['success' => false, 'message' => 'Error message from gateway or validation']
     */
    public function requestPayment(float $amount, string $currency, string $description, string $callbackUrl, array $additionalData = []): array;

    /**
     * Verify a payment after the user is redirected back from the gateway.
     *
     * @param Request $request The request object containing data from the gateway (e.g., query parameters).
     * @param array $storedTransactionData Data stored locally about the transaction initiated earlier (e.g., amount, payment_id/authority).
     * @return array Should return an array containing payment status, transaction ID from the gateway, and any other relevant data.
     *               Example success: ['success' => true, 'transaction_id' => 'gateway_trx_id_123', 'message' => 'Payment successful', 'card_pan' => '6037...1234 (optional)']
     *               Example error:   ['success' => false, 'message' => 'Payment failed, cancelled, or verification error', 'transaction_id' => null]
     */
    public function verifyPayment(Request $request, array $storedTransactionData): array;

    /**
     * Get any specific configuration needed for this gateway.
     * This might include API keys, merchant codes, mode (sandbox/live), etc.
     * These should typically be loaded from config files or general settings in the database.
     *
     * @return array
     */
    public function getConfig(): array;
}
