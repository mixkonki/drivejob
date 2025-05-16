/**
 * Εμφάνιση των μηνυμάτων της κονσόλας στη σελίδα
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Console display script loaded');

    // Δημιουργία του container για τα μηνύματα της κονσόλας
    const consoleContainer = document.createElement('div');
    consoleContainer.id = 'console-display';
    consoleContainer.style.position = 'fixed';
    consoleContainer.style.bottom = '0';
    consoleContainer.style.left = '0';
    consoleContainer.style.width = '100%';
    consoleContainer.style.maxHeight = '300px';
    consoleContainer.style.overflowY = 'auto';
    consoleContainer.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
    consoleContainer.style.color = '#fff';
    consoleContainer.style.fontFamily = 'monospace';
    consoleContainer.style.fontSize = '12px';
    consoleContainer.style.padding = '10px';
    consoleContainer.style.zIndex = '9999';
    consoleContainer.style.display = 'none'; // Αρχικά κρυμμένο

    // Προσθήκη του header
    const header = document.createElement('div');
    header.style.display = 'flex';
    header.style.justifyContent = 'space-between';
    header.style.alignItems = 'center';
    header.style.marginBottom = '10px';
    header.style.borderBottom = '1px solid #555';
    header.style.paddingBottom = '5px';

    const title = document.createElement('h3');
    title.textContent = 'Console Messages';
    title.style.margin = '0';
    title.style.color = '#fff';

    const clearButton = document.createElement('button');
    clearButton.textContent = 'Clear';
    clearButton.style.backgroundColor = '#555';
    clearButton.style.color = '#fff';
    clearButton.style.border = 'none';
    clearButton.style.padding = '5px 10px';
    clearButton.style.cursor = 'pointer';
    clearButton.style.borderRadius = '3px';
    clearButton.onclick = function() {
        messagesContainer.innerHTML = '';
        console.clear();
    };

    const closeButton = document.createElement('button');
    closeButton.textContent = 'Close';
    closeButton.style.backgroundColor = '#f44336';
    closeButton.style.color = '#fff';
    closeButton.style.border = 'none';
    closeButton.style.padding = '5px 10px';
    closeButton.style.cursor = 'pointer';
    closeButton.style.borderRadius = '3px';
    closeButton.style.marginLeft = '10px';
    closeButton.onclick = function() {
        consoleContainer.style.display = 'none';
        toggleButton.style.display = 'block';
    };

    header.appendChild(title);
    const buttonContainer = document.createElement('div');
    buttonContainer.appendChild(clearButton);
    buttonContainer.appendChild(closeButton);
    header.appendChild(buttonContainer);

    consoleContainer.appendChild(header);

    // Δημιουργία του container για τα μηνύματα
    const messagesContainer = document.createElement('div');
    messagesContainer.id = 'console-messages';
    consoleContainer.appendChild(messagesContainer);

    // Προσθήκη του container στη σελίδα
    document.body.appendChild(consoleContainer);

    // Δημιουργία του κουμπιού για εμφάνιση/απόκρυψη της κονσόλας
    const toggleButton = document.createElement('button');
    toggleButton.textContent = 'Show Console';
    toggleButton.style.position = 'fixed';
    toggleButton.style.bottom = '10px';
    toggleButton.style.right = '10px';
    toggleButton.style.backgroundColor = '#4CAF50';
    toggleButton.style.color = '#fff';
    toggleButton.style.border = 'none';
    toggleButton.style.padding = '10px';
    toggleButton.style.cursor = 'pointer';
    toggleButton.style.borderRadius = '5px';
    toggleButton.style.zIndex = '9998';
    toggleButton.onclick = function() {
        consoleContainer.style.display = 'block';
        toggleButton.style.display = 'none';
    };

    document.body.appendChild(toggleButton);

    // Αντικατάσταση των μεθόδων της κονσόλας
    const originalConsole = {
        log: console.log,
        warn: console.warn,
        error: console.error,
        info: console.info,
        debug: console.debug,
        clear: console.clear
    };

    // Συνάρτηση για προσθήκη μηνύματος στο container
    function addMessage(type, args) {
        const message = document.createElement('div');
        message.style.padding = '5px';
        message.style.borderBottom = '1px solid #333';
        message.style.wordBreak = 'break-word';

        // Προσθήκη timestamp
        const timestamp = new Date().toLocaleTimeString();
        const timestampSpan = document.createElement('span');
        timestampSpan.textContent = `[${timestamp}] `;
        timestampSpan.style.color = '#aaa';
        message.appendChild(timestampSpan);

        // Προσθήκη τύπου μηνύματος
        const typeSpan = document.createElement('span');
        typeSpan.textContent = `[${type}] `;
        
        switch (type) {
            case 'log':
                typeSpan.style.color = '#fff';
                break;
            case 'warn':
                typeSpan.style.color = '#ff9800';
                break;
            case 'error':
                typeSpan.style.color = '#f44336';
                break;
            case 'info':
                typeSpan.style.color = '#2196f3';
                break;
            case 'debug':
                typeSpan.style.color = '#9c27b0';
                break;
        }
        
        message.appendChild(typeSpan);

        // Προσθήκη περιεχομένου μηνύματος
        const content = document.createElement('span');
        
        // Μετατροπή των arguments σε string
        let contentText = '';
        for (let i = 0; i < args.length; i++) {
            if (typeof args[i] === 'object') {
                try {
                    contentText += JSON.stringify(args[i], null, 2);
                } catch (e) {
                    contentText += args[i];
                }
            } else {
                contentText += args[i];
            }
            
            if (i < args.length - 1) {
                contentText += ' ';
            }
        }
        
        content.textContent = contentText;
        message.appendChild(content);

        // Προσθήκη του μηνύματος στο container
        messagesContainer.appendChild(message);
        
        // Αυτόματο scroll στο τελευταίο μήνυμα
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // Αντικατάσταση των μεθόδων της κονσόλας
    console.log = function() {
        addMessage('log', arguments);
        originalConsole.log.apply(console, arguments);
    };

    console.warn = function() {
        addMessage('warn', arguments);
        originalConsole.warn.apply(console, arguments);
    };

    console.error = function() {
        addMessage('error', arguments);
        originalConsole.error.apply(console, arguments);
    };

    console.info = function() {
        addMessage('info', arguments);
        originalConsole.info.apply(console, arguments);
    };

    console.debug = function() {
        addMessage('debug', arguments);
        originalConsole.debug.apply(console, arguments);
    };

    console.clear = function() {
        messagesContainer.innerHTML = '';
        originalConsole.clear.apply(console);
    };

    // Καταγραφή των σφαλμάτων
    window.addEventListener('error', function(event) {
        addMessage('error', [`${event.message} at ${event.filename}:${event.lineno}:${event.colno}`]);
    });

    // Καταγραφή των unhandled promise rejections
    window.addEventListener('unhandledrejection', function(event) {
        addMessage('error', [`Unhandled Promise Rejection: ${event.reason}`]);
    });

    console.log('Console display initialized');
});
