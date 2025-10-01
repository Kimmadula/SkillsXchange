<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use App\Models\TradeMessage;
use App\Models\TradeTask;
use App\Events\MessageSent;
use App\Events\TaskUpdated;
use App\Services\SkillLearningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function show(Trade $trade)
    {
        $user = Auth::user();
        
        // Prevent admin users from accessing chat functionality
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Admin users cannot access user chat functionality.');
        }
        
        // Check if user is part of this trade
        if ($trade->user_id !== $user->id && 
            !$trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists()) {
            return redirect()->back()->with('error', 'You are not authorized to view this trade chat.');
        }

        // Get the other user (trade partner)
        $partner = $trade->user_id === $user->id 
            ? $trade->requests()->where('status', 'accepted')->first()->requester
            : $trade->user;

        // Get messages
        $messages = $trade->messages()->with('sender')->orderBy('created_at', 'asc')->get();

        // Get tasks
        $myTasks = $trade->tasks()->where('assigned_to', $user->id)->get();
        $partnerTasks = $trade->tasks()->where('assigned_to', $partner->id)->get();

        // Calculate progress
        $myProgress = $myTasks->count() > 0 ? ($myTasks->where('completed', true)->count() / $myTasks->count()) * 100 : 0;
        $partnerProgress = $partnerTasks->count() > 0 ? ($partnerTasks->where('completed', true)->count() / $partnerTasks->count()) * 100 : 0;

        return view('chat.session', compact('trade', 'partner', 'messages', 'myTasks', 'partnerTasks', 'myProgress', 'partnerProgress'));
    }

    public function sendMessage(Request $request, Trade $trade)
    {
        try {
            $user = Auth::user();
            
            Log::info('Chat message attempt', [
                'user_id' => $user->id,
                'trade_id' => $trade->id,
                'message' => $request->message
            ]);
            
            // Check if user is part of this trade
            if ($trade->user_id !== $user->id && 
                !$trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists()) {
                Log::warning('Unauthorized chat access attempt', [
                    'user_id' => $user->id,
                    'trade_id' => $trade->id
                ]);
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $request->validate([
                'message' => 'required|string|max:1000'
            ]);

            $message = $trade->messages()->create([
                'sender_id' => $user->id,
                'message' => $request->message
            ]);

            $message->load('sender');
            
            Log::info('Message created successfully', [
                'message_id' => $message->id,
                'trade_id' => $trade->id
            ]);

            // Broadcast message using Laravel events
            try {
                Log::info('Attempting to broadcast message', [
                    'message_id' => $message->id,
                    'trade_id' => $trade->id
                ]);
                
                event(new MessageSent($message, $trade->id));
                
                Log::info('Message broadcasted successfully');
            } catch (\Exception $e) {
                Log::error('Broadcasting failed: ' . $e->getMessage(), [
                    'message_id' => $message->id,
                    'trade_id' => $trade->id,
                    'error' => $e->getMessage()
                ]);
                // Continue even if broadcasting fails
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            Log::error('Message send error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? null,
                'trade_id' => $trade->id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'error' => 'Failed to send message: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createTask(Request $request, Trade $trade)
    {
        try {
            $user = Auth::user();
            
            // Check if user is part of this trade
            if ($trade->user_id !== $user->id && 
                !$trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'assigned_to' => 'required|exists:users,id'
            ]);

            $task = $trade->tasks()->create([
                'created_by' => $user->id,
                'assigned_to' => $request->assigned_to,
                'title' => $request->title,
                'description' => $request->description
            ]);

            $task->load(['creator', 'assignee']);

            // Broadcast task update using Laravel events
            try {
                event(new TaskUpdated($task, $trade->id));
            } catch (\Exception $e) {
                Log::error('Broadcasting failed: ' . $e->getMessage());
                // Continue even if broadcasting fails
            }

            return response()->json([
                'success' => true,
                'task' => $task
            ]);
        } catch (\Exception $e) {
            Log::error('Task creation error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create task: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getMessages(Trade $trade)
    {
        try {
            $user = Auth::user();
            
            // Check if user is part of this trade
            if ($trade->user_id !== $user->id && 
                !$trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $messages = $trade->messages()->with('sender')->orderBy('created_at', 'asc')->get();
            
            return response()->json([
                'success' => true,
                'count' => $messages->count(),
                'messages' => $messages
            ]);
        } catch (\Exception $e) {
            Log::error('Get messages error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to get messages: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleTask(Request $request, TradeTask $task)
    {
        try {
            $user = Auth::user();
            
            // Check if user is assigned to this task
            if ($task->assigned_to !== $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $task->update([
                'completed' => !$task->completed,
                'completed_at' => !$task->completed ? now() : null,
                // Reset verification when task is marked as incomplete
                'verified' => $task->completed ? false : $task->verified,
                'verified_at' => $task->completed ? null : $task->verified_at,
                'verified_by' => $task->completed ? null : $task->verified_by
            ]);

            $task->load(['creator', 'assignee', 'verifier']);

            // Broadcast task update using Laravel events
            try {
                event(new TaskUpdated($task, $task->trade_id));
            } catch (\Exception $e) {
                Log::error('Broadcasting failed: ' . $e->getMessage());
                // Continue even if broadcasting fails
            }

            return response()->json([
                'success' => true,
                'task' => $task
            ]);
        } catch (\Exception $e) {
            Log::error('Task toggle error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update task: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verifyTask(Request $request, TradeTask $task)
    {
        try {
            $user = Auth::user();
            
            // Check if user is the creator of this task (only the creator can verify)
            if ($task->created_by !== $user->id) {
                return response()->json(['error' => 'Only the task creator can verify completion'], 403);
            }

            // Check if task is completed
            if (!$task->completed) {
                return response()->json(['error' => 'Task must be completed before verification'], 400);
            }

            $request->validate([
                'verified' => 'required|boolean',
                'verification_notes' => 'nullable|string|max:1000'
            ]);

            $task->update([
                'verified' => $request->verified,
                'verified_at' => $request->verified ? now() : null,
                'verified_by' => $request->verified ? $user->id : null,
                'verification_notes' => $request->verification_notes
            ]);

            $task->load(['creator', 'assignee', 'verifier']);

            // Broadcast task update using Laravel events
            try {
                event(new TaskUpdated($task, $task->trade_id));
            } catch (\Exception $e) {
                Log::error('Broadcasting failed: ' . $e->getMessage());
                // Continue even if broadcasting fails
            }

            return response()->json([
                'success' => true,
                'task' => $task
            ]);
        } catch (\Exception $e) {
            Log::error('Task verification error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to verify task: ' . $e->getMessage()
            ], 500);
        }
    }

    public function completeSession(Request $request, Trade $trade)
    {
        try {
            $user = Auth::user();
            
            // Check if user is part of this trade
            if ($trade->user_id !== $user->id && 
                !$trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $skillLearningService = new SkillLearningService();
            
            // Check if trade is ready for skill learning processing
            if (!$skillLearningService->isTradeReadyForSkillLearning($trade)) {
                return response()->json([
                    'error' => 'Session is not ready for completion. Please ensure all completed tasks are verified.',
                    'ready_for_processing' => false
                ], 400);
            }

            // Process skill learning
            $results = $skillLearningService->processSkillLearning($trade);
            
            // Update trade status to completed
            $trade->update(['status' => 'completed']);

            return response()->json([
                'success' => true,
                'message' => 'Session completed successfully!',
                'skill_learning_results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Error completing session: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to complete session: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSkillLearningStatus(Trade $trade)
    {
        try {
            $user = Auth::user();
            
            // Check if user is part of this trade
            if ($trade->user_id !== $user->id && 
                !$trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $skillLearningService = new SkillLearningService();
            $summary = $skillLearningService->getSkillLearningSummary($trade);

            return response()->json([
                'success' => true,
                'summary' => $summary
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting skill learning status: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to get skill learning status: ' . $e->getMessage()
            ], 500);
        }
    }
}