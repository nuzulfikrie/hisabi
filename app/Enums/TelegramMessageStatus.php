<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\InteractsWithEnum;
use App\Contracts\Enum;

enum TelegramMessageStatus: string implements Enum
{
    use InteractsWithEnum;

    case PENDING = 'pending';
    case PROCESSED = 'processed';
    case FAILED = 'failed';
    case IGNORED = 'ignored';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('Pending'),
            self::PROCESSED => __('Processed'),
            self::FAILED => __('Failed'),
            self::IGNORED => __('Ignored'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PENDING => __('Message is waiting to be processed'),
            self::PROCESSED => __('Message has been successfully processed'),
            self::FAILED => __('Message processing failed'),
            self::IGNORED => __('Message was ignored (e.g., command)'),
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PROCESSED => 'success',
            self::FAILED => 'danger',
            self::IGNORED => 'secondary',
        };
    }
}
