# Video Call Flow Documentation

## Overview

The video call system uses **WebRTC** for peer-to-peer media streaming and **Firebase Realtime Database** for signaling (offers, answers, ICE candidates). This document explains the complete flow from initiation to termination.

---

## 🔄 Complete Video Call Flow

### Phase 1: Initialization & Setup

#### 1.1 Page Load
```
User opens chat page → session.blade.php loads
```

**What happens:**
- Global variables are set:
  - `window.tradeId` - Current trade ID
  - `window.partnerId` - Partner's user ID
  - `window.authUserId` - Current user's ID
- Firebase is initialized (database only, no auth)
- Video call session data is fetched from API: `/api/trades/get-current-session`
- Firebase room is prepared: `video_rooms/trade_{tradeId}_{user1}_{user2}`

#### 1.2 Firebase Video Integration Initialization
```javascript
firebaseVideoCall = new FirebaseVideoIntegration({
    userId: currentUserId,
    tradeId: tradeId,
    partnerId: partnerId,
    onCallReceived: handleVideoCallOffer,
    onCallAnswered: handleVideoCallAnswer,
    onCallEnded: handleVideoCallEnd
})
```

**What happens:**
- Firebase room is joined: `video_rooms/trade_{tradeId}_{user1}_{user2}`
- User presence is set: `users/{userId}` with status: 'online'
- Firebase listeners are set up for incoming calls

---

### Phase 2: Initiating a Call (Caller)

#### 2.1 User Clicks "Start Video Call" Button
```
User clicks button → window.startVideoCall() → window.startVideoCallFull()
```

**What happens:**
1. Check if `firebaseVideoCall` is initialized
2. Validate `partnerId` exists
3. Update UI: "Initializing..."

#### 2.2 Get User Media (Camera & Microphone)
```javascript
navigator.mediaDevices.getUserMedia({
    video: { width: 1280, height: 720 },
    audio: { echoCancellation: true, noiseSuppression: true }
})
```

**What happens:**
- Browser requests camera/microphone permission
- Local media stream is obtained
- Local video element displays user's camera feed

#### 2.3 Create WebRTC Peer Connection
```javascript
peerConnection = new RTCPeerConnection({
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
        // TURN servers for NAT traversal
    ]
})
```

**What happens:**
- RTCPeerConnection object is created
- Event handlers are attached:
  - `onicecandidate` - Handles ICE candidate generation
  - `ontrack` - Handles incoming remote stream
  - `onconnectionstatechange` - Monitors connection state
  - `oniceconnectionstatechange` - Monitors ICE connection

#### 2.4 Add Local Stream to Peer Connection
```javascript
localStream.getTracks().forEach(track => {
    peerConnection.addTrack(track, localStream)
})
```

**What happens:**
- Audio and video tracks are added to peer connection
- Tracks are sent to the remote peer once connection is established

#### 2.5 Create and Send Offer
```javascript
const offer = await peerConnection.createOffer()
await peerConnection.setLocalDescription(offer)
await sendOffer(offer) // Via Firebase
```

**What happens:**
1. WebRTC creates an SDP (Session Description Protocol) offer
2. Offer is set as local description
3. Offer is sent to Firebase: 
   ```
   video_rooms/{roomId}/calls/{callId}
   {
       type: 'offer',
       fromUserId: callerId,
       toUserId: partnerId,
       offer: { type: 'offer', sdp: '...' },
       callId: 'call_1234567890_userId',
       timestamp: 1234567890
   }
   ```

#### 2.6 ICE Candidate Gathering
```
Browser generates ICE candidates → onicecandidate event → sendIceCandidate()
```

**What happens:**
- Browser discovers network interfaces
- Generates ICE candidates (host, srflx, relay)
- Each candidate is sent to Firebase:
  ```
  video_rooms/{roomId}/calls/{callId}_ice_{timestamp}
  {
      type: 'ice-candidate',
      fromUserId: callerId,
      toUserId: partnerId,
      candidate: { candidate: '...', sdpMLineIndex: 0, sdpMid: '0' },
      callId: 'call_1234567890_userId',
      timestamp: 1234567890
  }
  ```

---

### Phase 3: Receiving a Call (Callee)

#### 3.1 Firebase Listener Detects Incoming Call
```javascript
setupFirebaseListeners() → on('value') → detects call with type: 'offer'
```

**What happens:**
- Firebase listener detects new call in `video_rooms/{roomId}/calls/`
- Checks if `call.toUserId === this.userId` and `call.type === 'offer'`
- Triggers `onCallReceived` callback
- Shows incoming call notification

#### 3.2 User Accepts Call
```
User clicks "Accept" → handleVideoCallOffer() → firebaseVideoCall.answerCall(offer)
```

**What happens:**
1. Get user media (camera/microphone)
2. Create peer connection
3. Add local stream to peer connection
4. Set remote description (the offer):
   ```javascript
   await peerConnection.setRemoteDescription(offer)
   ```

