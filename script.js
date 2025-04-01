document.addEventListener('DOMContentLoaded', () => {
    const socket = new WebSocket('ws://localhost:8080');
    const messagesDiv = document.getElementById('messages');
    const form = document.getElementById('message-form');
    const receiverInput = document.getElementById('receiver_id');
    const catererItems = document.querySelectorAll('.caterer-list li');
    const notificationSound = document.getElementById('notification-sound');
    let currentCaterer = null;

    function loadMessages(catererId) {
        fetch(`get_messages.php?caterer_id=${catererId}`)
            .then(response => response.json())
            .then(messages => {
                messagesDiv.innerHTML = '';
                messages.forEach(msg => {
                    addMessage(msg);
                });
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            });
    }

    function addMessage(msg) {
        const div = document.createElement('div');
        div.className = `message ${msg.sender_id == <?php echo $customer_id; ?> ? 'sent' : 'received'}`;
        div.innerHTML = `
            <div class="message-content">
                <div class="message-text">${msg.message}</div>
                <div class="message-info">
                    ${msg.sender_id == <?php echo $customer_id; ?> ? 'You' : 'Caterer'} • 
                    ${new Date(msg.sent_at).toLocaleString()}
                </div>
            </div>
        `;
        messagesDiv.appendChild(div);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    catererItems.forEach(item => {
        item.addEventListener('click', () => {
            catererItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            currentCaterer = item.dataset.catererId;
            receiverInput.value = currentCaterer;
            loadMessages(currentCaterer);
        });
    });

    socket.onopen = () => console.log('Connected to WebSocket');
    socket.onmessage = (event) => {
        const msg = JSON.parse(event.data);
        if (msg.receiver_id == <?php echo $customer_id; ?> && msg.sender_id == currentCaterer) {
            addMessage(msg);
            notificationSound.play();
        }
    };

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        if (!currentCaterer) {
            alert('Please select a caterer first');
            return;
        }

        const formData = new FormData(form);
        const message = {
            sender_id: <?php echo $customer_id; ?>,
            receiver_id: currentCaterer,
            message: formData.get('message')
        };

        socket.send(JSON.stringify(message));
        form.reset();
    });
});