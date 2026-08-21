<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function active(Request $request)
    {
        $dismissed = collect($request->session()->get('dismissed_announcements', []))->map(fn ($id) => (int) $id);

        $announcements = Announcement::query()
            ->currentlyActive()
            ->visibleTo($request->user())
            ->when($dismissed->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $dismissed))
            ->orderByRaw("CASE severity WHEN 'emergency' THEN 1 WHEN 'warning' THEN 2 WHEN 'success' THEN 3 ELSE 4 END")
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Announcement $announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'message' => $announcement->message,
                'severity' => $announcement->severity,
                'link_url' => $announcement->link_url,
                'is_dismissible' => $announcement->is_dismissible,
                'dismiss_url' => route('announcements.dismiss', $announcement),
            ]);

        return response()->json(['announcements' => $announcements]);
    }

    public function dismiss(Request $request, Announcement $announcement)
    {
        abort_unless($announcement->is_dismissible, 403);

        $ids = collect($request->session()->get('dismissed_announcements', []))
            ->push($announcement->id)
            ->unique()
            ->take(-50)
            ->values()
            ->all();

        $request->session()->put('dismissed_announcements', $ids);

        return response()->json(['status' => 'success']);
    }
}
