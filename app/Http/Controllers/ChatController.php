<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use App\Models\TradeMessage;
use App\Models\TradeTask;
use App\Events\MessageSent;
use App\Events\TaskUpdated;
use App\Events\TaskCreated;
use App\Events\TaskDeleted;
use App\Services\SkillLearningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function show(Trade $trade)
    {
        $user = Auth::user();
        
        // Add debugging information
        Log::info('Chat access attempt', [
            'user_id' => $user->id ?? 'not_authenticated',
            'user_role' => $user->role ?? 'not_authenticated',
            'trade_id' => $trade->id ?? 'not_found',
            'trade_user_id' => $trade->user_id ?? 'not_found',
            'trade_status' => $trade->status ?? 'not_found',
            'auth_check' => Auth::check(),
            'session_id' => session()->getId(),
            'url' => request()->url()
        ]);
        
        // Check if user is authenticated
        if (!$user || !Auth::check()) {
            Log::warning('Unauthenticated user trying to access chat', [
                'user_exists' => $user ? 'yes' : 'no',
                'auth_check' => Auth::check(),
                'session_id' => session()->getId()
            ]);
            
            // Redirect to login
            return redirect()->route('login')->with('error', 'Please log in to access chat.');
        }
        
        // Allow admin users to access chat functionality for support and monitoring
        // Removed admin restriction to enable chat access for all user roles
        
        // Check if user is part of this trade
        $isTradeOwner = $trade->user_id === $user->id;
        $hasAcceptedRequest = $trade->requests()->where('requester_id', $user->id)->where('status', 'accepted')->exists();
        
        Log::info('Trade authorization check', [
            'is_trade_owner' => $isTradeOwner,
            'has_accepted_request' => $hasAcceptedRequest,
            'user_id' => $user->id,
            'trade_id' => $trade->id
        ]);
        
        if (!$isTradeOwner && !$hasAcceptedRequest) {
            Log::warning('Unauthorized chat access attempt', [
                'user_id' => $user->id,
                'trade_id' => $trade->id,
                'trade_owner' => $trade->user_id
            ]);
            
            // Redirect back with error message
            return redirect()->route('trades.ongoing')->with('error', 'You are not authorized to access this chat.');
        }

        // Get the other user (trade partner)
        if ($trade->user_id === $user->id) {
            $acceptedRequest = $trade->requests()->where('status', 'accepted')->first();
            if (!$acceptedRequest) {
                Log::warning('No accepted request found for trade', [
                    'trade_id' => $trade->id,
                    'user_id' => $user->id
                ]);
                return redirect()->route('trades.ongoing')->with('error', 'No accepted request found for this trade.');
            }
            $partner = $acceptedRequest->requester;
        } else {
            $partner = $trade->user;
        }

        // Get messages
        $messages = $trade->messages()->with('sender')->orderBy('created_at', 'asc')->get();

        // Get tasks
        $myTasks = $trade->tasks()->where('assigned_to', $user->id)->get();
        $partnerTasks = $trade->tasks()->where('assigned_to', $partner->id)->get();

        // Calculate progress
        $myProgress = $myTasks->count() > 0 ? ($myTasks->where('completed', true)->count() / $myTasks->count()) * 100 : 0;
        $partnerProgress = $partnerTasks->count() > 0 ? ($partnerTasks->where('completed', true)->count() / $partnerTasks->count()) * 100 : 0;

        // Return JSON for debugging instead of view
        /*
        return response()->json([
            'success' => true,
            'message' => 'Chat controller reached successfully',
            'trade_id' => $trade->id,
            'user_id' => $user->id,
            'partner_id' => $partner->id,
            'messages_count' => $messages->count(),
            'my_tasks_count' => $myTasks->count(),
            'partner_tasks_count' => $partnerTasks->count(),
            'my_progress' => $myProgress,
            'partner_progress' => $partnerProgress
        ]);
        */

        // Return the actual chat session view
        return view('chat.session', compact('trade', 'partner', 'messages', 'myTasks', 'partnerTasks', 'myProgress', 'partnerProgress'));

        // Original view return (commented out for debugging)
        // return view('chat.session', compact('trade', 'partner', 'messages', 'myTasks', 'partnerTasks', 'myProgress', 'partnerProgress'));
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
                'assigned_to' => 'required|exists:users,id',
                'priority' => 'nullable|in:low,medium,high',
                'due_date' => 'nullable|date|after:today',
                'requires_submission' => 'boolean',
                'allowed_file_types' => 'nullable|array',
                'allowed_file_types.*' => 'in:images,videos,pdf,docx,excel',
                'submission_instructions' => 'nullable|string|max:1000'
            ]);

            // Ensure task is only assigned to the other party (not self)
            if ($request->assigned_to == $user->id) {
                return response()->json(['error' => 'Tasks can only be assigned to your trade partner'], 400);
            }

            // Get the trade partner (the other user in the trade)
            $tradePartner = null;
            if ($trade->user_id === $user->id) {
                // Current user is the trade owner, partner is the requester
                $acceptedRequest = $trade->requests()->where('status', 'accepted')->first();
                if ($acceptedRequest) {
                    $tradePartner = $acceptedRequest->requester;
                }
            } else {
                // Current user is the requester, partner is the trade owner
                $tradePartner = $trade->user;
            }

            // Validate that the task is being assigned to the trade partner
            if (!$tradePartner || $request->assigned_to != $tradePartner->id) {
                return response()->json(['error' => 'Tasks can only be assigned to your trade partner'], 400);
            }

            $task = $trade->tasks()->create([
                'created_by' => $user->id,
                'assigned_to' => $request->assigned_to,
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority ?? 'medium',
                'due_date' => $request->due_date,
                'requires_submission' => $request->boolean('requires_submission'),
                'allowed_file_types' => $request->allowed_file_types,
                'submission_instructions' => $request->submission_instructions,
                'max_score' => 100,
                'passing_score' => 70,
                'current_status' => 'assigned'
            ]);

            $task->load(['creator', 'assignee']);

            // Broadcast task created event
            try {
                event(new TaskCreated($task, $trade->id));
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
                    'error' => 'Session is not ready for completion. Please ensure all tasks are completed.',
                    'ready_for_processing' => false
                ], 400);
            }

            // Process skill learning
            $results = $skillLearningService->processSkillLearning($trade);
            
            // Update trade status to closed
            $trade->update(['status' => 'closed']);

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