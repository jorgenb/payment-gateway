<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Enums;

enum PaymentType: string
{
    /**
     * A single, non-repeating payment.
     */
    case ONE_TIME = 'one_time';

    /**
     * A recurring or subscription-based payment.
     */
    case RECURRING = 'recurring';
}
