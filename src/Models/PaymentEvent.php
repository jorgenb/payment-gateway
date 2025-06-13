<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Models;

use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $payment_id
 * @property PaymentStatus $event
 * @property array $payload
 * @property-read Payment $payment
 */
class PaymentEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'payment_id',
        'event',
        'payload',
    ];

    protected $casts = [
        'event' => PaymentStatus::class,
        'payload' => 'array',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
