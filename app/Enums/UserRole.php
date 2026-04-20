<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin     = 'admin';
    case Teacher   = 'teacher';
    case Student   = 'student';
    case Parent    = 'parent';
    case Tutor     = 'tutor';
    case Accountant = 'accountant';
    case Manager   = 'manager';

    public function label(): string
    {
        return match($this) {
            self::Admin      => __('roles.admin'),
            self::Teacher    => __('roles.teacher'),
            self::Student    => __('roles.student'),
            self::Parent     => __('roles.parent'),
            self::Tutor      => __('roles.tutor'),
            self::Accountant => __('roles.accountant'),
            self::Manager    => __('roles.manager'),
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Admin      => 'badge-error',
            self::Teacher    => 'badge-primary',
            self::Student    => 'badge-success',
            self::Parent     => 'badge-info',
            self::Tutor      => 'badge-secondary',
            self::Accountant => 'badge-warning',
            self::Manager    => 'badge-neutral',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Admin      => 'o-shield-check',
            self::Teacher    => 'o-academic-cap',
            self::Student    => 'o-user',
            self::Parent     => 'o-users',
            self::Tutor      => 'o-light-bulb',
            self::Accountant => 'o-calculator',
            self::Manager    => 'o-briefcase',
        };
    }

    public static function options(): array
    {
        return array_map(fn($case) => [
            'id'    => $case->value,
            'name'  => $case->label(),
            'icon'  => $case->icon(),
        ], self::cases());
    }
}
