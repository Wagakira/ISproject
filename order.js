document.addEventListener('DOMContentLoaded', () => {
    const orderCards = document.querySelectorAll('.order-card');
    orderCards.forEach(card => {
        card.addEventListener('click', () => {
            const orderId = card.getAttribute('data-order-id');
            window.location.href = `?order_id=${orderId}`;
        });
    });

    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        setTimeout(() => {
            progressBar.style.transition = 'width 1.5s ease-in-out';
            progressBar.style.width = progressBar.style.width; // Trigger animation
        }, 500); 
    }

    const miniProgressBars = document.querySelectorAll('.mini-progress-bar');
    miniProgressBars.forEach(bar => {
        setTimeout(() => {
            bar.style.transition = 'width 1s ease-in-out';
            bar.style.width = bar.style.width; // Trigger animation
        }, 300); 
    });

    const cancelButtons = document.querySelectorAll('.cancel-btn');
    cancelButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            if (!confirm('Are you sure you want to cancel this order?')) {
                e.preventDefault();
            }
        });
    });
});