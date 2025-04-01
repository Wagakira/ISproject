// booking.js

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('bookingForm');
    const preview = document.getElementById('bookingPreview');
    const previewCaterer = document.getElementById('previewCaterer');
    const previewDate = document.getElementById('previewDate');
    const previewLocation = document.getElementById('previewLocation');
    const previewGuests = document.getElementById('previewGuests');
    const previewRequest = document.getElementById('previewRequest');

    form.addEventListener('input', () => {
        // Show the preview
        preview.style.display = 'block';

        // Update caterer
        const catererSelect = document.getElementById('caterer_id');
        const selectedCaterer = catererSelect.options[catererSelect.selectedIndex]?.text || 'Not selected';
        previewCaterer.textContent = selectedCaterer;

        // Update event date
        const eventDate = document.getElementById('event_date').value;
        previewDate.textContent = eventDate || 'Not specified';

        // Update location
        const eventLocation = document.getElementById('event_location').value;
        previewLocation.textContent = eventLocation || 'Not specified';

        // Update number of guests
        const guestNumber = document.getElementById('guest_number').value;
        previewGuests.textContent = guestNumber || 'Not specified';

        // Update special request
        const specialRequest = document.getElementById('special_request').value;
        previewRequest.textContent = specialRequest || 'None';
    });
});