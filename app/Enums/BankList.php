<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\InteractsWithEnum;
use App\Contracts\Enum;

enum BankList: string implements Enum
{
    use InteractsWithEnum;

    case MAYBANK2U = 'maybank2u';
    case PUBLICBANK = 'publicbank';
    case CIMBCLICKS = 'cimbclicks';
    case HLB = 'hlb';
    case RHB = 'rhb';
    case DEUTSCHEBANK = 'deutschebank';
    case STANDARDCHARTERED = 'standardchartered';

    public function label(): string
    {
        return $this->displayName();
    }

    public function description(): string
    {
        return match ($this) {
            self::MAYBANK2U => __('Maybank2u - Maybank Internet Banking'),
            self::PUBLICBANK => __('Public Bank - Public Bank Internet Banking'),
            self::CIMBCLICKS => __('CIMB Clicks - CIMB Internet Banking'),
            self::HLB => __('Hong Leong Bank - Hong Leong Internet Banking'),
            self::RHB => __('RHB - RHB Internet Banking'),
            self::DEUTSCHEBANK => __('Deutsche Bank - Deutsche Bank Internet Banking'),
            self::STANDARDCHARTERED => __('Standard Chartered - Standard Chartered Internet Banking'),
        };
    }

    /**
     * Get the display name for the bank.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::MAYBANK2U => 'Maybank2u',
            self::PUBLICBANK => 'Public Bank',
            self::CIMBCLICKS => 'CIMB Clicks',
            self::HLB => 'Hong Leong Bank',
            self::RHB => 'RHB',
            self::DEUTSCHEBANK => 'Deutsche Bank',
            self::STANDARDCHARTERED => 'Standard Chartered',
        };
    }
}
