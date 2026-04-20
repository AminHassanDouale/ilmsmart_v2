import './bootstrap';

// Auto-scroll messages to bottom
document.addEventListener('livewire:navigated', () => {
    const msgArea = document.getElementById('message-area');
    if (msgArea) {
        msgArea.scrollTop = msgArea.scrollHeight;
    }
});

// Scroll on new message
Livewire.on('message-sent', () => {
    const msgArea = document.getElementById('message-area');
    if (msgArea) {
        setTimeout(() => { msgArea.scrollTop = msgArea.scrollHeight; }, 100);
    }
});
