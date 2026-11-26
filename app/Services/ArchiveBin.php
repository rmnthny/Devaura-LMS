<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Book;
use App\Models\LibraryMember;
use App\Models\LibraryPersonnel;
use App\Models\User;
use App\Support\Rbac;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArchiveBin
{
    public const TYPES = [
        'books' => [Book::class, 'books.delete', 'Books'],
        'members' => [LibraryMember::class, 'members.delete', 'Members'],
        'personnel' => [LibraryPersonnel::class, 'personnel.delete', 'Personnel'],
        'accounts' => [User::class, 'personnel.delete', 'Accounts'],
    ];

    public function archivePersonnel(User $actor, LibraryPersonnel $personnel): void
    {
        DB::transaction(function () use ($actor, $personnel): void {
            $personnel = LibraryPersonnel::lockForUpdate()->findOrFail($personnel->getKey());
            $user = $personnel->user()->withTrashed()->lockForUpdate()->firstOrFail();
            $this->guardAccount($actor, $user);
            $linkedAccount = null;
            if (! $user->trashed()) {
                $user->delete();
                $linkedAccount = $user->getKey();
            }
            $personnel->delete();
            activity('archive')->causedBy($actor)->performedOn($personnel)->event('archived')
                ->withProperties(['linked_account' => $linkedAccount])->log('Archived a personnel profile');
        });
    }

    public function archiveAccount(User $actor, User $account): void
    {
        $this->guardAccount($actor, $account);
        $account->delete();
    }

    public function archiveMember(User $actor, LibraryMember $member): void
    {
        DB::transaction(function () use ($actor, $member): void {
            $member = LibraryMember::lockForUpdate()->findOrFail($member->getKey());
            $user = $member->user()->withTrashed()->lockForUpdate()->firstOrFail();
            $this->guardAccount($actor, $user);
            $this->guardPersonnelAccount($actor, $user);
            $personnel = $user->personnel()->lockForUpdate()->first();
            $linkedAccount = null;
            $linkedPersonnel = null;
            if (! $user->trashed()) {
                $user->delete();
                $linkedAccount = $user->getKey();
                if ($personnel) {
                    $personnel->delete();
                    $linkedPersonnel = $personnel->getKey();
                }
            }
            $member->delete();
            activity('archive')->causedBy($actor)->performedOn($member)->event('archived')
                ->withProperties(['linked_account' => $linkedAccount, 'linked_personnel' => $linkedPersonnel])
                ->log('Archived a library member');
        });
    }

    public function restore(User $actor, string $model, int $id): Model
    {
        return DB::transaction(function () use ($actor, $model, $id): Model {
            $record = $model::onlyTrashed()->lockForUpdate()->findOrFail($id);
            if ($record instanceof LibraryMember || $record instanceof LibraryPersonnel) {
                if ($record instanceof LibraryMember) {
                    $user = $record->user()->withTrashed()->firstOrFail();
                    $this->guardPersonnelAccount($actor, $user);
                }
                $entry = Activity::where('log_name', 'archive')->forSubject($record)
                    ->where('event', 'archived')->latest('id')->first();
                $accountId = $entry?->properties?->get('linked_account');
                if ($accountId !== null) {
                    User::onlyTrashed()->find($accountId)?->restore();
                }
                $personnelId = $entry?->properties?->get('linked_personnel');
                if ($personnelId !== null) {
                    LibraryPersonnel::onlyTrashed()->find($personnelId)?->restore();
                }
            }
            if ($record instanceof User && $this->hasLinkedArchive($record)) {
                throw ValidationException::withMessages(['account' => 'Restore this account from its archived member or personnel profile.']);
            }
            $record->restore();
            activity('archive')->causedBy($actor)->performedOn($record)->event('restored')->log('Restored an archived record');

            return $record;
        });
    }

    public function purgeMember(User $actor, int $id): void
    {
        abort_unless($actor->hasRole(Rbac::SUPERADMIN) && $actor->can('members.delete'), 403);

        try {
            DB::transaction(function () use ($actor, $id): void {
                $member = LibraryMember::onlyTrashed()->lockForUpdate()->findOrFail($id);
                $user = $member->user()->withTrashed()->lockForUpdate()->firstOrFail();
                $personnel = $user->personnel()->withTrashed()->lockForUpdate()->first();
                if ($reason = $this->memberPurgeBlock($actor, $user, $personnel)) {
                    throw ValidationException::withMessages(['record' => $reason]);
                }

                activity('archive')->causedBy($actor)->performedOn($member)
                    ->event('purged')->log('Permanently deleted an archived library member');
                $personnel?->forceDelete();
                $member->forceDelete();
                $user->notifications()->delete();
                $user->forceDelete();
            });
        } catch (QueryException $error) {
            if ((int) ($error->errorInfo[1] ?? 0) !== 1451) {
                throw $error;
            }

            throw ValidationException::withMessages([
                'record' => 'This account is linked to library history and must stay in the archive.',
            ]);
        }
    }

    public function memberPurgeBlock(User $actor, User $user, ?LibraryPersonnel $personnel): ?string
    {
        if ($actor->is($user) || $user->hasRole(Rbac::SUPERADMIN)) {
            return 'The Head Librarian account is protected.';
        }
        if (! $user->trashed() || ($personnel && ! $personnel->trashed()) || $user->hasAnyRole(Rbac::PERSONNEL_ROLES)) {
            return 'Permanent deletion requires an archived member account with no active personnel assignment.';
        }

        $id = $user->getKey();
        if (User::withTrashed()->whereKey($id)->withoutLibraryHistory()->doesntExist()
            || ($personnel && DB::table('personnel_schedule')->where('personnel_id', $personnel->getKey())->exists())) {
            return 'This account is linked to library history and must stay in the archive.';
        }

        return null;
    }

    private function guardAccount(User $actor, User $target): void
    {
        if ($actor->is($target) || $target->hasRole(Rbac::SUPERADMIN)) {
            throw ValidationException::withMessages(['account' => 'You cannot archive your own account or the Head Librarian account.']);
        }
    }

    private function guardPersonnelAccount(User $actor, User $target): void
    {
        abort_if($target->hasAnyRole(Rbac::PERSONNEL_ROLES) && ! $actor->can('personnel.delete'), 403);
    }

    private function hasLinkedArchive(User $account): bool
    {
        foreach ([$account->member()->onlyTrashed()->first(), $account->personnel()->onlyTrashed()->first()] as $profile) {
            if ($profile && Activity::inLog('archive')->forSubject($profile)->where('event', 'archived')
                ->latest('id')->first()?->properties?->get('linked_account') === $account->getKey()) {
                return true;
            }
        }

        return false;
    }
}
