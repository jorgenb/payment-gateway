<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Models;

use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Bilberry\PaymentGateway\Database\Factories\PaymentRefundFactory;

/**
 * @property string $id
 * @property string $payment_id
 * @property int $amount_minor
 * @property string $currency
 * @property string $status
 * @property string|null $external_refund_id
 * @property array|null $metadata
 * @property-read Payment $payment
 */
class PaymentRefund extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PaymentRefundFactory
    {
        return PaymentRefundFactory::new();
    }

    protected $appends = [
        'amount',
    ];

    protected $fillable = [
        'payment_id',
        'amount_minor',
        'currency',
        'status',
        'external_refund_id',
        'metadata',
    ];

    protected $casts = [
        'metadata'            => 'array',
        'amount_minor' => 'integer',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Returns the refund amount as a Money object.
     *
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     */
    public function getAmountAttribute(): Money
    {
        return Money::ofMinor($this->amount_minor, $this->currency);
    }
}
