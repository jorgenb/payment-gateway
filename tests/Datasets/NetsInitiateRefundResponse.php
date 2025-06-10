<?php

declare(strict_types=1);

dataset('nets initiate refund response', fn () => [
    'refundId' => Illuminate\Support\Str::uuid()->toString(),
]);
