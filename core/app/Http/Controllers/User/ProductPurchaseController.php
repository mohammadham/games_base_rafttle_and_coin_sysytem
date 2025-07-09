<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\CoinType;
use App\Models\UserCoinBalance;
use App\Models\Order; // Assuming a general Order model or create ProductOrder
use App\Models\OrderItem; // Assuming OrderItem model for products in an order
use App\Constants\Status;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductPurchaseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth'); // User must be logged in
        $this->middleware('registration.complete'); // Ensure user profile is complete
        $this->middleware('check.status'); // Ensure user account is active
    }

    public function purchaseWithCoins(Request $request, $slug)
    {
        $request->validate([
            'quantity' => 'sometimes|required|integer|min:1',
        ]);

        $user = Auth::user();
        $product = Product::where('slug', $slug)->active()->first();

        if (!$product) {
            $notify[] = ['error', trans('Product not found or is currently unavailable.')];
            return back()->withNotify($notify);
        }

        $quantity = $request->input('quantity', 1);

        // Check stock
        if ($product->stock != -1 && $product->stock < $quantity) {
            $notify[] = ['error', trans('Not enough stock available for this product. Only :stock_left units left.', ['stock_left' => $product->stock])];
            return back()->withNotify($notify);
        }

        $baseCoin = CoinType::getBaseCoin();
        if (!$baseCoin) {
            $notify[] = ['error', trans('Base coin is not configured. Coin payment is unavailable.')];
            return back()->withNotify($notify);
        }

        // Product price is already in base coin
        $pricePerUnitInBaseCoin = (float) $product->price;
        $totalCostInBaseCoin = $pricePerUnitInBaseCoin * $quantity;

        $userBaseCoinBalanceInstance = UserCoinBalance::getOrCreateBalance($user->id, $baseCoin->id);
        $currentUserBalance = (float) $userBaseCoinBalanceInstance->balance;

        if ($currentUserBalance < $totalCostInBaseCoin) {
            $notify[] = ['error', trans('Insufficient :coin_name balance. Required: :required, Available: :available', [
                'coin_name' => __($baseCoin->name),
                'required' => showAmount($totalCostInBaseCoin, $baseCoin->meta['precision'] ?? 8) . ' ' . $baseCoin->symbol,
                'available' => showAmount($currentUserBalance, $baseCoin->meta['precision'] ?? 8) . ' ' . $baseCoin->symbol,
            ])];
            return back()->withNotify($notify);
        }

        $orderTrx = getTrx(); // Unique transaction ID for the order

        try {
            DB::beginTransaction();

            // 1. Debit coins from user's base coin balance
            $coinTransactionParams = [
                'trx' => $orderTrx, // Use order TRX for coin transaction as well for linkage
                'details' => trans(':quantity x :product_name purchased with :coin_name.', [
                    'quantity' => $quantity,
                    'product_name' => __($product->name),
                    'coin_name' => __($baseCoin->name)
                ]),
                // 'related_transactionable_id' => will be order_id after order is created
                // 'related_transactionable_type' => Order::class,
            ];
            $coinTransaction = $userBaseCoinBalanceInstance->debit($totalCostInBaseCoin, 'product_purchase_with_coin', $coinTransactionParams);

            // 2. Create Order Record (assuming a simple Order and OrderItem structure)
            // You might need a more complex order system depending on requirements (shipping, etc.)
            $order = new Order(); // Replace with your actual Order model if different
            $order->user_id = $user->id;
            $order->trx = $orderTrx; // Same TRX as coin transaction for easy lookup
            $order->total_amount = $totalCostInBaseCoin; // Store order total in base coin value
            $order->status = Status::ORDER_PAID; // Or a processing status
            $order->payment_method = 'coin_payment';
            $order->payment_via = $baseCoin->code; // Store which coin was used
            // Add other relevant order fields: shipping_address_id, billing_address_id etc.
            $order->save();

            // 3. Create Order Item Record
            $orderItem = new OrderItem(); // Replace with your OrderItem model
            $orderItem->order_id = $order->id;
            $orderItem->product_id = $product->id;
            $orderItem->quantity = $quantity;
            $orderItem->price_per_unit_base_coin = $pricePerUnitInBaseCoin; // Ensure field name matches model
            $orderItem->total_price_base_coin = $totalCostInBaseCoin;    // Ensure field name matches model
            $orderItem->product_name_snapshot = $product->name;
            $orderItem->product_image_snapshot = $product->image; // Store main image filename (path is constructed by accessor)
            // $orderItem->attributes_snapshot = $request->input('attributes', []); // If product has selectable attributes
            $orderItem->save();

            // Update CoinTransaction with related order ID
            $coinTransaction->related_transactionable_id = $order->id;
            $coinTransaction->related_transactionable_type = get_class($order); // e.g., App\Models\Order
            $coinTransaction->save();


            // 4. Decrease product stock (if not unlimited)
            if ($product->stock != -1) {
                $product->stock -= $quantity;
                $product->save();
            }

            // 5. Handle digital good delivery if applicable
            if ($product->is_digital && $product->digital_good_delivery_info) {
                // This is where you might trigger an email with download links, generate a key, etc.
                // For now, just add a note to the success message.
                session()->flash('digital_good_info', $product->digital_good_delivery_info);
            }

            DB::commit();

            $notify[] = ['success', trans('Product purchased successfully using your coin balance!')];
            // Redirect to an order confirmation page or user's order history
            return redirect()->route('user.orders.detail', $order->id)->withNotify($notify); // Assuming such a route exists

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("ProductCoinPurchase Error for product {$product->slug}, user {$user->id}: " . $e->getMessage());
            $notify[] = ['error', trans('An error occurred while processing your purchase: ') . $e->getMessage()];
            return back()->withNotify($notify);
        }
    }
}

/**
 * Note: This controller assumes existence of Order and OrderItem models.
 * If they don't exist, they need to be created with appropriate migrations:
 *
 * orders table:
 * - id, user_id, trx (unique), total_amount (in base coin value), status (e.g., pending, paid, processing, shipped, completed, cancelled),
 * - payment_method (e.g., 'coin_payment', 'gateway_zarinpal'), payment_via (e.g. 'MAIN_COIN_CODE', 'ZARINPAL_TRX_ID')
 * - shipping_address_id, billing_address_id, notes, timestamps etc.
 *
 * order_items table:
 * - id, order_id, product_id, quantity, price_per_unit (in base coin), total_price (in base coin),
 * - product_name_snapshot, product_sku_snapshot (to keep record of product details at time of purchase)
 * - timestamps etc.
 */
