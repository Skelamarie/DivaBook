function goBack() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.classList.add('active');
    }

    setTimeout(() => {
        window.location.href = '../Booking_1/B1.php';
    }, 1500);
}

document.addEventListener('DOMContentLoaded', () => {
    // PULL REQ. INFO IN PREV PAGE
    const savedService = localStorage.getItem('selectedServices');
    const savedSize = localStorage.getItem('selectedSize');
    const savedAddon = localStorage.getItem('selectedAddon');
    const savedRemoval = localStorage.getItem('removalFee');
    const savedDateTime = localStorage.getItem('dateTime');
    const savedTotal = localStorage.getItem('totalPrice');

    // SUMMARY
    const serviceDisplay = document.getElementById('b2-service-name');
    const itemPriceDisplay = document.getElementById('b2-item-price'); 
    const dateDisplay = document.getElementById('b2-date-time');
    const totalDisplay = document.getElementById('b2-total-price');

    // UPDATE
    if (serviceDisplay && savedService) {
        let details = [savedService];
        
        // 
        if (savedSize && !savedSize.includes("Select")) details.push(savedSize);
        if (savedAddon && !savedAddon.includes("Select")) details.push(savedAddon);
        if (savedRemoval === "Yes (+₱150)") details.push("Removal Inc.");

        serviceDisplay.textContent = details.join(' • ');
    }

    // UPDATE DATE & TIME
    if (dateDisplay && savedDateTime) {
        dateDisplay.textContent = savedDateTime;
    }

    // ALIGN CALCULATIONS
    if (savedTotal) {
        if (itemPriceDisplay) itemPriceDisplay.textContent = `₱${savedTotal}`;
        if (totalDisplay) totalDisplay.textContent = `₱${savedTotal}`;
    }
});

// LOADING PAGE
const loadingText = overlay.querySelector('.loading-text');
if (loadingText) {
    loadingText.textContent = "PROCEEDING TO PAYMENT...";
}
    
setTimeout(() => {
    document.getElementById('b2Form').submit();
}, 1500);


document.addEventListener('DOMContentLoaded', () => {
    const services = localStorage.getItem('selectedServices') || "No service selected";
    const price = localStorage.getItem('totalPrice') || "₱0";
    const dateTime = localStorage.getItem('dateTime') || "Not scheduled";

    if (document.getElementById('b2-service-name')) {
        document.getElementById('b2-service-name').textContent = services;
        document.getElementById('b2-item-price').textContent = price;
        document.getElementById('b2-date-time').innerHTML = dateTime.replace(' at ', '<br>');
        document.getElementById('b2-total-price').textContent = price;
    }
});

function navigateToPayment() {
    const form = document.getElementById('b2Form');
    
    // TRAGET REQ. INPUTS
    const inputs = {
        firstName: document.querySelector('input[name="first_name"]'),
        lastName: document.querySelector('input[name="last_name"]'),
        email: document.querySelector('input[name="email"]'),
        phone: document.querySelector('input[name="phone"]'),
        marketing: document.querySelector('input[name="marketing_opt_in"]'),
        terms: document.querySelector('input[name="terms_agreed"]')
    };

    let isValid = true;
    let firstErrorField = null;

    // TO VALIDATE
    for (let key in inputs) {
        const field = inputs[key];
        
        // TO CHECK THE CHECKBOX
        const isFieldEmpty = field.type !== 'checkbox' && !field.value.trim();
        const isCheckboxUnchecked = field.type === 'checkbox' && !field.checked;

        if (isFieldEmpty || isCheckboxUnchecked) {
            field.style.borderBottom = "1px solid #dc2626"; 
            isValid = false;
            if (!firstErrorField) firstErrorField = field;
        } else {
            field.style.borderBottom = "1px solid #074533"; 
        }
    }

    // RESTRICTIONS
    if (!isValid) {
        alert("Please complete all required fields and agreements before proceeding.");
        if (firstErrorField) firstErrorField.focus(); // TO GIUDE FOR THE MISSING FIELD
        return; 
    }

    // LOADING IF ALL INOUTS IS VALID
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.classList.add('active'); 
        const loadingText = overlay.querySelector('.loading-text');
        if (loadingText) {
            loadingText.textContent = "PROCEEDING TO PAYMENT..."; 
        }
    }

    setTimeout(() => {
        form.submit();
    }, 1500);
}

document.getElementById('b2Form').addEventListener('submit', function(e) {
    const overlay = document.getElementById('loading-overlay');
    
    // TO CHECK FORM
    if (this.checkValidity()) {
        overlay.style.display = 'flex';
    } else {
        e.preventDefault();
    }
});

function goBack() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) overlay.classList.add('active');
    
    setTimeout(() => {
        window.location.href = "../Booking_1/B1.php";
    }, 500);
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('b2Form');
    const overlay = document.getElementById('loading-overlay');

    // FORM HANDLER (SUBMISISON)
    if (form) {
        form.addEventListener('submit', function(e) {
            if (this.checkValidity()) {
                if (overlay) {
                    overlay.classList.add('active');
                    const loadingText = overlay.querySelector('.loading-text');
                    if (loadingText) loadingText.textContent = "PROCEEDING TO PAYMENT...";
                }
            } else {
                e.preventDefault();
            }
        });
    }

    // 2. LOAD SUMMARY DATA
    loadBookingData();
});

function loadBookingData() {
    // RETRIEVE DATA
    const service = localStorage.getItem('selectedService') || "None";
    const size = localStorage.getItem('selectedSize');
    const addon = localStorage.getItem('selectedAddon');
    const removal = localStorage.getItem('removalStatus');
    const total = localStorage.getItem('totalPrice') || '0';
    const dateTime = localStorage.getItem('dateTime') || 'Not scheduled';

    // BUILD TREATMENT STRING 
    let details = [service];
    if (size && !size.includes("--")) details.push(size);
    if (addon && !addon.includes("--")) details.push(addon);
    if (removal) details.push(removal);

    const serviceDisplay = document.getElementById('b2-service-name');
    if (serviceDisplay) serviceDisplay.textContent = details.join(' • ');

    const itemPrice = document.getElementById('b2-item-price');
    const totalPrice = document.getElementById('b2-total-price');
    if (itemPrice) itemPrice.textContent = '₱' + total;
    if (totalPrice) totalPrice.textContent = '₱' + total;
    
    const dateDisplay = document.getElementById('b2-date-time');
    if (dateDisplay) dateDisplay.textContent = dateTime;
}

function goBack() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.classList.add('active');
        const loadingText = overlay.querySelector('.loading-text');
        if (loadingText) loadingText.textContent = "RETURNING TO SCHEDULE...";
    }
    
    setTimeout(() => {
        window.location.href = "../Booking_1/B1.php";
    }, 800);
}

function updateSummary() {
    const summaryText = document.getElementById('summaryText');
    const dateInput = document.getElementById('selected_date');
    const timeInput = document.getElementById('selected_time');

    let displayDate = selectedDateStr || dateInput.value;
    let displayTime = selectedTime || timeInput.value;

    if (summaryText) {
        if (displayDate && displayDate !== "null") {
            summaryText.textContent = `${displayDate} at ${displayTime}`;
        } else {
            summaryText.textContent = "Please select a date";
        }
    }
}