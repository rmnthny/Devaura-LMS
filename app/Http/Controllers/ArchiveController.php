<?php

namespace App\Http\Controllers;

use App\Models\LibraryMember;
use App\Models\LibraryPersonnel;
use App\Models\User;
use App\Services\ArchiveBin;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    public function index(Request $request)
    {
        $sections = [];
        foreach (ArchiveBin::TYPES as $type => [$model, $permission, $title]) {
            if ($request->user()->can($permission)) {
                $sections[$type] = [
                    'title' => $title,
                    'records' => $model::onlyTrashed()->orderByDesc('deleted_at')
                        ->orderByDesc((new $model)->getKeyName())->paginate(15, ['*'], $type.'_page')->withQueryString(),
                ];
            }
        }

        if ($request->expectsJson()) {
            return response()->json($sections);
        }

        $section = $request->query('section');
        if (! is_string($section) || ! isset($sections[$section])) {
            $section = collect($sections)->filter(fn ($entry) => $entry['records']->total() > 0)->keys()->first()
                ?? array_key_first($sections);
        }
        if ($section === 'members') {
            $sections[$section]['records']->getCollection()->load(['user' => fn ($query) => $query->withTrashed()]);
        }

        return view('archive.index', compact('sections', 'section'));
    }

    public function restore(Request $request, string $type, int $record, ArchiveBin $archive)
    {
        [$model, $permission] = ArchiveBin::TYPES[$type];
        abort_unless($request->user()->can($permission), 403);
        $archive->restore($request->user(), $model, $record);

        return $request->expectsJson() ? response()->noContent() : to_route('archive.index', ['section' => $type])->with('status', 'Record restored.');
    }

    public function archivePersonnel(Request $request, LibraryPersonnel $personnel, ArchiveBin $archive)
    {
        $archive->archivePersonnel($request->user(), $personnel);

        return $request->expectsJson() ? response()->noContent() : to_route('archive.index')->with('status', 'Personnel profile archived.');
    }

    public function purgeMember(Request $request, int $record, ArchiveBin $archive)
    {
        $request->validate(['confirmation' => ['required', 'in:DELETE']], [
            'confirmation.in' => 'Type DELETE to confirm permanent removal.',
        ]);
        $archive->purgeMember($request->user(), $record);

        return $request->expectsJson() ? response()->noContent() : to_route('archive.index', ['section' => 'members'])->with('status', 'Member account permanently deleted.');
    }

    public function confirmMemberPurge(Request $request, int $record, ArchiveBin $archive)
    {
        abort_unless($request->user()->can('members.delete'), 403);
        $member = LibraryMember::onlyTrashed()->findOrFail($record);
        $account = $member->user()->withTrashed()->firstOrFail();
        $reason = $archive->memberPurgeBlock($request->user(), $account, $account->personnel()->withTrashed()->first());

        return view('archive.confirm-member-purge', compact('member', 'account', 'reason'));
    }

    public function archiveAccount(Request $request, User $account, ArchiveBin $archive)
    {
        $archive->archiveAccount($request->user(), $account);

        return $request->expectsJson() ? response()->noContent() : to_route('archive.index')->with('status', 'Account archived.');
    }
}
