<?php

namespace Bilberry\PaymentGateway\Commands;

use Illuminate\Console\Command;

class PaymentGatewayCommand extends Command
{
    public $signature = 'payment-gateway';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
