<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

/**
 * Legacy order-payment lifecycle statuses persisted in `payments.status`.
 *
 * Keep this enum separate from PaymentStatus (gateway workflow/status bus).
 */
enum PaymentStatusOld: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELED = 'canceled';
    case SUCCESSFUL = 'successful';
}
