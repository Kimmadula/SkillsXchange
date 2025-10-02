<?php

namespace App\Http\Controllers;

use App\Models\SkillAcquisitionHistory;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SkillHistoryController extends Controller
{
    /**
     * Display user's skill acquisition history
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get skill history with pagination
        $skillHistory = SkillAcquisitionHistory::forUser($user->id)
            ->with(['skill', 'task', 'trade'])
            ->orderBy('acquired_at', 'desc')
            ->paginate(20);

        // Get skill statistics
        $stats = SkillAcquisitionHistory::getSkillStats($user->id);
        
        // Get recent acquisitions for quick view
        $recentAcquisitions = SkillAcquisitionHistory::forUser($user->id)
            ->with(['skill', 'task'])
            ->recent(7)
            ->orderBy('acquired_at', 'desc')
            ->limit(5)
            ->get();

        return view('skills.history', compact('skillHistory', 'stats', 'recentAcquisitions'));
    }

    /**
     * Show detailed view of a specific skill acquisition
     */
    public function show(SkillAcquisitionHistory $history)
    {
        $user = Auth::user();
        
        // Check if this history belongs to the authenticated user
        if ($history->user_id !== $user->id) {
            return redirect()->back()->with('error', 'You can only view your own skill history.');
        }

        $history->load(['skill', 'task.trade', 'trade']);
        
        return view('skills.history-detail', compact('history'));
    }

    /**
     * Get skill acquisition data for charts/analytics
     */
    public function getAnalytics()
    {
        $user = Auth::user();
        
        // Monthly acquisition data for the last 12 months
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $count = SkillAcquisitionHistory::forUser($user->id)
                ->whereYear('acquired_at', $month->year)
                ->whereMonth('acquired_at', $month->month)
                ->count();
            
            $monthlyData[] = [
                'month' => $month->format('M Y'),
                'count' => $count
            ];
        }

        // Acquisition by method
        $methodData = SkillAcquisitionHistory::forUser($user->id)
            ->selectRaw('acquisition_method, COUNT(*) as count')
            ->groupBy('acquisition_method')
            ->get()
            ->map(function ($item) {
                return [
                    'method' => $item->acquisition_method,
                    'display' => match($item->acquisition_method) {
                        'task_completion' => 'Task Completion',
                        'manual_add' => 'Manual Addition',
                        'trade_completion' => 'Trade Completion',
                        'verification' => 'Skill Verification',
                        default => 'Other'
                    },
                    'count' => $item->count
                ];
            });

        // Top skills acquired
        $topSkills = SkillAcquisitionHistory::forUser($user->id)
            ->with('skill')
            ->get()
            ->groupBy('skill.category')
            ->map(function ($group) {
                return $group->count();
            })
            ->sortDesc()
            ->take(5);

        return response()->json([
            'monthly' => $monthlyData,
            'by_method' => $methodData,
            'top_categories' => $topSkills
        ]);
    }

    /**
     * Export skill history as CSV
     */
    public function export()
    {
        $user = Auth::user();
        
        $history = SkillAcquisitionHistory::forUser($user->id)
            ->with(['skill', 'task', 'trade'])
            ->orderBy('acquired_at', 'desc')
            ->get();

        $filename = 'skill_history_' . $user->id . '_' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($history) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Skill Name',
                'Category',
                'Acquisition Method',
                'Acquired Date',
                'Task Title',
                'Trade',
                'Score Achieved',
                'Notes'
            ]);

            // CSV data
            foreach ($history as $record) {
                fputcsv($file, [
                    $record->skill->name ?? 'N/A',
                    $record->skill->category ?? 'N/A',
                    $record->method_display,
                    $record->acquired_at->format('Y-m-d H:i:s'),
                    $record->task->title ?? 'N/A',
                    $record->trade ? ($record->trade->offeringSkill->name . ' ↔ ' . $record->trade->lookingSkill->name) : 'N/A',
                    $record->score_achieved ?? 'N/A',
                    $record->notes ?? 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
