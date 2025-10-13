// Simple peer-to-peer signaling using a data channel simulation
// In production, this would use WebSockets and a signaling server

let localStream = null;
let peerConnections = {};
let sessionId = null;
let isSharing = false;
let viewerCount = 0;

// Configuration for WebRTC
const config = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' }
    ]
};

// Generate unique session ID
function generateSessionId() {
    return 'session_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
}

// Start screen sharing
async function startSharing() {
    try {
        updateStatus('Requesting screen access...', 'Please select the screen or window you want to share');
        
        // Request screen capture
        localStream = await navigator.mediaDevices.getDisplayMedia({
            video: {
                cursor: 'always',
                displaySurface: 'monitor'
            },
            audio: false
        });
        
        // Display local preview
        document.getElementById('localVideo').srcObject = localStream;
        document.getElementById('videoContainer').classList.add('active');
        
        // Generate session and share link
        sessionId = generateSessionId();
        const shareUrl = window.location.origin + window.location.pathname + '?view=' + sessionId;
        document.getElementById('shareLink').value = shareUrl;
        document.getElementById('shareLinkContainer').classList.add('active');
        
        // Update UI
        document.getElementById('startBtn').disabled = true;
        document.getElementById('stopBtn').disabled = false;
        isSharing = true;
        
        updateStatus('✅ Sharing active', 'Your screen is being shared. Send the link to your co-worker.');
        
        // Store session info (in production, this would be on a server)
        storeSession(sessionId, localStream);
        
        // Handle stream end
        localStream.getVideoTracks()[0].onended = () => {
            stopSharing();
        };
        
        // Simulate viewer connection monitoring
        startViewerMonitoring();
        
    } catch (err) {
        console.error('Error starting screen share:', err);
        updateStatus('❌ Error', 'Could not access screen. ' + err.message);
    }
}

// Stop screen sharing
function stopSharing() {
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop());
        localStream = null;
    }
    
    // Close all peer connections
    Object.values(peerConnections).forEach(pc => pc.close());
    peerConnections = {};
    
    document.getElementById('videoContainer').classList.remove('active');
    document.getElementById('shareLinkContainer').classList.remove('active');
    document.getElementById('localVideo').srcObject = null;
    
    document.getElementById('startBtn').disabled = false;
    document.getElementById('stopBtn').disabled = true;
    isSharing = false;
    viewerCount = 0;
    
    updateStatus('Sharing stopped', 'Click "Start Screen Share" to begin a new session');
    
    // Clear session
    if (sessionId) {
        clearSession(sessionId);
        sessionId = null;
    }
}

// Copy share link to clipboard
function copyLink() {
    const linkInput = document.getElementById('shareLink');
    linkInput.select();
    linkInput.setSelectionRange(0, 99999);
    
    navigator.clipboard.writeText(linkInput.value).then(() => {
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '✅ Copied!';
        setTimeout(() => {
            btn.innerHTML = originalText;
        }, 2000);
    });
}

// Update status display
function updateStatus(text, detail) {
    document.getElementById('statusText').textContent = text;
    document.getElementById('statusDetail').textContent = detail;
}

// Store session (simulated - in production use server)
function storeSession(id, stream) {
    sessionStorage.setItem('screen_share_' + id, JSON.stringify({
        id: id,
        started: Date.now(),
        active: true
    }));
}

// Clear session
function clearSession(id) {
    sessionStorage.removeItem('screen_share_' + id);
}

// Monitor viewers (simulated)
function startViewerMonitoring() {
    setInterval(() => {
        if (isSharing) {
            // In production, this would query the server for actual viewer count
            document.getElementById('viewerCount').textContent = viewerCount + ' viewer' + (viewerCount !== 1 ? 's' : '');
            
            // Update connection stats
            updateConnectionStats();
        }
    }, 1000);
}

// Update connection statistics
function updateConnectionStats() {
    const statsHtml = `
        <div class="stat-item">
            <div class="stat-label">Connected Viewers</div>
            <div class="stat-value">${viewerCount}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Session Duration</div>
            <div class="stat-value">${getSessionDuration()}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Status</div>
            <div class="stat-value">🟢 Live</div>
        </div>
    `;
    document.getElementById('connectionStats').innerHTML = statsHtml;
}

// Get session duration
function getSessionDuration() {
    if (!sessionId) return '0:00';
    const stored = sessionStorage.getItem('screen_share_' + sessionId);
    if (!stored) return '0:00';
    const data = JSON.parse(stored);
    const seconds = Math.floor((Date.now() - data.started) / 1000);
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return mins + ':' + (secs < 10 ? '0' : '') + secs;
}

// Check if this is a viewer joining
function checkForViewMode() {
    const params = new URLSearchParams(window.location.search);
    const viewSessionId = params.get('view');
    
    if (viewSessionId) {
        // This is a viewer
        initializeViewer(viewSessionId);
    }
}

// Initialize viewer mode
async function initializeViewer(viewSessionId) {
    document.querySelector('.screen-sharing-container').innerHTML = `
        <h1>🖥️ Screen Share Viewer</h1>
        <p class="subtitle">Viewing co-worker's screen</p>
        
        <div class="status">
            <div class="status-text">Connecting to screen share...</div>
            <div class="status-detail">Please wait while we establish the connection</div>
        </div>
        
        <div id="videoContainer" class="video-container active">
            <div class="video-overlay">
                <span>👁️</span>
                <span>Viewing shared screen</span>
            </div>
            <video id="remoteVideo" autoplay playsinline style="width: 100%; height: auto;"></video>
        </div>
        
        <div class="info-box">
            <strong>ℹ️ Viewer Mode:</strong>
            You are viewing a co-worker's screen in real-time. This is useful for:
            <ul style="margin-left: 20px; margin-top: 8px;">
                <li>Reviewing customer information together</li>
                <li>Collaborative troubleshooting</li>
                <li>Training and demonstrations</li>
                <li>Code reviews and testing</li>
            </ul>
        </div>
    `;
    
    // In a real implementation, this would connect via WebRTC
    // For this demo, we'll show a message about production requirements
    setTimeout(() => {
        document.querySelector('.status-text').textContent = '⚠️ Production Server Required';
        document.querySelector('.status-detail').textContent = 
            'This demo requires a WebRTC signaling server to connect viewers. In production, viewers would see the shared screen here in real-time using WebRTC peer-to-peer connections.';
    }, 1500);
}

// Initialize on page load
window.onload = () => {
    checkForViewMode();
};

// Handle page unload
window.onbeforeunload = () => {
    if (isSharing) {
        return 'You are currently sharing your screen. Are you sure you want to leave?';
    }
};