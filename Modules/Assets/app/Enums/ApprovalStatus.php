<?php

namespace Modules\Assets\app\Enums;

enum ApprovalStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::PENDING => 'ri-time-line',
            self::APPROVED => 'ri-check-line',
            self::REJECTED => 'ri-close-line',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::PENDING => 'Waiting for response',
            self::APPROVED => 'Approved and confirmed',
            self::REJECTED => 'Rejected with reason',
        };
    }

    /**
     * Get all cases as array for forms/dropdowns
     */
    public static function options(): array
    {
        return [
            self::PENDING->value => self::PENDING->label(),
            self::APPROVED->value => self::APPROVED->label(),
            self::REJECTED->value => self::REJECTED->label(),
        ];
    }

    /**
     * Get cases for user selection (excluding pending)
     */
    public static function userOptions(): array
    {
        return [
            self::APPROVED->value => self::APPROVED->label(),
            self::REJECTED->value => self::REJECTED->label(),
        ];
    }
}