#### 3.3 Create and Send Answer
```javascript
const answer = await peerConnection.createAnswer()
await peerConnection.setLocalDescription(answer)
await sendAnswer(answer) // Via Firebase
```

**What happens:**
1. WebRTC creates an SDP answer
2. Answer is set as local description
3. Answer is sent to Firebase:
   ```
   video_rooms/{roomId}/calls/{callId}
   {
       type: 'answer',
       fromUserId: calleeId,
       toUserId: callerId,
       answer: { type: 'answer', sdp: '...' },
       callId: 'call_1234567890_userId',
       timestamp: 1234567890
   }
   ```

#### 3.4 Process Buffered ICE Candidates
```javascript
// If ICE candidates arrived before remote description was set
pendingRemoteCandidates.forEach(candidate => {
    await peerConnection.addIceCandidate(candidate)
})
```

**What happens:**
- ICE candidates that arrived early are buffered
- Once remote description is set, buffered candidates are processed

---

### Phase 4: Connection Establishment

#### 4.1 Caller Receives Answer
```
Firebase listener detects answer → handleCallAnswer() → setRemoteDescription(answer)
```

**What happens:**
- Caller's Firebase listener detects answer
- Answer is set as remote description
- Buffered ICE candidates are processed

#### 4.2 ICE Candidate Exchange
```
Both peers exchange ICE candidates via Firebase
→ addIceCandidate() on both sides
→ ICE connection negotiation
```

**What happens:**
1. Both peers send ICE candidates to Firebase
2. Each peer receives the other's candidates
3. ICE candidates are added to peer connection
4. WebRTC tries to establish connection using:
   - Direct connection (host candidates)
   - STUN (srflx candidates) for NAT traversal
   - TURN (relay candidates) if direct connection fails

#### 4.3 Connection States
```
checking → connected → completed
```

**Connection States:**
- `new` - Initial state
- `connecting` - ICE negotiation in progress
- `connected` - Connection established
- `disconnected` - Temporary disconnection
- `failed` - Connection failed
- `closed` - Connection closed

#### 4.4 Remote Stream Received
```javascript
peerConnection.ontrack = (event) => {
    remoteStream = event.streams[0]
    remoteVideo.srcObject = remoteStream
    remoteVideo.play()
}
```

**What happens:**
- When connection is established, `ontrack` event fires
- Remote audio/video stream is received
- Remote video element displays partner's camera feed
- Audio is played through speakers

---

### Phase 5: Active Call

#### 5.1 Media Streaming
```
Local Stream → Peer Connection → Remote Peer
Remote Stream ← Peer Connection ← Remote Peer
```

**What happens:**
- Audio/video streams flow directly between peers (P2P)
- If direct connection fails, TURN server relays the streams
- Both users can see and hear each other

#### 5.2 Call Timer
```javascript
startCallTimer() → Updates every second → Displays call duration
```

**What happens:**
- Timer starts when call is established
- Updates UI with call duration (MM:SS format)

#### 5.3 Connection Monitoring
```javascript
onconnectionstatechange → Logs state changes
oniceconnectionstatechange → Handles ICE state changes
```

**What happens:**
- Connection state is monitored continuously
- UI is updated based on connection state
- Automatic reconnection attempts if disconnected

---

### Phase 6: Ending a Call

#### 6.1 User Clicks "End Call"
```
User clicks "End Call" → window.endVideoCall() → firebaseVideoCall.endCall()
```

**What happens:**
1. Stop local media stream:
   ```javascript
   localStream.getTracks().forEach(track => track.stop())
   ```
2. Close peer connection:
   ```javascript
   peerConnection.close()
   ```
3. Send end call signal to Firebase:
   ```
   video_rooms/{roomId}/calls/{callId}_end
   {
       type: 'end-call',
       fromUserId: userId,
       toUserId: partnerId,
       callId: 'call_1234567890_userId',
       timestamp: 1234567890
   }
   ```
4. Detach Firebase listeners
5. Reset call state
6. Update UI

#### 6.2 Partner Receives End Signal
```
Firebase listener detects end-call → handleCallEnd() → Cleanup
```

**What happens:**
- Partner's Firebase listener detects end-call signal
- Local cleanup is performed
- UI is updated
- Call is terminated

---

## 📊 Data Flow Diagram

