<?php

namespace App\Http\Controllers;

use App\Models\UserReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserReportController extends Controller
{
    public function store(Request $request, $tradeId)
    {
        $validated = $request->validate([
            'reported_user_id' => 'required|exists:users,id',
            'reason' => 'required|in:harassment,spam,inappropriate,fraud,safety,other',
            'description' => 'required|string|min:10|max:2000',
        ]);
        if ((int) $request->input('reported_user_id') === (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'error' => 'You cannot report yourself.'
            ], 422);
        }

        $report = UserReport::create([
            'reporter_id' => Auth::id(),
            'reported_user_id' => $request->input('reported_user_id'),
            'trade_id' => $tradeId,
            'context' => 'chat_session',
            'reason' => $request->input('reason'),
            'description' => $request->input('description'),
            'evidence' => $request->input('evidence', null),
            'status' => 'pending',
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'report_id' => $report->id,
                'message' => 'Report submitted successfully.'
            ]);
        }

        return back()->with('success', 'Report submitted successfully.');
    }
}


