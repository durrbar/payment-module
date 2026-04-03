<?php

declare(strict_types=1);

namespace Modules\Payment\Drivers\Enums;

enum DriverTransactionStatus: string
{
    case Success = 'success';
    case Verified = 'verified';
    case Refunded = 'refunded';
    case Error = 'error';
}
