<?php

declare(strict_types=1);

namespace Modules\Payment\Payments;

use Modules\Settings\Models\Settings;

abstract class Base
{
    public $currency;

    public function __construct()
    {
        $settings = Settings::first();
        $this->currency = $settings->options['currency'];
    }
}