```
┌─────────────┐                    ┌─────────────┐
│   Caller    │                    │   Callee    │
│  (User A)   │                    │  (User B)   │
└──────┬──────┘                    └──────┬──────┘
       │                                   │
       │ 1. Get User Media                │
       │ (Camera + Microphone)             │
       │                                   │
       │ 2. Create Peer Connection        │
       │                                   │
       │ 3. Create Offer                   │
       │                                   │
       │ 4. Send Offer ──────────────────>│
       │    (via Firebase)                │
       │                                   │ 5. Get User Media
       │                                   │    (Camera + Microphone)
       │                                   │
       │                                   │ 6. Create Peer Connection
       │                                   │
       │                                   │ 7. Set Remote Description
       │                                   │    (Offer)
       │                                   │
       │                                   │ 8. Create Answer
       │                                   │
       │ <──────────────────────────────── │ 9. Send Answer
       │    (via Firebase)                │
       │                                   │
       │ 10. Set Remote Description       │
       │     (Answer)                      │
       │                                   │
       │ 11. Exchange ICE Candidates      │
       │     (via Firebase)               │
       │ <────────────────────────────────>│
       │                                   │
       │ 12. WebRTC Connection            │
       │     Established                   │
       │                                   │
       │ 13. Media Streaming (P2P)        │
       │ <────────────────────────────────>│
       │                                   │
       │ 14. End Call                     │
       │     (via Firebase)               │
       │ <────────────────────────────────>│
       │                                   │
```

---

## 🔑 Key Components

### Firebase Realtime Database Structure
```
video_rooms/
  trade_{tradeId}_{user1}_{user2}/
    metadata/
      tradeId: 1
      user1: 5
      user2: 6
      createdAt: 1234567890
      maxUsers: 2
    users/
      {userId}/
        userId: 5
        status: 'online'
        ready: false
        joinedAt: 1234567890
        lastSeen: 1234567890
    calls/
      {callId}/
        type: 'offer' | 'answer' | 'ice-candidate' | 'end-call'
        fromUserId: 5
        toUserId: 6
        offer: { type: 'offer', sdp: '...' }
        answer: { type: 'answer', sdp: '...' }
        candidate: { candidate: '...', sdpMLineIndex: 0, sdpMid: '0' }
        callId: 'call_1234567890_5'
        timestamp: 1234567890
```

### WebRTC Configuration
- **STUN Servers**: For NAT discovery
  - `stun:stun.l.google.com:19302`
  - `stun:stun1.l.google.com:19302`
- **TURN Servers**: For NAT traversal (relay)
  - `turn:asia.relay.metered.ca:80`
  - Credentials: Metered.ca API

### Event Handlers
- `onicecandidate` - ICE candidate generation
- `ontrack` - Remote stream received
- `onconnectionstatechange` - Connection state changes
- `oniceconnectionstatechange` - ICE connection state changes
- `onicegatheringstatechange` - ICE gathering state changes

---

## 🐛 Error Handling

### Common Issues & Solutions

1. **Permission Denied (Camera/Microphone)**
   - User must grant browser permissions
   - Check browser settings

2. **ICE Connection Failed**
   - May need TURN server for NAT traversal
   - Check firewall/network settings

3. **Firebase Permission Denied**
   - Update Firebase security rules (see `FIREBASE_RULES_UPDATE_GUIDE.md`)
   - Ensure rules allow unauthenticated access to `video_rooms`

4. **No Remote Stream**
   - Check if peer connection is established
   - Verify remote description is set
   - Check browser console for errors

5. **Connection Timeout**
   - Check network connectivity
   - Verify TURN servers are accessible
   - Check firewall settings

---

## 📝 Notes

- **No Firebase Authentication Required**: Video calls use Laravel authentication. Firebase is only used for signaling.
- **P2P Connection**: Media streams flow directly between peers when possible.
- **TURN Fallback**: If direct connection fails, TURN server relays the streams.
- **Real-time Signaling**: Firebase Realtime Database provides instant signaling updates.
- **Buffering**: ICE candidates are buffered if they arrive before remote description is set.

---

## 🔄 State Machine

```
[Idle]
  │
  ├─> [Initializing] (Get media, create peer connection)
  │     │
  │     ├─> [Calling] (Offer sent, waiting for answer)
  │     │     │
  │     │     ├─> [Connecting] (Answer received, ICE negotiation)
  │     │     │     │
  │     │     │     ├─> [Connected] (Media streaming)
  │     │     │     │     │
  │     │     │     │     └─> [Ended] (Call terminated)
  │     │     │     │
  │     │     │     └─> [Failed] (Connection failed)
  │     │     │
  │     │     └─> [Rejected] (Call rejected)
  │     │
  │     └─> [Incoming] (Offer received, waiting for user)
  │           │
  │           ├─> [Answering] (User accepted, creating answer)
  │           │     │
  │           │     └─> [Connecting] → [Connected] → [Ended]
  │           │
  │           └─> [Rejected] (User declined)
  │
  └─> [Error] (Initialization failed)
```

---

## 🚀 Quick Reference

### Starting a Call
```javascript
firebaseVideoCall.startCall(partnerId)
```

### Answering a Call
```javascript
firebaseVideoCall.answerCall(offer)
```

### Ending a Call
```javascript
firebaseVideoCall.endCall()
```

### Checking Connection State
```javascript
peerConnection.connectionState // 'new', 'connecting', 'connected', 'disconnected', 'failed', 'closed'
peerConnection.iceConnectionState // 'new', 'checking', 'connected', 'completed', 'failed', 'disconnected', 'closed'
```

