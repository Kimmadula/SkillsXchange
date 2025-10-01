# Video Call Deployment Guide

## 🎥 Video Call Feature Deployment Fix

This guide explains the fixes implemented to make the video call feature work properly when deployed to Render.

## ✅ Issues Fixed

### 1. **WebSocket Server Deployment**
- **Problem**: WebSocket server was not running in production
- **Solution**: Updated `render.yaml` to use multiple services approach
- **Files Modified**: `render.yaml`, `start-render.sh`

### 2. **WebSocket Client Configuration**
- **Problem**: Hardcoded port 8080 not accessible in production
- **Solution**: Added environment-aware connection logic with fallbacks
- **Files Modified**: `resources/views/websocket-video-call.js`

### 3. **Process Management**
- **Problem**: No mechanism to run both main app and WebSocket server
- **Solution**: Created separate services in Render configuration
- **Files Modified**: `render.yaml`

## 🔧 Configuration Changes

### Render Configuration (`render.yaml`)
```yaml
services:
  # Main Laravel Application
  - type: web
    name: skillsxchangee-main
    # ... main app config ...
  
  # WebSocket Signaling Server
  - type: web
    name: skillsxchangee-websocket
    startCommand: php artisan websocket:start --port=8080
    healthCheckPath: /health
    # ... same env vars as main service ...
```

### WebSocket Client (`websocket-video-call.js`)
- Added environment detection
- Added multiple connection fallbacks
- Added robust error handling
- Added alternative connection methods

### Health Check Endpoint (`public/health.php`)
- Created health check endpoint for WebSocket service
- Used by Render to verify service status

## 🚀 Deployment Process

### 1. **Deploy to Render**
```bash
# Push changes to your repository
git add .
git commit -m "Fix video call deployment configuration"
git push origin main
```

### 2. **Verify Services**
After deployment, you should have two services running:
- `skillsxchangee-main` - Main Laravel application
- `skillsxchangee-websocket` - WebSocket signaling server

### 3. **Test Video Calls**
1. Open your deployed application
2. Navigate to a trade page
3. Try to start a video call
4. Check browser console for connection logs

## 🧪 Testing

### Local Testing
1. Start WebSocket server: `php artisan websocket:start --port=8080`
2. Start main app: `php artisan serve`
3. Open `test-video-call-deployment.html` in browser
4. Run connection tests

### Production Testing
1. Open `https://your-app.onrender.com/test-video-call-deployment.html`
2. Run the deployment test
3. Check connection status and logs

## 🔍 Troubleshooting

### Common Issues

#### 1. **WebSocket Connection Failed**
- **Cause**: WebSocket service not running or wrong URL
- **Solution**: Check Render service status, verify service names

#### 2. **Port 8080 Not Accessible**
- **Cause**: Render doesn't expose custom ports
- **Solution**: Use separate service approach (implemented)

#### 3. **CORS Issues**
- **Cause**: Cross-origin WebSocket connections
- **Solution**: Ensure both services use same domain/subdomain

### Debug Steps

1. **Check Service Status**
   ```bash
   # Check if WebSocket service is running
   curl https://skillsxchangee-websocket.onrender.com/health
   ```

2. **Check Browser Console**
   - Look for WebSocket connection logs
   - Check for error messages
   - Verify connection URLs

3. **Check Render Logs**
   - View service logs in Render dashboard
   - Look for WebSocket server startup messages
   - Check for any errors

## 📋 Service URLs

After deployment, your services will be available at:
- **Main App**: `https://skillsxchangee-main.onrender.com`
- **WebSocket Service**: `https://skillsxchangee-websocket.onrender.com`

## 🔄 Fallback Strategy

The WebSocket client now tries multiple connection methods:
1. Primary: WebSocket service URL
2. Fallback 1: Same hostname with port 8080
3. Fallback 2: Alternative subdomain
4. Fallback 3: Localhost (for development)

## ✅ Success Indicators

Video calls are working correctly when:
- ✅ WebSocket connection establishes successfully
- ✅ ICE candidates are exchanged
- ✅ Media streams are transmitted
- ✅ Call controls work (mute, video toggle, end call)
- ✅ Notifications work for incoming calls

## 🆘 Support

If you encounter issues:
1. Check the test page: `/test-video-call-deployment.html`
2. Review browser console logs
3. Check Render service logs
4. Verify both services are running

## 📝 Notes

- The WebSocket service runs on port 8080 internally
- Render handles the external port mapping
- Both services share the same environment variables
- Health checks ensure service availability
