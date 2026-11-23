<?php

namespace App\Support;

final class Rbac
{
    public const SUPERADMIN = 'superadmin';
    public const ADMIN = 'admin';
    public const STAFF = 'staff';
    public const MAINTENANCE = 'maintenance';
    public const STUDENT = 'student';
    public const FACULTY = 'faculty';
    public const VISITOR = 'visitor';

    public const PERSONNEL_ROLES = [self::SUPERADMIN, self::ADMIN, self::STAFF, self::MAINTENANCE];

    public const MEMBER_ROLES = [self::STUDENT, self::FACULTY];

    public const PERMISSIONS = [
        'attendance.time',
        'attendance.view',
        'books.view',
        'books.manage',
        'books.update',
        'books.delete',
        'books.qr',
        'inventory.audit',
        'circulation.process',
        'borrow.self',
        'history.own',
        'qr.own',
        'profile.update',
        'members.manage',
        'members.delete',
        'personnel.manage',
        'personnel.delete',
        'schedules.view-own',
        'schedules.manage-staff',
        'schedules.manage-admin',
        'roles.manage',
        'reports.generate',
        'audit.view',
        'system.maintain',
    ];

    /** @return array<string, list<string>> */
    public static function rolePermissions(): array
    {
        $staff = [
            'attendance.time', 'attendance.view', 'books.view', 'books.qr',
            'circulation.process', 'borrow.self', 'profile.update', 'personnel.manage', 'schedules.view-own',
        ];
        $admin = [
            ...$staff,
            'members.manage', 'inventory.audit', 'reports.generate', 'audit.view',
            'books.manage', 'books.update', 'schedules.manage-staff',
        ];
        $member = ['attendance.time', 'books.view', 'borrow.self', 'history.own', 'qr.own', 'profile.update'];

        return [
            self::SUPERADMIN => self::PERMISSIONS,
            self::ADMIN => $admin,
            self::STAFF => $staff,
            self::MAINTENANCE => ['system.maintain', 'profile.update'],
            self::STUDENT => $member,
            self::FACULTY => $member,
            self::VISITOR => ['attendance.time', 'books.view'],
        ];
    }
}
