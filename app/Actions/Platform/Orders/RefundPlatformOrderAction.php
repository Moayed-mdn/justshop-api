<?php

declare(strict_types=1);

namespace App\Actions\Platform\Orders;

use App\DTOs\Platform\Orders\RefundPlatformOrderDTO;
use App\Enums\ErrorCode;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Exceptions\BaseApiException;
use App\Models\Order;
use App\Repositories\Platform\Order\PlatformOrderRepository;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class RefundPlatformOrderAction
{
    public function __construct(
        private PlatformOrderRepository $repository,
        private StripeClient $stripe,
    ) {}

    public function execute(RefundPlatformOrderDTO $dto): Order
    {
        $order = $this->repository->find($dto->orderId);

        if (empty($order->payment_intent_id)) {
            throw new BaseApiException(
                message: 'Order has no payment intent to refund',
                statusCode: 422,
                errorCode: ErrorCode::VAL_001->value
            );
        }

        if ($order->payment_status !== PaymentStatusEnum::PAID) {
            throw new BaseApiException(
                message: 'Only paid orders can be refunded',
                statusCode: 422,
                errorCode: ErrorCode::VAL_001->value
            );
        }

        try {
            $refundParams = [
                'payment_intent' => $order->payment_intent_id,
            ];

            if ($dto->amount !== null) {
                $refundParams['amount'] = (int) round($dto->amount * 100);
            }

            if ($dto->reason !== null) {
                $refundParams['metadata'] = ['reason' => $dto->reason];
            }

            if ($order->store->canReceivePayments()) {
                $refundParams['reverse_transfer'] = true;
                $refundParams['refund_application_fee'] = true;
            }

            $refund = $this->stripe->refunds->create($refundParams);

            Log::info('Platform Stripe refund processed', [
                'order_id' => $order->id,
                'store_id' => $order->store_id,
                'payment_intent_id' => $order->payment_intent_id,
                'refund_id' => $refund->id,
                'amount' => $dto->amount,
                'reason' => $dto->reason,
                'reverse_transfer' => $refundParams['reverse_transfer'] ?? false,
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Platform Stripe refund failed', [
                'order_id' => $order->id,
                'store_id' => $order->store_id,
                'payment_intent_id' => $order->payment_intent_id,
                'error' => $e->getMessage(),
            ]);

            throw new BaseApiException(
                message: 'Refund failed: ' . $e->getMessage(),
                statusCode: 500,
                errorCode: ErrorCode::SYS_001->value
            );
        }

        $order->update([
            'status' => OrderStatusEnum::REFUNDED,
            'payment_status' => PaymentStatusEnum::REFUNDED,
        ]);

        return $order->fresh();
    }
}
