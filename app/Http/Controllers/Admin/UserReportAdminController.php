<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserReport;
use Illuminate\Http\Request;

class UserReportAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = UserReport::query()->with(['reporter', 'reported']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($reason = $request->get('reason')) {
            $query->where('reason', $reason);
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.user-reports.index', compact('reports'));
    }

    public function show(UserReport $report)
    {
        $report->load(['reporter', 'reported']);
        return view('admin.user-reports.show', compact('report'));
    }

    public function updateStatus(Request $request, UserReport $report)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,under_review,resolved,dismissed',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $report->update($validated);

        return redirect()->route('admin.user-reports.show', $report)->with('success', 'Report updated successfully.');
    }
}


