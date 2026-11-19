<?php

namespace App\Support;

/**
 * Single source of truth for the 7 roles and their Spatie permissions.
 * Mapped from the Use Case Diagrams + Sprint 1 requirements.
 */
final class Rbac
{
    public const SUPERADMIN = 'superadmin';   // Head Librarian (only one)
    public const ADMIN = 'admin';             // Assigned Teacher
    public const STAFF = 'staff';             // Student Staff
    public const MAINTENANCE = 'maintenance'; // System maintenance
    public const STUDENT = 'student';         // Library member
    public const FACULTY = 'faculty';         // Library member
    public const VISITOR = 'visitor';         // Walk-in (no login)

    /** Roles that are never public; granted only by the superadmin. */
    public const PERSONNEL_ROLES = [self::SUPERADMIN, self::ADMIN, self::STAFF, self::MAINTENANCE];

    /** Roles allowed on public sign-up. */
    public const MEMBER_ROLES = [self::STUDENT, self::FACULTY];

    public const PERMISSIONS = [
        'attendance.time',     // Time In / Time Out
        'attendance.view',     // View Attendance Logs
        'books.view',          // Search Book Catalog
        'books.manage',        // Manage Book Inventory (add/update)
        'books.delete',        // Delete/archive books
        'books.qr',            // Generate / Print QR codes
        'inventory.audit',     // Audit Library Inventory
        'circulation.process', // Process Book Borrowing / Return
        'borrow.self',         // Borrow / Return Book (as borrower)
        'history.own',         // View Borrowing History
        'qr.own',              // View Personal QR
        'profile.update',      // Update Personal Info
        'members.manage',      // Manage Library Members
        'members.delete',      // Archive members
        'personnel.manage',    // Manage Library Personnel
        'personnel.delete',    // Archive personnel
        'roles.manage',        // Manage Role Permissions / assign roles
        'reports.generate',    // Generate Operational Reports
        'audit.view',          // View Audit Trail
        'system.maintain',     // Maintenance tasks
    ];

    /** @return array<string, list<string>> */
    public static function rolePermissions(): array
    {
        $staff = [
            'attendance.time', 'attendance.view', 'books.view', 'books.manage', 'books.qr',
            'circulation.process', 'borrow.self', 'profile.update', 'personnel.manage',
        ];
        $admin = [
            ...$staff,
            'members.manage', 'members.delete', 'inventory.audit', 'reports.generate', 'audit.view',
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
