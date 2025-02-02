<?php

namespace Modules\Payment\Services;

class MultiCurrencyService
{
    protected $rates;

    public function __construct()
    {
        $this->rates = [
            'USD' => 1,
            'EUR' => 0.85,
            'BDT' => 105,
        ];
    }

    public function convertToBaseCurrency(float $amount, string $currency): float
    {
        return $amount / $this->rates[$currency];
    }

    public function convertFromBaseCurrency(float $amount, string $currency): float
    {
        return $amount * $this->rates[$currency];
    }
}
