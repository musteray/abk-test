const ALLOWED_ORIGIN = window.location.origin;
let currentExpression = '';

// Listen for messages from iframes
window.addEventListener('message', function(event) {
    // Security: Verify the message origin
    if (event.origin !== ALLOWED_ORIGIN) {
        console.warn('Message from unauthorized origin blocked:', event.origin);
        return;
    }

    // Security: Validate message structure
    if (!event.data || typeof event.data.type !== 'string') {
        return;
    }

    if (event.data.type === 'button') {
        const value = event.data.value;
        
        // Security: Whitelist allowed values
        if (!/^[0-9+\-=C]$/.test(value)) {
            console.warn('Invalid button value blocked:', value);
            return;
        }

        handleButtonPress(value);
    }
});

function handleButtonPress(value) {
    if (value === 'C') {
        currentExpression = '';
        updateDisplay('');
        document.getElementById('resultField').value = '';
    } else if (value === '=') {
        calculateResult();
    } else {
        currentExpression += value;
        updateDisplay(currentExpression);
    }
}

function updateDisplay(text) {
    const displayFrame = document.getElementById('displayFrame');
    displayFrame.contentWindow.postMessage({
        type: 'update',
        value: text
    }, ALLOWED_ORIGIN);
}

function calculateResult() {
    // Security: Sanitize and validate the expression
    const sanitized = currentExpression.replace(/[^0-9+\-]/g, '');
    
    // Security: Check for valid expression pattern
    if (!/^[0-9]+([+\-][0-9]+)*$/.test(sanitized)) {
        document.getElementById('resultField').value = 'Error: Invalid expression';
        return;
    }

    try {
        // Security: Safe evaluation using manual parsing instead of eval()
        const result = safeCalculate(sanitized);
        document.getElementById('resultField').value = result;
    } catch (error) {
        document.getElementById('resultField').value = 'Error';
    }
}

function safeCalculate(expression) {
    // Parse and calculate without using eval()
    const tokens = expression.match(/[+\-]?[0-9]+/g);
    if (!tokens) return 0;
    
    let result = 0;
    tokens.forEach(token => {
        result += parseInt(token, 10);
    });
    
    return result;
}