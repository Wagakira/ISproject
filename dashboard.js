document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            button.classList.add('active');
            document.getElementById(button.dataset.tab).classList.add('active');
        });
    });

    // Total price calculation for orders
    const dishSelect = document.getElementById('dish_id');
    const quantityInput = document.getElementById('quantity');
    const totalPriceInput = document.getElementById('total_price');

    function calculateTotalPrice() {
        const price = parseFloat(dishSelect.options[dishSelect.selectedIndex]?.dataset.price) || 0;
        const quantity = parseInt(quantityInput.value) || 0;
        const total = price * quantity;
        totalPriceInput.value = total.toFixed(2);
    }

    if (dishSelect && quantityInput) {
        dishSelect.addEventListener('change', calculateTotalPrice);
        quantityInput.addEventListener('input', calculateTotalPrice);
    }

    // Initialize FullCalendar
    const calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: bookings.map(booking => ({
                title: `Booking #${booking.booking_id} (${booking.status})`,
                start: booking.event_date,
                backgroundColor: booking.status === 'pending' ? '#ff9800' : booking.status === 'confirmed' ? '#4caf50' : '#f44336'
            })),
            dateClick: function(info) {
                const dateStr = info.dateStr;
                const isAvailable = availability[dateStr] !== undefined ? availability[dateStr] : true;
                alert(`Date: ${dateStr}\nAvailable: ${isAvailable ? 'Yes' : 'No'}`);
            }
        });
        calendar.render();
    }
});