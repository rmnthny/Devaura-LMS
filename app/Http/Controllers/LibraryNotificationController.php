<?php

namespace App\Http\Controllers;

use App\Services\LibraryAlerts;
use Illuminate\Http\Request;

class LibraryNotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()
            ->where('type', LibraryAlerts::class)->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function read(Request $request, string $notification)
    {
        $request->user()->notifications()
            ->where('type', LibraryAlerts::class)->findOrFail($notification)->markAsRead();

        return to_route('notifications.index');
    }
}
