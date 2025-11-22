<aside class="tasks-sidebar">
    <div class="sidebar-header">
        <div class="sidebar-title-container">
            <span class="sidebar-icon">☑️</span>
            <h2 class="sidebar-title">Session Tasks</h2>
        </div>
        <div class="tabs-container">
            <button class="tab-btn active" data-tab="my-tasks">My Tasks ({{ $myTasks->count() }})</button>
            <button class="tab-btn" data-tab="partner-tasks">Partner ({{ $partnerTasks->count() }})</button>
        </div>
    </div>

    <div class="tasks-content">
        <!-- My Tasks Tab -->
        <div class="tab-content" data-content="my-tasks">
            @php
                $myProgressValue = max(0, min(100, round($myProgress ?? 0)));
            @endphp
            <div class="progress-section">
                <div class="progress-header">
                    <span class="progress-label">Progress</span>
                    <span class="progress-percentage">{{ $myProgressValue }}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="--progress-width: {{ $myProgressValue }}%; width: var(--progress-width);"></div>
                </div>
            </div>
            
            <div class="task-list" id="my-tasks">
                @foreach($myTasks as $task)
                    <div class="task-item" data-task-id="{{ $task->id }}" onclick="showTaskDetails({{ $task->id }})" style="cursor: pointer;">
                        <div class="task-header">
                            <input type="checkbox" class="task-checkbox" 
                                {{ $task->completed ? 'checked' : '' }}
                                onclick="event.stopPropagation(); toggleTask({{ $task->id }})">
                            <div class="task-content">
                                <div class="task-title">{{ $task->title }}</div>
                                <div class="task-meta">
                                    @if($task->priority)
                                        <span class="task-tag tag-priority-{{ $task->priority }}">
                                            {{ ucfirst($task->priority) }} Priority
                                        </span>
                                    @endif
                                    @if($task->current_status)
                                        <span class="task-tag tag-status">
                                            {{ ucfirst(str_replace('_', ' ', $task->current_status)) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @if(Auth::id() === $task->created_by && !$task->submissions()->exists())
                                <div class="task-actions" style="display:flex; gap:6px; margin-left:auto;" onclick="event.stopPropagation()">
                                    <button onclick="editTask({{ $task->id }})" title="Edit Task"
                                            style="background:#3b82f6;color:#fff;border:none;border-radius:4px;padding:4px 8px;font-size:.75rem;cursor:pointer;">
                                        Edit
                                    </button>
                                    <button onclick="deleteTask({{ $task->id }})" title="Delete Task"
                                            style="background:#ef4444;color:#fff;border:none;border-radius:4px;padding:4px 8px;font-size:.75rem;cursor:pointer;">
                                        Delete
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Partner Tasks Tab -->
        <div class="tab-content" data-content="partner-tasks" style="display: none;">
            @php
                $partnerProgressValue = max(0, min(100, round($partnerProgress ?? 0)));
            @endphp
            <div class="progress-section">
                <div class="progress-header">
                    <span class="progress-label">Progress</span>
                    <span class="progress-percentage">{{ $partnerProgressValue }}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="--progress-width: {{ $partnerProgressValue }}%; width: var(--progress-width);"></div>
                </div>
            </div>
            
            <div class="task-list" id="partner-tasks">
                @foreach($partnerTasks as $task)
                    <div class="task-item" data-task-id="{{ $task->id }}" onclick="showTaskDetails({{ $task->id }})" style="cursor: pointer;">
                        <div class="task-header">
                            <input type="checkbox" class="task-checkbox" 
                                {{ $task->completed ? 'checked' : '' }}
                                disabled
                                onclick="event.stopPropagation()">
                            <div class="task-content">
                                <div class="task-title">{{ $task->title }}</div>
                                <div class="task-meta">
                                    @if($task->priority)
                                        <span class="task-tag tag-priority-{{ $task->priority }}">
                                            {{ ucfirst($task->priority) }} Priority
                                        </span>
                                    @endif
                                    @if($task->current_status)
                                        <span class="task-tag tag-status">
                                            {{ ucfirst(str_replace('_', ' ', $task->current_status)) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @if(Auth::id() === $task->created_by && !$task->submissions()->exists())
                                <div class="task-actions" style="display:flex; gap:6px; margin-left:auto;" onclick="event.stopPropagation()">
                                    <button onclick="editTask({{ $task->id }})" title="Edit Task"
                                            style="background:#3b82f6;color:#fff;border:none;border-radius:4px;padding:4px 8px;font-size:.75rem;cursor:pointer;">
                                        Edit
                                    </button>
                                    <button onclick="deleteTask({{ $task->id }})" title="Delete Task"
                                            style="background:#ef4444;color:#fff;border:none;border-radius:4px;padding:4px 8px;font-size:.75rem;cursor:pointer;">
                                        Delete
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="sidebar-footer">
        <button class="add-task-btn" onclick="showAddTaskModal()">
            <span class="plus-icon">+</span>
            Add Task
        </button>
    </div>

    <div id="taskDetailsModal" class="modal-overlay" style="display: none;" onclick="closeTaskDetails(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">Task Details</h3>
            <button class="modal-close" onclick="closeTaskDetails()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="detail-group">
                <label class="detail-label">Title</label>
                <div class="detail-value" id="detailTitle"></div>
            </div>
            <div class="detail-group">
                <label class="detail-label">Description</label>
                <div class="detail-value" id="detailDescription"></div>
            </div>
            <div class="detail-row">
                <div class="detail-group">
                    <label class="detail-label">Priority</label>
                    <div class="detail-value" id="detailPriority"></div>
                </div>
                <div class="detail-group">
                    <label class="detail-label">Status</label>
                    <div class="detail-value" id="detailStatus"></div>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-group">
                    <label class="detail-label">Required File Types</label>
                    <div class="detail-value" id="detailFileTypes"></div>
                </div>
                <div class="detail-group">
                    <label class="detail-label">Created By</label>
                    <div class="detail-value" id="detailCreatedBy"></div>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-group">
                    <label class="detail-label">Due Date</label>
                    <div class="detail-value" id="detailDueDate"></div>
                </div>
                <div class="detail-group">
                    <label class="detail-label">Completed</label>
                    <div class="detail-value" id="detailCompleted"></div>
                </div>
            </div>
            <div class="detail-group">
                <label class="detail-label">Created At</label>
                <div class="detail-value" id="detailCreatedAt"></div>
            </div>
            <!-- Grade and Checked Date (only show if graded) -->
            <div id="detailGradeSection" style="display: none;">
                <div class="detail-row">
                    <div class="detail-group">
                        <label class="detail-label">Status</label>
                        <div class="detail-value" id="detailGrade"></div>
                    </div>
                    <div class="detail-group">
                        <label class="detail-label">Checked Date</label>
                        <div class="detail-value" id="detailCheckedAt"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer" id="taskDetailsModalFooter">
            <button class="modal-btn btn-secondary" onclick="closeTaskDetails()">Close</button>
        </div>
    </div>
</div>

<!-- View Submission Modal -->
<div id="viewSubmissionModal" class="modal-overlay" style="display: none; z-index: 1000;" onclick="closeViewSubmissionModal(event)">
    <div class="modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto; z-index: 1001;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">View Partner's Work</h3>
            <button class="modal-close" onclick="closeViewSubmissionModal()">&times;</button>
        </div>
        <div class="modal-body" id="viewSubmissionContent">
            <p>Loading submission...</p>
        </div>
        <div class="modal-footer" id="viewSubmissionFooter">
            <button class="modal-btn btn-secondary" onclick="closeViewSubmissionModal()">Close</button>
        </div>
    </div>
</div>

<!-- Grade Task Modal -->
<div id="gradeTaskModal" class="modal-overlay" style="display: none;" onclick="closeGradeTaskModal(event)">
    <div class="modal-content" style="max-width: 600px;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">Grade Task</h3>
            <button class="modal-close" onclick="closeGradeTaskModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="gradeTaskForm">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" id="gradeTaskId" name="task_id">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="gradeScore" style="display: block; margin-bottom: 8px; font-weight: 600;">Score (0-<span id="maxScoreDisplay">100</span>)</label>
                    <input type="number" id="gradeScore" name="score_percentage" min="0" max="100" required 
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;"
                           onchange="updateStatusDisplay()">
                    <small style="color: #6b7280; display: block; margin-top: 4px;">Enter a score from 0 to <span id="maxScoreDisplay2">100</span></small>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Status Preview</label>
                    <div id="statusBadge" style="padding: 12px; border-radius: 4px; text-align: center; font-weight: 600; font-size: 18px; border: 2px solid #ddd;">
                        <span id="statusText">-</span>
                    </div>
                    <small style="color: #6b7280; display: block; margin-top: 4px;">Status is automatically determined: Pass (≥70%) or Fail (<70%)</small>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Final Status</label>
                    <div id="finalStatusBadge" style="padding: 12px; border-radius: 4px; text-align: center; font-weight: 600; font-size: 18px; border: 2px solid #ddd; background-color: #f3f4f6; color: #6b7280;">
                        <span id="finalStatusText">-</span>
                    </div>
                    <small style="color: #6b7280; display: block; margin-top: 4px;">Status is automatically determined: Pass (≥70) or Fail (<70)</small>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="gradeFeedback" style="display: block; margin-bottom: 8px; font-weight: 600;">Feedback</label>
                    <textarea id="gradeFeedback" name="feedback" rows="4" 
                              style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; resize: vertical;"
                              placeholder="Provide feedback on the submitted work..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="modal-btn btn-primary" onclick="submitGrade()" id="submitGradeBtn">
                <i class="fas fa-check" style="margin-right: 4px;"></i>Submit Grade
            </button>
            <button class="modal-btn btn-secondary" onclick="closeGradeTaskModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
// Function to show task details
function showTaskDetails(taskId) {
    // Fetch task details via AJAX
    fetch(`/tasks/${taskId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.task) {
                throw new Error(data.error || 'Failed to load task details');
            }
            
            const task = data.task;
            
            // Populate modal with task data
            document.getElementById('detailTitle').textContent = task.title || 'N/A';
            document.getElementById('detailDescription').textContent = task.description || 'No description provided';
            
            // Priority with color badge
            const priorityEl = document.getElementById('detailPriority');
            if (task.priority) {
                priorityEl.innerHTML = `<span class="task-tag tag-priority-${task.priority}">${task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}</span>`;
            } else {
                priorityEl.textContent = 'Not set';
            }
            
            // Status with badge
            const statusEl = document.getElementById('detailStatus');
            if (task.current_status) {
                const statusText = task.current_status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                statusEl.innerHTML = `<span class="task-tag tag-status">${statusText}</span>`;
            } else {
                statusEl.textContent = 'Not set';
            }
            
            // File types
            const fileTypesEl = document.getElementById('detailFileTypes');
            if (task.allowed_file_types && task.allowed_file_types.length > 0) {
                fileTypesEl.textContent = task.allowed_file_types.join(', ');
            } else {
                fileTypesEl.textContent = 'Any file type';
            }
            
            // Created by (from loaded relationship or fallback)
            const createdByEl = document.getElementById('detailCreatedBy');
            if (task.creator && task.creator.firstname) {
                createdByEl.textContent = `${task.creator.firstname || ''} ${task.creator.lastname || ''}`.trim() || 'Unknown';
            } else {
                createdByEl.textContent = 'Unknown';
            }
            
            // Assigned to (if element exists)
            const assignedToEl = document.getElementById('detailAssignedTo');
            if (assignedToEl) {
                if (task.assignee && task.assignee.firstname) {
                    assignedToEl.textContent = `${task.assignee.firstname || ''} ${task.assignee.lastname || ''}`.trim() || 'Unassigned';
                } else {
                    assignedToEl.textContent = 'Unassigned';
                }
            }
            
            document.getElementById('detailDueDate').textContent = task.due_date ? new Date(task.due_date).toLocaleDateString() : 'No due date';
            document.getElementById('detailCompleted').innerHTML = task.completed ? 
                '<span style="color: #10b981; font-weight: 600;">✓ Yes</span>' : 
                '<span style="color: #6b7280;">✗ No</span>';
            document.getElementById('detailCreatedAt').textContent = task.created_at ? new Date(task.created_at).toLocaleString() : 'Unknown';
            
            // Show status and checked date if graded
            const gradeSection = document.getElementById('detailGradeSection');
            if (task.evaluation && task.evaluation.has_been_graded) {
                gradeSection.style.display = 'block';
                const gradeEl = document.getElementById('detailGrade');
                const status = task.evaluation.status || 'N/A';
                const statusText = status === 'pass' ? 'Pass' : status === 'fail' ? 'Fail' : 'Needs Improvement';
                const statusColor = status === 'pass' ? '#10b981' : status === 'fail' ? '#ef4444' : '#f59e0b';
                const score = task.evaluation.score_percentage || 0;
                gradeEl.innerHTML = `<span style="font-size: 20px; font-weight: bold; color: ${statusColor};">${statusText}</span> <span style="color: #6b7280; margin-left: 8px;">(${score}%)</span>`;
                
                const checkedAtEl = document.getElementById('detailCheckedAt');
                if (task.evaluation.checked_at) {
                    checkedAtEl.textContent = new Date(task.evaluation.checked_at).toLocaleString();
                } else {
                    checkedAtEl.textContent = 'Not available';
                }
            } else {
                gradeSection.style.display = 'none';
            }
            
            // Update modal footer with action buttons
            const footer = document.getElementById('taskDetailsModalFooter');
            let footerHtml = '';
            
            // Check if user can view submission (creator viewing submitted work - available even after grading)
            if (task.can_view_submission) {
                footerHtml = `
                    <button class="modal-btn btn-info" onclick="viewPartnerWork(${task.id})" style="margin-right: 8px;">
                        <i class="fas fa-eye" style="margin-right: 4px;"></i>View Work
                    </button>
                    <button class="modal-btn btn-secondary" onclick="closeTaskDetails()">Close</button>
                `;
            }
            // Check if user can submit this task
            else if (task.can_be_submitted) {
                footerHtml = `
                    <button class="modal-btn btn-success" onclick="submitTaskWork(${task.id})" style="margin-right: 8px;">
                        <i class="fas fa-upload" style="margin-right: 4px;"></i>Submit Work
                    </button>
                    <button class="modal-btn btn-secondary" onclick="closeTaskDetails()">Close</button>
                `;
            } else if (task.can_be_started) {
                footerHtml = `
                    <form action="/tasks/${task.id}/start" method="POST" style="display: inline; margin-right: 8px;">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <button type="submit" class="modal-btn btn-primary">
                            <i class="fas fa-play" style="margin-right: 4px;"></i>Start Task
                        </button>
                    </form>
                    <button class="modal-btn btn-secondary" onclick="closeTaskDetails()">Close</button>
                `;
            } else {
                footerHtml = '<button class="modal-btn btn-secondary" onclick="closeTaskDetails()">Close</button>';
            }
            
            footer.innerHTML = footerHtml;
            
            // Show modal
            document.getElementById('taskDetailsModal').style.display = 'flex';
        })
        .catch(error => {
            console.error('Error fetching task details:', error);
            alert('Failed to load task details: ' + error.message);
        });
}

// Function to close modal
function closeTaskDetails(event) {
    if (!event || event.target.classList.contains('modal-overlay')) {
        document.getElementById('taskDetailsModal').style.display = 'none';
    }
}

// View Partner's Work
function viewPartnerWork(taskId) {
    // Validate taskId
    if (!taskId || taskId === 'null' || taskId === null) {
        console.error('Invalid taskId:', taskId);
        alert('Invalid task ID. Please try again.');
        return;
    }
    
    // Auto-mark as viewed when opening the modal
    fetch(`/tasks/${taskId}/mark-viewed`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
        }
    })
    .catch(error => {
        console.warn('Failed to mark as viewed (non-critical):', error);
        // Continue anyway - this is non-critical
    });
    
    fetch(`/tasks/${taskId}/submission-details`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.error || 'Failed to load submission');
        }
        
        const submission = data.submission;
        const task = data.task;
        const evaluation = data.evaluation;
        
        // Build submission content
        let content = `
            <div style="margin-bottom: 20px;">
                <h4 style="margin-bottom: 10px;">Submitted by: ${submission.submitter_name}</h4>
                <p style="color: #6b7280; font-size: 14px;">Submitted on: ${new Date(submission.created_at).toLocaleString()}</p>
            </div>
        `;
        
        if (submission.submission_notes) {
            content += `
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; display: block; margin-bottom: 8px;">Submission Notes:</label>
                    <div style="background: #f9fafb; padding: 12px; border-radius: 4px; white-space: pre-wrap;">${submission.submission_notes}</div>
                </div>
            `;
        }
        
        if (submission.file_paths && submission.file_paths.length > 0) {
            content += `
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; display: block; margin-bottom: 8px;">Submitted Files:</label>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
            `;
            
            submission.file_paths.forEach((filePath, index) => {
                const fileName = filePath.split('/').pop();
                // Use download route for file access
                const fileUrl = `/submissions/${submission.id}/files/${index}`;
                const viewUrl = submission.file_view_urls && submission.file_view_urls[index] ? submission.file_view_urls[index] : fileUrl;
                const isImage = fileName.match(/\.(jpg|jpeg|png|gif|webp)$/i);
                const isVideo = fileName.match(/\.(mp4|webm|ogg)$/i);
                
                if (isImage) {
                    // For images, show preview with download option
                    content += `
                        <div style="margin-bottom: 8px;">
                            <a href="${viewUrl}" target="_blank" style="display: inline-flex; align-items: center; padding: 8px 12px; background: #eff6ff; border-radius: 4px; text-decoration: none; color: #3b82f6; margin-right: 8px;">
                                <i class="fas fa-image" style="margin-right: 8px;"></i>${fileName}
                                <i class="fas fa-external-link-alt" style="margin-left: 8px; font-size: 12px;"></i>
                            </a>
                            <a href="${fileUrl}" download style="display: inline-flex; align-items: center; padding: 8px 12px; background: #10b981; border-radius: 4px; text-decoration: none; color: white;">
                                <i class="fas fa-download" style="margin-right: 4px;"></i>Download
                            </a>
                        </div>
                    `;
                } else {
                    // For other files, show download link
                    content += `
                        <a href="${fileUrl}" download style="display: inline-flex; align-items: center; padding: 8px 12px; background: #eff6ff; border-radius: 4px; text-decoration: none; color: #3b82f6;">
                            <i class="fas fa-file" style="margin-right: 8px;"></i>${fileName}
                            <i class="fas fa-download" style="margin-left: 8px; font-size: 12px;"></i>
                        </a>
                    `;
                }
            });
            
            content += `</div></div>`;
        }
        
        // Show existing evaluation if already graded
        if (evaluation && evaluation.has_been_graded) {
            const statusColor = evaluation.status === 'pass' ? '#10b981' : evaluation.status === 'fail' ? '#ef4444' : '#f59e0b';
            const statusText = evaluation.status === 'pass' ? 'Pass' : evaluation.status === 'fail' ? 'Fail' : 'Needs Improvement';
            content += `
                <div style="margin-top: 20px; padding: 16px; background: #f0f9ff; border-radius: 4px; border-left: 4px solid ${statusColor};">
                    <h5 style="margin-bottom: 12px; color: #1e40af;">Current Evaluation</h5>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label style="font-weight: 600; display: block; margin-bottom: 4px; color: #6b7280;">Status:</label>
                            <div style="font-size: 20px; font-weight: bold; color: ${statusColor};">${statusText}</div>
                        </div>
                        <div>
                            <label style="font-weight: 600; display: block; margin-bottom: 4px; color: #6b7280;">Score:</label>
                            <div style="font-size: 20px; font-weight: bold; color: #059669;">${evaluation.score_percentage || 0}%</div>
                        </div>
                    </div>
                    ${evaluation.feedback ? `
                        <div style="margin-top: 12px;">
                            <label style="font-weight: 600; display: block; margin-bottom: 4px; color: #6b7280;">Feedback:</label>
                            <div style="background: white; padding: 8px; border-radius: 4px; white-space: pre-wrap;">${evaluation.feedback}</div>
                        </div>
                    ` : ''}
                </div>
            `;
        }
        
        document.getElementById('viewSubmissionContent').innerHTML = content;
        
        // Update footer - only show grade/edit grade button
        let footerHtml = '';
        if (data.can_grade) {
            if (evaluation && evaluation.has_been_graded) {
                // Show edit grade button
                footerHtml = `
                    <button class="modal-btn btn-warning" onclick="openGradeTaskModal(${taskId}, true)" style="margin-right: 8px;">
                        <i class="fas fa-edit" style="margin-right: 4px;"></i>Edit Grade
                    </button>
                `;
            } else {
                // Show grade button (can_grade means they've viewed it)
                footerHtml = `
                    <button class="modal-btn btn-success" onclick="openGradeTaskModal(${taskId}, false)" style="margin-right: 8px;">
                        <i class="fas fa-check-circle" style="margin-right: 4px;"></i>Grade Task
                    </button>
                `;
            }
        }
        footerHtml += `<button class="modal-btn btn-secondary" onclick="closeViewSubmissionModal()">Close</button>`;
        document.getElementById('viewSubmissionFooter').innerHTML = footerHtml;
        
        // Store taskId for later use
        window.currentViewingTaskId = taskId;
        
        // Show modal
        document.getElementById('viewSubmissionModal').style.display = 'flex';
    })
    .catch(error => {
        console.error('Error loading submission:', error);
        alert('Failed to load submission: ' + error.message);
    });
}

function closeViewSubmissionModal(event) {
    if (!event || event.target.classList.contains('modal-overlay')) {
        document.getElementById('viewSubmissionModal').style.display = 'none';
        window.currentViewingTaskId = null;
    }
}

// Grade Task Modal
let currentGradingTaskId = null;
let currentGradingMaxScore = 100;
let currentGradingPassingScore = 70;
window.currentGradingPassingPercentage = 70;

function openGradeTaskModal(taskId, isEdit = false) {
    // Validate taskId
    if (!taskId || taskId === 'null' || taskId === null) {
        console.error('Invalid taskId:', taskId);
        alert('Invalid task ID. Please try again.');
        return;
    }
    
    currentGradingTaskId = taskId;
    
    // Fetch task details to get max score and existing evaluation
    fetch(`/tasks/${taskId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success || !data.task) {
            throw new Error(data.error || 'Failed to load task details');
        }
        
        const task = data.task;
        currentGradingMaxScore = task.max_score || 100;
        currentGradingPassingScore = task.passing_score || 70;
        
        // Calculate passing percentage
        const passingPercentage = currentGradingMaxScore > 0 ? Math.round((currentGradingPassingScore / currentGradingMaxScore) * 100) : 70;
        window.currentGradingPassingPercentage = passingPercentage;
        
        // Update max score display
        document.getElementById('maxScoreDisplay').textContent = currentGradingMaxScore;
        document.getElementById('maxScoreDisplay2').textContent = currentGradingMaxScore;
        document.getElementById('gradeScore').max = currentGradingMaxScore;
        document.getElementById('gradeTaskId').value = taskId;
        
            // If editing, populate form with existing evaluation
            if (isEdit && task.evaluation && task.evaluation.has_been_graded) {
                const eval = task.evaluation;
                // Use score_percentage directly (0-100)
                document.getElementById('gradeScore').value = eval.score_percentage || '';
                document.getElementById('gradeFeedback').value = eval.feedback || '';
                updateStatusDisplay();
                
                // Update modal title and button
                document.querySelector('#gradeTaskModal .modal-title').textContent = 'Edit Evaluation';
                document.getElementById('submitGradeBtn').innerHTML = '<i class="fas fa-save" style="margin-right: 4px;"></i>Update Evaluation';
            } else {
                // Reset form for new evaluation
                document.getElementById('gradeTaskForm').reset();
                document.getElementById('gradeTaskId').value = taskId;
                document.getElementById('gradeScore').value = '';
                
                // Reset status displays
                const statusText = document.getElementById('statusText');
                const statusBadge = document.getElementById('statusBadge');
                const finalStatusText = document.getElementById('finalStatusText');
                const finalStatusBadge = document.getElementById('finalStatusBadge');
                
                if (statusText) statusText.textContent = '-';
                if (statusBadge) {
                    statusBadge.style.backgroundColor = '#f3f4f6';
                    statusBadge.style.color = '#6b7280';
                    statusBadge.style.border = '2px solid #ddd';
                }
                if (finalStatusText) finalStatusText.textContent = '-';
                if (finalStatusBadge) {
                    finalStatusBadge.style.backgroundColor = '#f3f4f6';
                    finalStatusBadge.style.color = '#6b7280';
                    finalStatusBadge.style.border = '2px solid #ddd';
                }
                
                document.querySelector('#gradeTaskModal .modal-title').textContent = 'Evaluate Task';
                document.getElementById('submitGradeBtn').innerHTML = '<i class="fas fa-check" style="margin-right: 4px;"></i>Submit Evaluation';
            }
        
        // Show modal
        document.getElementById('gradeTaskModal').style.display = 'flex';
    })
    .catch(error => {
        console.error('Error loading task:', error);
        alert('Failed to load task: ' + error.message);
    });
}

function closeGradeTaskModal(event) {
    if (!event || event.target.classList.contains('modal-overlay')) {
        document.getElementById('gradeTaskModal').style.display = 'none';
        currentGradingTaskId = null;
    }
}

function updateStatusDisplay() {
    const score = parseInt(document.getElementById('gradeScore').value) || 0;
    const statusBadge = document.getElementById('statusBadge');
    const statusText = document.getElementById('statusText');
    const finalStatusBadge = document.getElementById('finalStatusBadge');
    const finalStatusText = document.getElementById('finalStatusText');
    
    // Check if elements exist
    if (!statusBadge || !statusText || !finalStatusBadge || !finalStatusText) {
        return;
    }
    
    if (score === 0 || !document.getElementById('gradeScore').value) {
        statusText.textContent = '-';
        statusBadge.style.backgroundColor = '#f3f4f6';
        statusBadge.style.color = '#6b7280';
        statusBadge.style.border = '2px solid #ddd';
        
        finalStatusText.textContent = '-';
        finalStatusBadge.style.backgroundColor = '#f3f4f6';
        finalStatusBadge.style.color = '#6b7280';
        finalStatusBadge.style.border = '2px solid #ddd';
        return;
    }
    
    // Auto-determine status: >= 70 = Pass, < 70 = Fail
    const status = score >= 70 ? 'pass' : 'fail';
    const statusDisplay = status === 'pass' ? 'Pass' : 'Fail';
    const statusColor = status === 'pass' ? '#10b981' : '#ef4444';
    const bgColor = status === 'pass' ? '#d1fae5' : '#fee2e2';
    
    // Update Status Preview badge
    statusText.textContent = statusDisplay;
    statusBadge.style.backgroundColor = bgColor;
    statusBadge.style.color = statusColor;
    statusBadge.style.border = `2px solid ${statusColor}`;
    
    // Update Final Status badge (same values)
    finalStatusText.textContent = statusDisplay;
    finalStatusBadge.style.backgroundColor = bgColor;
    finalStatusBadge.style.color = statusColor;
    finalStatusBadge.style.border = `2px solid ${statusColor}`;
}

function submitGrade() {
    // Validate taskId
    if (!currentGradingTaskId || currentGradingTaskId === 'null' || currentGradingTaskId === null) {
        console.error('Invalid taskId:', currentGradingTaskId);
        alert('Invalid task ID. Please try again.');
        return;
    }
    
    const form = document.getElementById('gradeTaskForm');
    const formData = new FormData(form);
    const score = parseInt(document.getElementById('gradeScore').value) || 0;
    
    // Validate score is between 0-100
    if (score < 0 || score > 100) {
        alert('Score must be between 0 and 100');
        return;
    }
    
    // Set score_percentage (0-100)
    formData.set('score_percentage', score);
    
    // Remove status from form data - it will be auto-calculated on server
    formData.delete('status');
    
    fetch(`/tasks/${currentGradingTaskId}/evaluation`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Task graded successfully!');
            closeGradeTaskModal();
            
            // If view modal is open, refresh it to show updated grade
            if (document.getElementById('viewSubmissionModal').style.display === 'flex' && window.currentViewingTaskId) {
                viewPartnerWork(window.currentViewingTaskId);
            }
            
            // Refresh task details if modal is open
            if (document.getElementById('taskDetailsModal').style.display === 'flex') {
                showTaskDetails(currentGradingTaskId);
            }
            
            // Reload page to update task list
            location.reload();
        } else {
            if (data.requires_viewing) {
                alert('You must view the submission before grading it.');
                closeGradeTaskModal();
                if (window.currentViewingTaskId) {
                    viewPartnerWork(window.currentViewingTaskId);
                } else {
                    viewPartnerWork(currentGradingTaskId);
                }
            } else {
                alert('Failed to grade task: ' + (data.error || 'Unknown error'));
            }
        }
    })
    .catch(error => {
        console.error('Error grading task:', error);
        alert('Failed to grade task: ' + error.message);
    });
}
</script>
</aside>
