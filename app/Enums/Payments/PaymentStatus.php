<?php

namespace App\Enums\Payments;

enum PaymentStatus: int
{
    case INITIAL   = 0;
    case COMPLETED = 1;
    case PENDING   = 2;
    case FAILED    = 3;
    case CANCELED  = 4;
    case REFUNDED  = 5;

    /**
     * Human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::INITIAL   => 'Initial',
            self::PENDING   => 'Pending',
            self::COMPLETED => 'Completed',
            self::FAILED    => 'Failed',
            self::CANCELED  => 'Canceled',
            self::REFUNDED  => 'Refunded',
        };
    }

    /**
     * Return all statuses as array (useful for dropdowns/admin)
     */
    public static function all(): array
    {
        return array_map(
            fn ($case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}
