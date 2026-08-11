<?php

namespace App\Actions\Admin\Order;

use App\DTOs\Admin\Order\RefundOrderDTO;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Exceptions\BaseApiException;
use App\Models\Order;
use App\Repositories\Admin\Order\AdminOrderRepository;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class RefundOrderAction
{
    public function __construct(
        private AdminOrderRepository $repository,
        private StripeClient $stripe,
    ) {}

    public function execute(RefundOrderDTO $dto): Order
    {
        $order = $this->repository->findInStore($dto->orderId, $dto->storeId);
        
        if (empty($order->payment_intent_id)) {
            throw new BaseApiException(
                message: 'Order has no payment intent to refund',
                statusCode: 422,
                errorCode: \App\Enums\ErrorCode::VAL_001->value
            );
        }

        if ($order->payment_status !== PaymentStatusEnum::PAID) {
            throw new BaseApiException(
                message: 'Only paid orders can be refunded',
                statusCode: 422,
                errorCode: \App\Enums\ErrorCode::VAL_001->value
            );
        }

        // Process Stripe refund
        try {
            $refundParams = [
                'payment_intent' => $order->payment_intent_id,
            ];

            // If store had Stripe Connect enabled, reverse transfer and refund application fee
            if ($order->store->canReceivePayments()) {
                $refundParams['reverse_transfer'] = true;
                $refundParams['refund_application_fee'] = true;
            }

            $refund = $this->stripe->refunds->create($refundParams);

            Log::info('Stripe refund processed', [
                'order_id' => $order->id,
                'payment_intent_id' => $order->payment_intent_id,
                'refund_id' => $refund->id,
                'reverse_transfer' => $refundParams['reverse_transfer'] ?? false,
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe refund failed', [
                'order_id' => $order->id,
                'payment_intent_id' => $order->payment_intent_id,
                'error' => $e->getMessage(),
            ]);

            throw new BaseApiException(
                message: 'Refund failed: ' . $e->getMessage(),
                statusCode: 500,
                errorCode: \App\Enums\ErrorCode::SYS_001->value
            );
        }

        // Update order status
        $order->update([
            'status' => OrderStatusEnum::REFUNDED,
            'payment_status' => PaymentStatusEnum::REFUNDED,
        ]);
        
        return $order->fresh();
    }
}

