<?php

declare(strict_types=1);

dataset('nets initiate payment response', fn () => [
    'paymentId' => \Illuminate\Support\Str::uuid()->toString(),
]);
