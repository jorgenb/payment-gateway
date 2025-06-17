<?php

declare(strict_types=1);

namespace Bilberry\PaymentGateway\Models;

use Bilberry\PaymentGateway\Database\Factories\FakePayableFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class used as a placeholder model to satisfy polymorphic relationships
 * in the Payments module without relying on external modules.
 *
 * This model does not persist any meaningful data and assumes the
 * existence of a backing 'fake_payables' table, or can be faked in
 * tests by stubbing out Eloquent behavior.
 */
class FakePayable extends Model
{
    use HasFactory;
    use HasUuids;

    protected static function newFactory(): FakePayableFactory
    {
        return FakePayableFactory::new();
    }

    protected $table = 'fake_payables';
    protected $fillable = ['*'];
    public $timestamps = false;
    protected $casts = [
        'data' => 'array',
    ];
}
