# Testing Guide - SkillsXchange Chat Session

This document provides comprehensive testing procedures for the chat session functionality, including chat, video calls, and task management.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Manual Testing Steps](#manual-testing-steps)
3. [Browser Console Tests](#browser-console-tests)
4. [Common Issues & Solutions](#common-issues--solutions)
5. [Debugging Checklist](#debugging-checklist)
6. [Performance Checks](#performance-checks)
7. [Integration Testing](#integration-testing)

---

## Prerequisites

Before testing, ensure:

- ✅ Laravel application is running
- ✅ Database is migrated and seeded
- ✅ Pusher/Laravel Echo is configured
- ✅ Firebase is configured (for video calls)
- ✅ Two user accounts are available for testing
- ✅ A trade session is active between the two users
- ✅ Browser console is open (F12)
- ✅ HTTPS is enabled (required for camera access)

---

## Manual Testing Steps

### 1. Chat Functionality Testing

#### Test 1.1: Send a Message
1. Open the chat session page
2. Type a message in the message input field
3. Click "Send" or press Enter
4. **Expected Result:**
   - Message appears immediately in chat (optimistic update)
   - Message shows "Sending..." state briefly
   - Message is confirmed and timestamped
   - Message appears in correct position (right side for own messages)

#### Test 1.2: Receive a Message (Real-time)
1. Open the chat session in two different browsers/incognito windows
2. Log in as different users in each window
3. Send a message from User A
4. **Expected Result:**
   - Message appears in User B's chat immediately
   - Flash effect shows on new message
   - "NEW" indicator appears briefly
   - Message appears on left side (partner's messages)

#### Test 1.3: Message with Emoji
1. Click the emoji button (😊)
2. Select an emoji from the picker
3. Type additional text
4. Send the message
5. **Expected Result:**
   - Emoji picker opens/closes correctly
   - Emoji is inserted at cursor position
   - Message displays with emoji correctly

#### Test 1.4: Long Messages
1. Type a very long message (500+ characters)
2. Send the message
3. **Expected Result:**
   - Message wraps correctly
   - No layout breaking
   - Scrollbar appears if needed

#### Test 1.5: Special Characters & HTML
1. Send messages with:
   - HTML tags: `<script>alert('xss')</script>`
   - Special characters: `!@#$%^&*()`
   - Unicode: `你好 مرحبا`
2. **Expected Result:**
   - HTML is escaped (not executed)
   - Special characters display correctly
   - No XSS vulnerabilities

#### Test 1.6: Image/Video Messages
1. Click the image/video upload button
2. Select a file
3. Send the message
4. **Expected Result:**
   - File uploads successfully
   - Preview appears in chat
   - File is accessible to partner

---

### 2. Video Call Testing

#### Test 2.1: Open Video Chat Modal
1. Click the video call button (📹) in the header
2. **Expected Result:**
   - Modal opens
   - Camera permission prompt appears
   - Local video stream displays
   - Video is muted (no echo)

#### Test 2.2: Start a Video Call
1. Open video chat modal
2. Allow camera/microphone permissions
3. Click "Start Call" button
4. **Expected Result:**
   - Call status shows "Initializing..."
   - Firebase connection established
   - Remote video appears when partner answers
   - Call timer starts

#### Test 2.3: Receive Incoming Call
1. User A starts a call
2. User B should see:
   - Incoming call notification
   - Ringtone plays
   - Modal opens automatically
   - "Incoming call..." status
3. User B clicks "Start Call" to accept
4. **Expected Result:**
   - Call connects
   - Both users see each other's video
   - Audio works (test with headphones to avoid echo)

#### Test 2.4: End a Call
1. During an active call, click "End Call"
2. **Expected Result:**
   - Call ends immediately
   - Video streams stop
   - Modal can be closed
   - Call timer stops
   - Both users see "Call ended" status

#### Test 2.5: Camera Access Denied
1. Deny camera permission when prompted
2. **Expected Result:**
   - Error message displays clearly
   - Modal closes
   - Helpful instructions shown

#### Test 2.6: HTTPS Requirement
1. Access page via HTTP (not HTTPS)
2. Try to open video chat
3. **Expected Result:**
   - Warning message about HTTPS requirement
   - Clear instructions to use HTTPS

---

### 3. Task Management Testing

#### Test 3.1: Create a Task
1. Click "Add Task" button in tasks sidebar
2. Fill in task form:
   - Title: "Test Task"
   - Description: "This is a test"
   - Priority: Medium
   - Assign to: Partner
3. Click "Create Task"
4. **Expected Result:**
   - Modal closes
   - Task appears in appropriate tab (My Tasks or Partner Tasks)
   - Success notification shows
   - Task count updates

#### Test 3.2: Toggle Task Completion
1. Click checkbox on a task
2. **Expected Result:**
   - Checkbox toggles immediately
   - Task title gets strikethrough
   - Progress bar updates
   - Progress percentage recalculates

#### Test 3.3: Edit a Task
1. Click "Edit" button on a task you created
2. Modify task details
3. Save changes
4. **Expected Result:**
   - Edit modal opens with pre-filled data
   - Changes save successfully
   - Task updates in UI
   - Page may reload to show changes

#### Test 3.4: Delete a Task
1. Click "Delete" button on a task you created
2. Confirm deletion
3. **Expected Result:**
   - Confirmation dialog appears
   - Task is removed from UI
   - Task count updates
   - Success notification shows

#### Test 3.5: Real-time Task Updates
1. User A creates a task assigned to User B
2. **Expected Result:**
   - Task appears in User B's "My Tasks" tab immediately
   - Notification shows for User B
   - Task count updates for both users

#### Test 3.6: Task Progress Calculation
1. Create multiple tasks
2. Complete some tasks
3. **Expected Result:**
   - Progress bar fills correctly
   - Percentage matches completed/total ratio
   - Both "My Tasks" and "Partner Tasks" show correct progress

#### Test 3.7: Task with Submission Requirements
1. Create a task with "Requires Submission" checked
2. Select file types
3. Add submission instructions
4. **Expected Result:**
   - Task shows "Submission Required" badge
   - File type options are saved
   - Instructions are stored

---

### 4. Real-time Updates Testing

#### Test 4.1: Echo Connection
1. Open browser console
2. Check Echo connection status
3. **Expected Result:**
   - `window.Echo` is defined
   - Connection state is "connected"
   - No connection errors in console

#### Test 4.2: Message Broadcasting
1. Send a message from User A
2. Check User B's console
3. **Expected Result:**
   - Echo event "new-message" is received
   - Message appears without page refresh
   - Console shows event data

#### Test 4.3: Task Broadcasting
1. Create a task from User A
2. Check User B's console
3. **Expected Result:**
   - Echo event "task-created" is received
   - Task appears in User B's UI
   - Console shows task data

#### Test 4.4: Connection Loss Recovery
1. Disconnect internet briefly
2. Reconnect
3. **Expected Result:**
   - Echo reconnects automatically
   - Connection status updates
   - Missed messages are fetched via polling fallback

---

## Browser Console Tests

### Quick Access
Open browser console (F12) and use these commands:

### Test Chat Manager

```javascript
// Check if chat manager is initialized
window.app.chat

// Send a test message
window.app.chat.sendMessage('Test message from console')

// Check messages container
window.app.chat.messagesContainer

// Scroll to bottom
window.app.chat.scrollToBottom()

// Check connection status
window.app.chat.updateConnectionStatus('connected')
```

### Test Video Call Manager

```javascript
// Check if video manager is initialized
window.app.video

// Open video chat modal
window.app.video.openVideoChat()

// Check if Firebase is available
window.firebaseVideoCall

// Check video call state
window.app.video.videoCallState

// Start a call (if partner is available)
window.app.video.startCall()

// End current call
window.app.video.endCall()
```

### Test Task Manager

```javascript
// Check if task manager is initialized
window.app.tasks

// Create a test task
window.app.tasks.createTask({
    title: 'Console Test Task',
    description: 'Created from browser console',
    assigned_to: window.app.partnerId,
    priority: 'medium'
})

// Toggle a task (replace 1 with actual task ID)
window.app.tasks.toggleTask(1)

// Update progress manually
window.app.tasks.updateProgress()

// Show add task modal
window.app.tasks.showAddTaskModal()
```

### Test Echo Connection

```javascript
// Check Echo availability
typeof window.Echo

// Check connection state
window.Echo.connector.pusher.connection.state

// Check if connected
window.Echo.connector.pusher.connection.state === 'connected'

// Manually listen to a channel
window.Echo.channel(`trade-${window.app.tradeId}`)
    .listen('new-message', (data) => {
        console.log('Message received:', data)
    })
```

### Test All Managers

```javascript
// Check all managers
window.app

// Get session data
{
    tradeId: window.app.tradeId,
    userId: window.app.userId,
    partnerId: window.app.partnerId,
    partnerName: window.app.partnerName
}

// Check manager initialization status
{
    chat: !!window.app.chat,
    video: !!window.app.video,
    tasks: !!window.app.tasks
}
```

---

## Common Issues & Solutions

### Issue 1: Echo Not Connecting

**Symptoms:**
- Messages not appearing in real-time
- Console shows connection errors
- "Connecting..." status persists

**Solutions:**
1. Check Pusher credentials in `.env`:
   ```
   PUSHER_APP_ID=your_app_id
   PUSHER_APP_KEY=your_app_key
   PUSHER_APP_SECRET=your_app_secret
   PUSHER_APP_CLUSTER=your_cluster
   ```

2. Verify Echo initialization:
   ```javascript
   console.log(window.Echo.connector.pusher.connection.state)
   ```

3. Check network tab for WebSocket connection (ws:// or wss://)

4. Verify broadcasting routes are accessible:
   - `/broadcasting/auth` should return 200

5. Check Laravel logs for authentication errors

### Issue 2: Camera Access Denied

**Symptoms:**
- Video modal opens but no video displays
- Error message about camera access

**Solutions:**
1. **Check HTTPS:** Camera requires HTTPS (except localhost)
   - Use `https://` URL
   - Or use `localhost` for development

2. **Browser Permissions:**
   - Chrome: Settings → Privacy → Site Settings → Camera
   - Firefox: Preferences → Privacy → Permissions → Camera
   - Allow camera for your domain

3. **Check Browser Console:**
   ```javascript
   navigator.mediaDevices.getUserMedia({ video: true, audio: true })
       .then(stream => console.log('Camera access granted'))
       .catch(error => console.error('Camera error:', error))
   ```

4. **Test Camera:**
   - Visit `https://webcammictest.com/` to verify camera works

### Issue 3: CSRF Token Errors

**Symptoms:**
- 419 errors in console
- "CSRF token mismatch" errors
- Requests failing with authentication errors

**Solutions:**
1. **Check CSRF Token:**
   ```javascript
   document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
   ```

2. **Verify Token in Headers:**
   - Check Network tab
   - Ensure `X-CSRF-TOKEN` header is present
   - Token should match meta tag value

3. **Refresh Page:**
   - CSRF tokens expire after session timeout
   - Refresh page to get new token

4. **Check Session:**
   - Verify user is logged in
   - Check Laravel session is active

### Issue 4: Task Not Updating

**Symptoms:**
- Task changes don't appear
- Progress bar doesn't update
- Real-time updates not working

**Solutions:**
1. **Check Echo Connection:**
   ```javascript
   window.Echo.connector.pusher.connection.state
   ```

2. **Verify Task Events:**
   ```javascript
   window.Echo.channel(`trade-${window.app.tradeId}`)
       .listen('task-updated', (data) => {
           console.log('Task update received:', data)
       })
   ```

3. **Check Task Manager:**
   ```javascript
   window.app.tasks.updateProgress()
   window.app.tasks.updateTaskCount()
   ```

4. **Manual Refresh:**
   - Reload page to see if changes persist
   - Check database directly

5. **Check Backend:**
   - Verify task events are being broadcast
   - Check Laravel logs for errors

### Issue 5: Messages Not Sending

**Symptoms:**
- Message stays in "Sending..." state
- Error messages appear
- Messages don't appear in chat

**Solutions:**
1. **Check Network Tab:**
   - Look for POST request to `/chat/{tradeId}/message`
   - Check response status (should be 200)
   - Check response body for errors

2. **Verify Route:**
   - Ensure route exists: `Route::post('/chat/{tradeId}/message', ...)`
   - Check route is accessible

3. **Check Chat Manager:**
   ```javascript
   window.app.chat.sendMessage('test')
   ```

4. **Check Console Errors:**
   - Look for JavaScript errors
   - Check for CORS issues

### Issue 6: Video Call Not Starting

**Symptoms:**
- "Start Call" button does nothing
- Firebase errors in console
- No video connection

**Solutions:**
1. **Check Firebase:**
   ```javascript
   typeof window.firebaseVideoCall
   window.firebaseVideoCall?.initialize()
   ```

2. **Verify Firebase Config:**
   - Check `firebase-config.js` is loaded
   - Verify Firebase credentials

3. **Check Partner ID:**
   ```javascript
   window.app.partnerId
   ```

4. **Check Network:**
   - Ensure Firebase servers are accessible
   - Check firewall settings

5. **Check Browser Console:**
   - Look for Firebase initialization errors
   - Check WebRTC connection errors

---

## Debugging Checklist

Use this checklist when debugging issues:

### Initial Checks
- [ ] Browser console is open (F12)
- [ ] No JavaScript errors in console
- [ ] Network tab shows no failed requests
- [ ] User is logged in
- [ ] Session is active

### Manager Initialization
- [ ] `window.app` is defined
- [ ] `window.app.chat` exists
- [ ] `window.app.video` exists
- [ ] `window.app.tasks` exists
- [ ] All managers show "initialized" in console

### Echo Connection
- [ ] `window.Echo` is defined
- [ ] Connection state is "connected"
- [ ] No connection errors in console
- [ ] WebSocket connection is active (check Network tab)

### Firebase
- [ ] `window.firebaseVideoCall` is defined (for video calls)
- [ ] Firebase config is loaded
- [ ] No Firebase errors in console

### Data Validation
- [ ] `window.app.tradeId` is valid
- [ ] `window.app.userId` is valid
- [ ] `window.app.partnerId` is valid
- [ ] CSRF token is present

### Feature-Specific Checks

#### Chat
- [ ] Message form exists: `document.getElementById('message-form')`
- [ ] Messages container exists: `document.getElementById('messages')`
- [ ] Echo channel is subscribed: `window.Echo.channel('trade-{id}')`

#### Video Calls
- [ ] Video modal exists: `document.getElementById('video-chat-modal')`
- [ ] Local video element exists: `document.getElementById('local-video')`
- [ ] Camera permissions are granted
- [ ] HTTPS is enabled (or localhost)

#### Tasks
- [ ] Tasks sidebar exists: `document.querySelector('.tasks-sidebar')`
- [ ] Add task button exists: `document.querySelector('.add-task-btn')`
- [ ] Task lists exist: `document.getElementById('my-tasks')`

---

## Performance Checks

### Message Load Time
1. Open chat session
2. Check Network tab
3. Find request to `/chat/{tradeId}/messages`
4. **Target:** < 500ms response time
5. **Check:**
   ```javascript
   console.time('Message Load')
   fetch(`/chat/${window.app.tradeId}/messages`)
       .then(() => console.timeEnd('Message Load'))
   ```

### Video Call Connection Time
1. Start a video call
2. Measure time from "Start Call" to video appearing
3. **Target:** < 5 seconds
4. **Check:**
   ```javascript
   const startTime = Date.now()
   window.app.video.startCall()
   // Check when remote video appears
   const connectionTime = Date.now() - startTime
   console.log('Connection time:', connectionTime, 'ms')
   ```

### Task Update Latency
1. Create a task from User A
2. Measure time until it appears for User B
3. **Target:** < 1 second (real-time)
4. **Check:**
   ```javascript
   const startTime = Date.now()
   window.app.tasks.createTask({...})
   // Check when task appears in partner's UI
   const latency = Date.now() - startTime
   console.log('Task update latency:', latency, 'ms')
   ```

### Echo Event Latency
1. Send a message
2. Measure time until Echo event is received
3. **Target:** < 100ms
4. **Check:**
   ```javascript
   const startTime = Date.now()
   window.Echo.channel(`trade-${window.app.tradeId}`)
       .listen('new-message', (data) => {
           const latency = Date.now() - startTime
           console.log('Echo latency:', latency, 'ms')
       })
   ```

### Memory Usage
1. Open browser DevTools → Memory tab
2. Take heap snapshot
3. Use app for 10 minutes
4. Take another snapshot
5. **Check:** No memory leaks (memory should stabilize)

### CPU Usage
1. Open browser DevTools → Performance tab
2. Record while using app
3. **Check:** No excessive CPU usage during normal operation

---

## Integration Testing

### Test Complete User Flow

1. **User A:**
   - Opens chat session
   - Sends a message
   - Creates a task for User B
   - Starts a video call

2. **User B:**
   - Receives message in real-time
   - Sees new task notification
   - Receives incoming call notification
   - Answers call
   - Completes task

3. **Verify:**
   - All events appear in real-time
   - No page refreshes needed
   - All features work together
   - No conflicts between managers

### Test Error Recovery

1. **Simulate Network Issues:**
   - Disconnect internet
   - Try to send message
   - Reconnect internet
   - **Expected:** Message sends after reconnection

2. **Simulate Echo Disconnection:**
   - Stop Pusher service
   - Try to send message
   - Restart Pusher
   - **Expected:** Polling fallback activates, then Echo reconnects

3. **Simulate Firebase Issues:**
   - Block Firebase domains
   - Try to start video call
   - **Expected:** Error message, graceful degradation

---

## Reporting Issues

When reporting issues, include:

1. **Browser & Version:** e.g., Chrome 120.0
2. **Console Errors:** Copy all errors from console
3. **Network Tab:** Screenshot of failed requests
4. **Steps to Reproduce:** Detailed steps
5. **Expected vs Actual:** What should happen vs what happened
6. **Manager Status:** Output of `window.app`
7. **Echo Status:** `window.Echo.connector.pusher.connection.state`
8. **Screenshots:** If applicable

---

## Quick Test Commands

Copy and paste these into browser console for quick testing:

```javascript
// Full system check
(() => {
    console.log('=== System Check ===')
    console.log('App:', !!window.app)
    console.log('Chat:', !!window.app?.chat)
    console.log('Video:', !!window.app?.video)
    console.log('Tasks:', !!window.app?.tasks)
    console.log('Echo:', typeof window.Echo)
    console.log('Echo State:', window.Echo?.connector?.pusher?.connection?.state)
    console.log('Firebase:', typeof window.firebaseVideoCall)
    console.log('Trade ID:', window.app?.tradeId)
    console.log('User ID:', window.app?.userId)
    console.log('Partner ID:', window.app?.partnerId)
})()
```

---

**Last Updated:** 2025-01-XX
**Version:** 1.0.0

