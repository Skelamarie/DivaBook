
document.addEventListener('DOMContentLoaded', () => {
    //RETRIEVE DAA
    try {
        const services = localStorage.getItem('selectedServices') || "No service selected";
        let price = localStorage.getItem('totalPrice') || "0";
        const dateTime = localStorage.getItem('dateTime') || "Please select a date";
        const displayPrice = price.startsWith('₱') ? price : `₱${price}`;

        const serviceNameElem = document.getElementById('p1-service-name');
        const itemPriceElem = document.getElementById('p1-item-price');
        const totalPriceElem = document.getElementById('p1-total-price');
        const dateTimeElem = document.getElementById('p1-date-time');

        if (serviceNameElem) serviceNameElem.textContent = services;
        if (itemPriceElem) itemPriceElem.textContent = displayPrice;
        if (totalPriceElem) totalPriceElem.textContent = displayPrice;
        if (dateTimeElem) dateTimeElem.innerHTML = dateTime.replace(' at ', '<br>');
    } catch (err) {
        console.warn("Summary data elements missing:", err);
    }

    // PAYMENT STRATEGY
    const strategyRadios = document.querySelectorAll('input[name="payment_strategy"]');
    const totalPriceElem = document.querySelector('.total-price');
    const originalTotal = parseFloat(localStorage.getItem('totalPrice') || "0");

    function updateDisplayTotal() {
        const checkedStrategy = document.querySelector('input[name="payment_strategy"]:checked');
        if (!checkedStrategy) return;
        const selectedStrategy = checkedStrategy.value;

        const hiddenStrategy = document.getElementById('hidden_payment_strategy');
        if (hiddenStrategy) hiddenStrategy.value = selectedStrategy;

        if (selectedStrategy === 'downpayment') {
            if (totalPriceElem) totalPriceElem.textContent = '₱450.00';
        } else {
            if (totalPriceElem) totalPriceElem.textContent = `₱${originalTotal.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
        }
    }

    strategyRadios.forEach(radio => radio.addEventListener('change', updateDisplayTotal));
    updateDisplayTotal();

    // FIELD HANDLING
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const notice = document.getElementById('submit-notice');
    
    function updateRequiredFields() {
        if (notice) notice.style.display = 'none';

        const fields = ['gcash_name', 'gcash_receipt', 'maya_name', 'maya_receipt', 'shopee_name', 'shopee_receipt'];
        fields.forEach(name => {
            const el = document.querySelector(`[name="${name}"]`);
            if (el) el.removeAttribute('required');
        });

        const selected = document.querySelector('input[name="payment_method"]:checked');
        if (selected) {
            const method = selected.value;
            const prefix = method === 'gcash' ? 'gcash' : (method === 'paymaya' ? 'maya' : 'shopee');
            
            const nameEl = document.querySelector(`[name="${prefix}_name"]`);
            const fileEl = document.querySelector(`[name="${prefix}_receipt"]`);
            
            if (nameEl) nameEl.setAttribute('required', 'required');
            if (fileEl) fileEl.setAttribute('required', 'required');
        }
    }

    paymentMethods.forEach(radio => radio.addEventListener('change', updateRequiredFields));
});

// VALIDATION
const checkoutForm = document.getElementById('checkout-form');
if (checkoutForm) {
    checkoutForm.addEventListener('submit', function (e) {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        const notice = document.getElementById('submit-notice');
        
        // CHECK PAYMENT METHOD
        if (!selectedMethod) {
            e.preventDefault();
            document.getElementById('no-payment-popup').style.display = 'flex';
            return;
        }

        // CHECK FOR NAME AND RECEIPT UPLOAD
        const method = selectedMethod.value;
        const prefix = method === 'gcash' ? 'gcash' : (method === 'paymaya' ? 'maya' : 'shopee');
        
        const nameInp = document.querySelector(`input[name="${prefix}_name"]`);
        const fileInp = document.querySelector(`input[name="${prefix}_receipt"]`);

        if (!nameInp || !fileInp || !nameInp.value.trim() || !fileInp.value) {
            e.preventDefault();
            if (notice) {
                notice.style.display = 'block';
                notice.innerHTML = "⚠ Please enter the sender name and upload your receipt.";
                notice.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } else {
            if (notice) notice.style.display = 'none';
        }
    });
}

// NAVIGATION
function goBackToDetails() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) overlay.classList.add('active');
    setTimeout(() => { window.location.href = '../Booking_2/B2.php'; }, 1500);
}

function closeModal() {
    const modal = document.getElementById('confirmation-modal');
    if (modal) modal.style.display = 'none';
    window.location.href = '../HomePage.php';
}