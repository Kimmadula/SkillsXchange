<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use App\Models\TradeTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    /**
     * Display a listing of all tasks for the authenticated user
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get all tasks where user is either creator or assignee
        $myTasks = TradeTask::where('assigned_to', $user->id)
            ->orWhere('created_by', $user->id)
            ->with(['trade', 'creator', 'assignee', 'verifier'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get task statistics
        $stats = [
            'total' => $myTasks->total(),
            'completed' => TradeTask::where('assigned_to', $user->id)->where('completed', true)->count(),
            'pending' => TradeTask::where('assigned_to', $user->id)->where('completed', false)->count(),
            'verified' => TradeTask::where('assigned_to', $user->id)->where('verified', true)->count(),
        ];

        return view('tasks.index', compact('myTasks', 'stats'));
    }

    /**
     * Show the form for creating a new task
     */
    public function create()
    {
        $user = Auth::user();
        
        // Get user's active trades
        $activeTrades = Trade::where('user_id', $user->id)
            ->orWhereHas('requests', function($query) use ($user) {
                $query->where('requester_id', $user->id)->where('status', 'accepted');
            })
            ->with(['user', 'requests.requester'])
            ->get();

        return view('tasks.create', compact('activeTrades'));
    }

    /**
     * Store a newly created task
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'trade_id' => 'required|exists:trades,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'assigned_to' => 'required|exists:users,id',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => 'nullable|date|after:today'
        ]);

        try {
            $trade = Trade::findOrFail($request->trade_id);
            
            // Check if user is part of this trade
            if ($trade->user_id !== $user->id && 
                !$trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists()) {
                return redirect()->back()->with('error', 'You are not authorized to create tasks for this trade.');
            }

            $task = $trade->tasks()->create([
                'created_by' => $user->id,
                'assigned_to' => $request->assigned_to,
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority ?? 'medium',
                'due_date' => $request->due_date
            ]);

            $task->load(['creator', 'assignee']);

            return redirect()->route('tasks.index')->with('success', 'Task created successfully!');
            
        } catch (\Exception $e) {
            Log::error('Task creation error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create task: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified task
     */
    public function show(TradeTask $task)
    {
        $user = Auth::user();
        
        // Check if user is part of this task's trade
        if ($task->trade->user_id !== $user->id && 
            !$task->trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists()) {
            return redirect()->back()->with('error', 'You are not authorized to view this task.');
        }

        $task->load(['trade', 'creator', 'assignee', 'verifier']);
        
        return view('tasks.show', compact('task'));
    }

    /**
     * Show the form for editing the specified task
     */
    public function edit(TradeTask $task)
    {
        $user = Auth::user();
        
        // Check if user is the creator of this task
        if ($task->created_by !== $user->id) {
            return redirect()->back()->with('error', 'You can only edit tasks you created.');
        }

        $task->load(['trade', 'assignee']);
        
        return view('tasks.edit', compact('task'));
    }

    /**
     * Update the specified task
     */
    public function update(Request $request, TradeTask $task)
    {
        $user = Auth::user();
        
        // Check if user is the creator of this task
        if ($task->created_by !== $user->id) {
            return redirect()->back()->with('error', 'You can only edit tasks you created.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'assigned_to' => 'required|exists:users,id',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => 'nullable|date'
        ]);

        try {
            $task->update([
                'assigned_to' => $request->assigned_to,
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority ?? 'medium',
                'due_date' => $request->due_date
            ]);

            return redirect()->route('tasks.show', $task)->with('success', 'Task updated successfully!');
            
        } catch (\Exception $e) {
            Log::error('Task update error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update task: ' . $e->getMessage());
        }
    }

    /**
     * Toggle task completion status
     */
    public function toggle(TradeTask $task)
    {
        $user = Auth::user();
        
        // Check if user is assigned to this task
        if ($task->assigned_to !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $task->update([
                'completed' => !$task->completed,
                'completed_at' => !$task->completed ? now() : null,
                'verified' => $task->completed ? false : $task->verified,
                'verified_at' => $task->completed ? null : $task->verified_at,
                'verified_by' => $task->completed ? null : $task->verified_by
            ]);

            return response()->json([
                'success' => true,
                'task' => $task->load(['creator', 'assignee', 'verifier'])
            ]);
            
        } catch (\Exception $e) {
            Log::error('Task toggle error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update task'], 500);
        }
    }

    /**
     * Remove the specified task
     */
    public function destroy(TradeTask $task)
    {
        $user = Auth::user();
        
        // Check if user is the creator of this task
        if ($task->created_by !== $user->id) {
            return redirect()->back()->with('error', 'You can only delete tasks you created.');
        }

        try {
            $task->delete();
            return redirect()->route('tasks.index')->with('success', 'Task deleted successfully!');
            
        } catch (\Exception $e) {
            Log::error('Task deletion error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete task: ' . $e->getMessage());
        }
    }
}
