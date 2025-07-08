<?php

namespace App\Lib\PaymentGateway;

use App\Lib\PaymentGateway\Gateways\ZarinpalGateway;
use App\Models\Gateway as GatewayModel; // For getAvailableGateways
use App\Constants\Status;              // For getAvailableGateways

// Import other gateway classes here as they are added, e.g.:
// use App\Lib\PaymentGateway\Gateways\PaypingGateway;

class PaymentManager
{
    /**
     * Get an instance of the specified payment gateway.
     *
     * @param string $gatewayCode The alias or code of the gateway (e.g., 'zarinpal').
     * @return PaymentGatewayInterface|null Returns a gateway instance or null if not found/supported or if the gateway is disabled.
     */
    public static function gateway(string $gatewayCode): ?PaymentGatewayInterface
    {
        $normalizedCode = strtolower(trim($gatewayCode));
        $gatewayInstance = null;

        // First, check if the gateway definition exists and is active in the database
        $gatewayModel = GatewayModel::where('code', $normalizedCode)->orWhere('alias', $normalizedCode)->first();

        if (!$gatewayModel || $gatewayModel->status == Status::DISABLE) {
            logger()->warning("Payment gateway '{$gatewayCode}' not found or is disabled in database.");
            return null;
        }

        switch ($normalizedCode) {
            case 'zarinpal':
            case $gatewayModel->alias == 'zarinpal': // Allow fetching by alias too
                // The ZarinpalGateway constructor loads its specific configuration
                // by querying the Gateway model with its code 'zarinpal'.
                // We pass the already fetched model to avoid a second DB query.
                $gatewayInstance = new ZarinpalGateway($gatewayModel);
                break;

            // case 'payping':
            // case $gatewayModel->alias == 'payping':
            //     $gatewayInstance = new PaypingGateway($gatewayModel);
            //     break;

            // Add other gateways here
            // case 'mellat':
            //     $gatewayInstance = new MellatGateway($gatewayModel);
            //     break;

            default:
                logger()->warning("Unsupported payment gateway requested or switch case not updated: {$gatewayCode}");
                return null;
        }

        // Final check: ensure the gateway loaded its config successfully (e.g., merchant ID is present)
        // This is a basic check; specific gateways might have more complex config validation.
        $config = $gatewayInstance->getConfig();
        if (isset($config['success']) && $config['success'] === false) {
            logger()->error("Gateway '{$gatewayCode}' failed to load its configuration: " . ($config['message'] ?? 'Unknown error'));
            return null;
        }
         if ($normalizedCode == 'zarinpal' && (empty($config['merchant_id']))) {
            logger()->error("Zarinpal gateway '{$gatewayCode}' is missing merchant_id in its configuration.");
            return null;
        }


        return $gatewayInstance;
    }

    /**
     * Get a list of available (and active) gateways, suitable for a selection dropdown.
     *
     * @return array
     */
    public static function getAvailableGateways(): array
    {
        $activeGateways = GatewayModel::where('status', Status::ENABLE)
                                    ->where('code', '<', 1000) // Only automatic gateways
                                    ->orderBy('name')
                                    ->get();

        $formattedGateways = [];
        foreach ($activeGateways as $gw) {
            // Attempt to instantiate to check if basic config is somewhat valid (e.g., Zarinpal has merchant_id)
            // This is a bit heavy but ensures we don't list gateways that are technically active but misconfigured for use.
            $instance = self::gateway($gw->code);
            if ($instance) {
                 $config = $instance->getConfig();
                 // Check if the gateway is configured enough to be listed
                 $isConfigured = true; // Assume configured unless a specific check fails
                 if ($gw->code == 'zarinpal' && empty($config['merchant_id'])) {
                     $isConfigured = false;
                 }
                 // Add similar checks for other gateways if they have mandatory config fields

                if ($isConfigured) {
                    $formattedGateways[] = [
                        'code' => $gw->code,
                        'name' => $gw->name,
                        'alias' => $gw->alias,
                        'image' => $gw->image ? getImage(getFilePath('gateway') . '/' . $gw->image, getFileSize('gateway')) : null,
                        'note' => $gw->extra->currency_note ?? null, // Example: display a note like "پرداخت به ریال"
                    ];
                }
            }
        }
        return $formattedGateways;
    }
}
