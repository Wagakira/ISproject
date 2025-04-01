document.addEventListener('DOMContentLoaded', () => {
    const paymentForm = document.getElementById('paymentForm');
    const orderSelect = document.getElementById('order_id');
    const amountInput = document.getElementById('amount');
    const paymentMethods = document.querySelectorAll('.method');
    const paymentMethodInput = document.getElementById('payment_method');
    const mpesaDetails = document.getElementById('mpesaDetails');
    const mpesaOrderId = document.getElementById('mpesaOrderId');
    const mpesaAmount = document.getElementById('mpesaAmount');
    const downloadReceiptLinks = document.querySelectorAll('.download-receipt');

    orderSelect.addEventListener('change', () => {
        const selectedOption = orderSelect.options[orderSelect.selectedIndex];
        const amount = selectedOption.getAttribute('data-amount');
        amountInput.value = amount;
        mpesaOrderId.textContent = selectedOption.value || '';
        mpesaAmount.textContent = amount ? `Ksh ${parseFloat(amount).toFixed(2)}` : '';
    });

    paymentMethods.forEach(method => {
        method.addEventListener('click', () => {
            paymentMethods.forEach(m => m.classList.remove('selected'));
            method.classList.add('selected');
            paymentMethodInput.value = method.getAttribute('data-method');

            if (method.getAttribute('data-method') === 'M-Pesa') {
                mpesaDetails.classList.remove('hidden');
            } else {
                mpesaDetails.classList.add('hidden');
            }
        });
    });

    downloadReceiptLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const receipt = JSON.parse(link.getAttribute('data-receipt'));
            const receiptText = `
                Catering Service Payment Receipt
                --------------------------------
                Order ID: ${receipt.order_id}
                Amount: Ksh ${parseFloat(receipt.amount).toFixed(2)}
                Payment Method: ${receipt.payment_method}
                Transaction ID: ${receipt.transaction_id}
                Date: ${receipt.date}
                --------------------------------
                Thank you for your payment!
            `;
            const blob = new Blob([receiptText], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `receipt_order_${receipt.order_id}.txt`;
            a.click();
            URL.revokeObjectURL(url);
        });
    });
});