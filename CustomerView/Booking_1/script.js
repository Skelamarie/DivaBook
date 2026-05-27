document.addEventListener('DOMContentLoaded', () => {
    // SELECT ELEMENTS
    const calendarGrid = document.getElementById('calendarDays');
    const monthYearLabel = document.getElementById('monthYear');
    const priceDisplay = document.getElementById('totalPrice');
    const summaryText = document.getElementById('summaryText');
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('fileInput');
    const deleteBtn = document.getElementById('delete-img-btn');
    const slotBtns = document.querySelectorAll('.slot-btn');

    // Hidden Inputs (PHP)
    const dateInput = document.getElementById('selected_date');
    const timeInput = document.getElementById('selected_time');

    // DROPDOWN & SELECTION
    const serviceDropdown = document.getElementById('service-select');
    const sizeDropdown = document.getElementById('size-select');
    const addonDropdown = document.getElementById('addon-select');
    const removalCheck = document.getElementById('removal-check');
    const addonCards = document.querySelectorAll('.addon-card');

    // POLICY AND CONFI.
    const policyOverlay = document.getElementById('policy-overlay');
    const agreeCheck = document.getElementById('policy-agree-check');
    const proceedBtn = document.getElementById('proceed-booking-btn');
    const confirmBtn = document.querySelector('.confirm-btn');

    // MOBILE SIDE BAR
    const hamburger = document.querySelector('.hamburger-menu');
    const sidebar = document.querySelector('.mobile-sidebar');
    const closeSidebar = document.querySelector('.close-sidebar');

    // STATE VAR
    let viewDate = new Date();
    let selectedDateStr = (typeof window.restoredDateStr !== 'undefined' && window.restoredDateStr !== '') ? window.restoredDateStr : null;
    let selectedTime = (timeInput && timeInput.value) ? timeInput.value : "09:00 AM";

    // MOBILE SIDEBAR
    if (hamburger && sidebar) {
        hamburger.addEventListener('click', () => sidebar.classList.add('open'));
    }
    if (closeSidebar) {
        closeSidebar.addEventListener('click', () => sidebar.classList.remove('open'));
    }

    // DIFERENT SIZE
    function updateSizeOptions() {
        if (!sizeDropdown) return;
        const service = serviceDropdown ? serviceDropdown.value : '';

        sizeDropdown.innerHTML = `
            <option value="0" data-price="0">-- Select Size/Option --</option>
            <option value="standard" data-price="0">Standard - ₱0</option>
        `;

        if (service === "soft-gel") {
            sizeDropdown.innerHTML += '<option value="xl" data-price="200">Size: L - XXL - ₱200</option>';
        } else if (service === "hard-biab") {
            sizeDropdown.innerHTML += '<option value="extension" data-price="500">With Extension - ₱500</option>';
        }

        if (typeof window.restoredSize !== 'undefined' && window.restoredSize !== '') {
            sizeDropdown.value = window.restoredSize;
            window.restoredSize = '';
        }

        calculateTotal();
    }

    // FOR CALCULATIONS
    function calculateTotal() {
        if (!priceDisplay) return;

        const basePrice = parseInt(serviceDropdown?.options[serviceDropdown.selectedIndex]?.getAttribute('data-price')) || 0;
        const sizePrice = parseInt(sizeDropdown?.options[sizeDropdown.selectedIndex]?.getAttribute('data-price')) || 0;
        const addonPrice = parseInt(addonDropdown?.options[addonDropdown.selectedIndex]?.getAttribute('data-price')) || 0;
        const removalPrice = (removalCheck && removalCheck.checked) ? 150 : 0;

        const total = basePrice + sizePrice + addonPrice + removalPrice;
        priceDisplay.textContent = total;
        updateSummary();
    }

    // EVENT LISTENERS
    serviceDropdown?.addEventListener('change', updateSizeOptions);
    sizeDropdown?.addEventListener('change', calculateTotal);
    addonDropdown?.addEventListener('change', calculateTotal);
    removalCheck?.addEventListener('change', calculateTotal);

    addonCards.forEach(card => {
        card.style.cursor = 'default';
        card.onclick = (e) => { e.preventDefault(); e.stopPropagation(); };
    });

    // 7. CALENDAR & TIME LOGIC — FOR BLOCKING
    function isDateSelectable(year, month, day) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const candidate = new Date(year, month, day);
        candidate.setHours(0, 0, 0, 0);
        return candidate >= today; // FOR TODAY AND FUTURE NA DATES
    }

    function isToday(year, month, day) {
        const now = new Date();
        return now.getFullYear() === year && now.getMonth() === month && now.getDate() === day;
    }

    function renderCalendar() {
        if (!calendarGrid) return;
        calendarGrid.innerHTML = '';

        const year = viewDate.getFullYear();
        const month = viewDate.getMonth();
        monthYearLabel.textContent = viewDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // EMPTY SLOT BEFORE MNTH STARTS
        for (let i = 0; i < firstDay; i++) {
            const empty = document.createElement('div');
            empty.className = 'day muted';
            calendarGrid.appendChild(empty);
        }

        for (let i = 1; i <= daysInMonth; i++) {
            const dayEl = document.createElement('div');
            const monthShort = new Date(year, month, 1).toLocaleDateString('en-US', { month: 'short' });
            const dateID = `${monthShort} ${i}, ${year}`;
            const selectable = isDateSelectable(year, month, i);

            if (!selectable) {
                // FOR PAST DATES TO BE BLOCKED
                dayEl.className = 'day muted';
                dayEl.style.cssText = 'cursor:not-allowed !important; opacity:0.28; text-decoration:line-through; pointer-events:none;';
            } else {
                dayEl.className = 'day';
                if (selectedDateStr === dateID) dayEl.classList.add('active');

                // TODAY'S DATE INDICATION
                if (isToday(year, month, i)) {
                    dayEl.classList.add('today');
                    dayEl.style.cssText = 'outline: 2px solid currentColor; outline-offset: -3px; font-weight: 700;';
                }

                dayEl.onclick = () => {
                    document.querySelectorAll('.day').forEach(d => d.classList.remove('active'));
                    dayEl.classList.add('active');
                    selectedDateStr = dateID;
                    if (dateInput) dateInput.value = selectedDateStr;
                    updateSummary();
                    refreshSlots(); // RE-EVAL TIME AND SOT FOR THE NEW SELECTED DTAE
                };
            }

            dayEl.textContent = i;
            calendarGrid.appendChild(dayEl);
        }
    }

    function updateSummary() {
        if (!summaryText) return;
        if (selectedDateStr && selectedDateStr !== "null") {
            summaryText.textContent = `${selectedDateStr} at ${selectedTime}`;
        } else {
            summaryText.textContent = "Please select a date";
        }
    }

    // FOR TIME SLOT AVAILABLITY
    function slotToMinutes(label) {
        const match = label.trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
        if (!match) return 0;
        let hours = parseInt(match[1], 10);
        const mins  = parseInt(match[2], 10);
        const period = match[3].toUpperCase();
        if (period === 'AM' && hours === 12) hours = 0;
        if (period === 'PM' && hours !== 12) hours += 12;
        return hours * 60 + mins;
    }

    function normDateStr(str) {
        if (!str) return '';
        return str.replace(/(\w+ )(\d+)(, \d+)/, (_, m, d, y) => m + parseInt(d, 10) + y);
    }

    // IS THE SLOT AVAILABLE (SEKECTED)
    function isSlotAvailable(slotLabel) {
        const now = new Date();
        const todayFormatted = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

        // IF NO SELECTED DATE, DEFAULT THE CURRENT DATE
        const effectiveDate = (!selectedDateStr || selectedDateStr === 'null') ? todayFormatted : selectedDateStr;

        // CHECK IF SLOT IS ALREADY TAKEN AND THEN BLOCK
        const bookedMap = window.occupiedSlotsMap || {};
        const bookedForDate = bookedMap[effectiveDate] || [];
        if (bookedForDate.includes(slotLabel.trim())) return false;

        // BLOCK PAST TIME FOR THE CURRENT DATE
        if (normDateStr(effectiveDate) !== normDateStr(todayFormatted)) return true;

        const nowMinutes = now.getHours() * 60 + now.getMinutes();
        // BLOCK IF SLOT IS ALREADY 30 MINS PASSED BEFORE THE SCHEUDLED TIME
        return slotToMinutes(slotLabel) > nowMinutes + 30;
    }

    function refreshSlots() {
        let firstAvailable = null;

        // DETERMINE SLOT TAT ARE BOOKED OR PAST 
        const now = new Date();
        const todayFormatted = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const effectiveDate = (!selectedDateStr || selectedDateStr === 'null') ? todayFormatted : selectedDateStr;
        const bookedMap = window.occupiedSlotsMap || {};
        const bookedForDate = bookedMap[effectiveDate] || [];

        slotBtns.forEach(btn => {
            const label = btn.textContent.trim();
            const isBooked = bookedForDate.includes(label);
            const available = isSlotAvailable(label);

            if (isBooked) {
                // IF SLOT IS TAKEN, BLOCK AND MESSAGE APPEARS
                btn.classList.remove('active', 'slot-past');
                btn.classList.add('slot-booked');
                btn.style.cssText = 'opacity:0.45; cursor:not-allowed; pointer-events:none; text-decoration:line-through;';
                btn.disabled = true;
                btn.title = 'Already booked';
            } else if (!available) {
                btn.classList.remove('active', 'slot-booked');
                btn.classList.add('slot-past');
                btn.style.cssText = 'opacity:0.3; cursor:not-allowed; pointer-events:none; text-decoration:line-through;';
                btn.disabled = true;
                btn.title = '';
            } else {
                btn.classList.remove('slot-past', 'slot-booked');
                btn.style.cssText = '';
                btn.disabled = false;
                btn.title = '';
                if (!firstAvailable) firstAvailable = btn;
            }
        });

        // IIF NA DISABLE, AUTO PIC THE NEXT SCH.
        const active = document.querySelector('.slot-btn.active:not(.slot-past)');
        if (!active) {
            slotBtns.forEach(b => b.classList.remove('active'));
            if (firstAvailable) {
                firstAvailable.classList.add('active');
                selectedTime = firstAvailable.textContent.trim();
                if (timeInput) timeInput.value = selectedTime;
            } else {
                selectedTime = '';
                if (timeInput) timeInput.value = '';
            }
            updateSummary();
        }
    }

    slotBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            if (btn.disabled) return;                                   // BLOCK CLICK SA PASSED DISBALED SLOT
            slotBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedTime = btn.textContent.trim();
            if (timeInput) timeInput.value = selectedTime;
            updateSummary();
        });
    });

    // PREVENT NO NAVIGATE SA PAST MONTHS
    document.getElementById('prevMonth')?.addEventListener('click', () => {
        const today = new Date();
        const proposed = new Date(viewDate.getFullYear(), viewDate.getMonth() - 1, 1);
        const thisMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        if (proposed >= thisMonth) {
            viewDate.setMonth(viewDate.getMonth() - 1);
            renderCalendar();
        }
    });
    document.getElementById('nextMonth')?.addEventListener('click', () => {
        viewDate.setMonth(viewDate.getMonth() + 1);
        renderCalendar();
    });

    // FOR PHOTO UPLOAD
    const providerChoosesCheck = document.getElementById('provider-chooses-check');
    const uploadInnerCard = document.getElementById('upload-inner-card');

    if (providerChoosesCheck && uploadInnerCard) {
        providerChoosesCheck.addEventListener('change', (e) => {
            if (e.target.checked) {
                uploadInnerCard.style.opacity = '0.5';
                uploadInnerCard.style.pointerEvents = 'none';
            } else {
                uploadInnerCard.style.opacity = '1';
                uploadInnerCard.style.pointerEvents = 'auto';
            }
        });
    }

    if (dropzone && fileInput) {
        dropzone.onclick = (e) => {
            if (e.target !== deleteBtn) fileInput.click();
        };
        fileInput.onchange = (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    dropzone.style.backgroundImage = `url(${ev.target.result})`;
                    dropzone.classList.add('has-image');
                    const uploadText = document.getElementById('upload-text');
                    if (uploadText) uploadText.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        };
    }

    if (deleteBtn) {
        deleteBtn.onclick = (e) => {
            e.stopPropagation();
            if (fileInput) fileInput.value = "";
            if (dropzone) {
                dropzone.style.backgroundImage = 'none';
                dropzone.classList.remove('has-image');
            }
            const uploadText = document.getElementById('upload-text');
            if (uploadText) uploadText.style.display = 'block';
        };
    }

    // FOR POLICY POPUP
    if (policyOverlay) {
        if (policyOverlay.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
        }

        if (agreeCheck && proceedBtn) {
            agreeCheck.addEventListener('change', () => {
                proceedBtn.disabled = !agreeCheck.checked;
            });

            proceedBtn.addEventListener('click', () => {
                policyOverlay.classList.remove('active');
                document.body.style.overflow = 'auto';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    }

    // CONFIRMATION
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function (e) {
            e.preventDefault();

            // Final safety sync for hidden inputs
            if (dateInput) dateInput.value = selectedDateStr;
            if (timeInput) timeInput.value = selectedTime;

            // Client-side validation
            if (!serviceDropdown || serviceDropdown.value === "0") {
                alert("Please select a service.");
                return;
            }
            if (!selectedDateStr || selectedDateStr === "null") {
                alert("Please select a future date on the calendar.");
                return;
            }
            if (!sizeDropdown || sizeDropdown.value === "0") {
                alert("Please select a size option.");
                return;
            }
            if (!addonDropdown || addonDropdown.value === "0") {
                alert("Please select a design add-on.");
                return;
            }

            // REF IMG. CHECK
            const providerChooses = document.getElementById('provider-chooses-check');
            const hasImage = fileInput && fileInput.files && fileInput.files.length > 0;
            if (providerChooses && !providerChooses.checked && !hasImage) {
                alert("Please upload a reference image or check 'Service provider will choose the design'.");
                return;
            }

            // SAVE
            const serviceName = serviceDropdown.options[serviceDropdown.selectedIndex].text;
            const sizeName = sizeDropdown.options[sizeDropdown.selectedIndex].text;
            const addonName = addonDropdown.options[addonDropdown.selectedIndex].text;
            const removalActive = (removalCheck && removalCheck.checked) ? "Removal Inc." : "";

            localStorage.setItem('selectedService', serviceName);
            localStorage.setItem('selectedSize', sizeName);
            localStorage.setItem('selectedAddon', addonName);
            localStorage.setItem('removalStatus', removalActive);
            localStorage.setItem('totalPrice', priceDisplay ? priceDisplay.textContent : '0');
            localStorage.setItem('dateTime', summaryText ? summaryText.textContent : '');

            // SHOW LOADING THEN SUBMIT
            const loadingOverlay = document.getElementById('loading-overlay');
            if (loadingOverlay) loadingOverlay.classList.add('active');
            setTimeout(() => {
                const form = document.getElementById('bookingForm');
                if (form) form.submit();
            }, 500);
        });
    }

    renderCalendar();
    updateSizeOptions();

    if (timeInput && timeInput.value) {
        selectedTime = timeInput.value;
    } else {
        selectedTime = "09:00 AM";
    }
    updateSummary();
    refreshSlots(); 
});

function goToHome() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = "../index.html";
    }
}

const policyCloseBtn = document.getElementById('policy-close-btn');
if (policyCloseBtn) {
    policyCloseBtn.addEventListener('click', () => {
        // Check if there is a history to go back to
        if (window.history.length > 1) {
            window.history.back();
        } else {
            // Fallback if they opened the link directly in a new tab
            window.location.href = "../ServicePage3.php";
        }
    });
}