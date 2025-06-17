<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Models;

use Bilberry\PaymentGateway\Database\Factories\PaymentFactory;
use Bilberry\PaymentGateway\Enums\PayableType;
use Bilberry\PaymentGateway\Enums\PaymentProvider;
use Bilberry\PaymentGateway\Enums\PaymentStatus;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RuntimeException;

/**
 * @property string $id
 * @property string $payable_id
 * @property PayableType $payable_type
 * @property PaymentProvider $provider
 * @property string $type
 * @property int $amount_minor
 * @property string $currency
 * @property PaymentStatus|null $status
 * @property string|null $external_id
 * @property string|null $external_charge_id
 * @property string|null $reference
 * @property CarbonImmutable|null $capture_at
 * @property array|null $metadata
 * @property bool|null $auto_capture
 * @property array|null $payment_config Stores the configuration object (API keys, merchant account, webhook secret, etc.) used for this payment, to guarantee callbacks can always be processed.
 * @property string|null $context_id Arbitrary context reference for config resolution, set by the consuming application.
 * @property-read Money $total_charged_amount
 * @property-read Money $total_refunded_amount
 * @property-read Collection|PaymentEvent[] $events
 * @property-read Collection|PaymentRefund[] $refunds
 */
class Payment extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }

    protected $fillable = [
        'payable_id',
        'payable_type',
        'provider',
        'type',
        'amount_minor',
        'currency',
        'status',
        'external_id',
        'external_charge_id',
        'reference',
        'capture_at',
        'metadata',
        'auto_capture',
        'context_id',
    ];

    protected $appends = [
        'amount',
        'total_charged_amount',
        'total_refunded_amount',
        'total_pending_refunded_amount',
    ];

    protected $casts = [
        'payable_type' => PayableType::class,
        'capture_at' => 'immutable_datetime',
        'provider' => PaymentProvider::class,
        'status' => PaymentStatus::class,
        'metadata' => 'array',
        'amount_minor' => 'integer',
        'type' => 'string',
        'auto_capture' => 'boolean',
        'context_id' => 'string',
    ];

    protected $hidden = [
        'payable_type'
    ];

    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class);
    }

    /**
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     */
    public function getAmountMinorMoneyAttribute(): Money
    {
        return Money::ofMinor($this->amount_minor, $this->currency);
    }

    /**
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     */
    public function getTotalChargedAmountAttribute(): Money
    {
        if ($this->currency === null) {
            throw new RuntimeException('Currency must be set before accessing total_charged_amount.');
        }

        $amount = $this->status === PaymentStatus::CHARGED
            ? $this->amount_minor
            : 0;

        return Money::ofMinor($amount, $this->currency);
    }

    /**
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     */
    public function getTotalRefundedAmountAttribute(): Money
    {
        return Money::ofMinor(
            $this->refunds->where('status', PaymentStatus::REFUNDED)->sum('amount_minor'),
            $this->currency
        );
    }

    /**
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     */
    public function getTotalPendingRefundedAmountAttribute(): Money
    {
        return Money::ofMinor(
            $this->refunds->where('status', PaymentStatus::REFUND_INITIATED)->sum('amount_minor'),
            $this->currency
        );
    }

    /**
     * Get the parent payable model (morph-to relationship).
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Returns the correct capture configuration value per provider.
     *
     * For Adyen: returns int|null for captureDelayHours.
     * For Stripe: returns 'manual' or 'automatic'.
     * For Nets: always returns null (no support for capture config).
     *
     * If `capture_at` is set, auto-capture is explicitly disabled.
     */
    public function getCaptureConfigurationForProvider(): string|int|null
    {
        $manual = $this->capture_at !== null || $this->auto_capture === false;

        return match ($this->provider) {
            PaymentProvider::STRIPE => $manual ? 'manual' : 'automatic',
            PaymentProvider::ADYEN => $manual ? null : 0,
            PaymentProvider::NETS => null,
        };
    }

    /**
     * Returns the payment amount as a Money object.
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
