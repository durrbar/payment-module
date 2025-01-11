<?php

namespace Modules\Payment\Services;

use Modules\Payment\Models\Invoice;

class InvoiceService
{
    public function generateInvoice(array $data): Invoice
    {
        return Invoice::create([
            'user_id' => $data['user_id'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'status' => 'paid',
        ]);
    }
}
