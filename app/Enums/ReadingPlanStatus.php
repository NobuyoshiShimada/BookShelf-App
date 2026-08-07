<?php

namespace App\Enums;

enum ReadingPlanStatus: string
{
    case Unread = 'unread';
    case Reading = 'reading';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Unread => '未読',
            self::Reading => '読書中',
            self::Completed => '読了',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Unread => 'bg-gray-100 text-gray-800',
            self::Reading => 'bg-yellow-100 text-yellow-800',
            self::Completed => 'bg-green-100 text-green-800',
        };
    }
}
