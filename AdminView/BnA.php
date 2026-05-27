<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//redirect to login if session variable is missing
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    // Clear any corrupted session data
    session_unset();
    session_destroy();
    header("Location: Login.php?error=unauthorized");
    exit();
}

require_once '../db_connect.php'; 

$current_admin_id = $_SESSION['admin_id'];

//ensures Partner A doesn't see Partner B's calendar events
$calendarCursor = $bookingsCollection->find(['admin_id' => $current_admin_id]);

$dynamicAppts = [];
$pendingRequests = [];

foreach ($calendarCursor as $doc) {
    $dateKey = date("Y-n-j", strtotime($doc['selected_date']));
    $status = $doc['status'] ?? 'pending';
    
    //add to the pending list if status is pending
    if ($status === 'pending') {
        $pendingRequests[] = $doc; //populates list
    }

    $statusClass = match($status) {
        'approved' => 'appt-accepted', 
        'pending'  => 'appt-pending',  
        'declined' => 'appt-declined', 
        'queue'    => 'appt-queue',
        default    => 'appt-pending'
    };

    $dynamicAppts[$dateKey][] = [
        'id'      => (string)$doc['_id'],
        'title'   => htmlspecialchars($doc['service_name'] ?? $doc['service']), 
        'time'    => $doc['selected_time'],
        'date'    => htmlspecialchars($doc['selected_date']), 
        'type'    => $statusClass,
        'name'    => htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']), 
        'email'   => htmlspecialchars($doc['email']),
        'request' => htmlspecialchars($doc['request_note'] ?? ''),
        'method'  => htmlspecialchars($doc['payment_method'] ?? 'N/A'),
        'paid'    => number_format($doc['amount_paid'] ?? 0, 2)

    ];
}
?>

<?php require_once 'customization_helper.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>DivaBook - Bookings &amp; Appointments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL,GRAD,opsz@400,0,0,24" rel="stylesheet">
    <link rel="stylesheet" href="BnA.css">
    <style>
        <?= getCustomStyles($colorPrimary, $colorSecondary) ?>
        
        .appt-accepted { background-color: #dcfce7 !important; border-left: 4px solid #166534 !important; color: #166534 !important; } /* Green */
        .appt-pending  { background-color: #ffedd5 !important; border-left: 4px solid #ea580c !important; color: #9a3412 !important; } /* Orange */
        .appt-declined { background-color: #fee2e2 !important; border-left: 4px solid #dc2626 !important; color: #991b1b !important; } /* Red */
        
        .appt-queue    { background-color: #f3f4f6 !important; border-left: 4px solid #4b5563 !important; color: #4b5563 !important; }
        .appt { z-index: 10; width: calc(100% - 8px); box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        
        .week-label-dropdown { border: none; background: white; font-size: 0.875rem; font-weight: 600; padding: 4px 12px; cursor: pointer; border-left: 1px solid var(--outline); border-right: 1px solid var(--outline); outline: none; appearance: none; text-align: center; color: #1a1c1a; }
        .month-date-num { font-size: 0.75rem; font-weight: 700; color: #9ca3af; display: block; margin-bottom: 5px; }
        #clientInfoCard hr { opacity: 0.1; margin: 8px 0; }
        .info-section-title { font-size: 10px; text-transform: uppercase; color: var(--primary); font-weight: 800; margin-bottom: 4px; }
    </style>
</head>

<body class="app-body">
    <?php $activePage = 'bookings'; require_once 'navbar.php'; ?>
    <script>
        const appointments = <?php echo json_encode($dynamicAppts); ?>;
    </script>

    <main class="main-content">
        <div class="page-header">
            <div class="header-text">
                <h1 class="page-title">Booking & Appointment</h1>
                <p class="page-subtitle">Manage your professional schedule and customer flow.</p>
            </div>
            <button class="btn-new-appointment"><span class="material-symbols-outlined">add</span> New Appointment</button>
        </div>

        <div class="dashboard-grid">
            <div class="calendar-card">
                <div class="calendar-toolbar">
                    <div class="toolbar-left">
                        <h2 id="calendarLabel" class="calendar-current-date"></h2>
                        <div id="paginationControls" class="pagination-controls">
                            <button onclick="navigate(-1)" class="nav-arrow"><span class="material-symbols-outlined">chevron_left</span></button>
                            <select id="weekIndicator" class="week-label-dropdown" onchange="jumpToDate(this.value)"></select>
                            <button onclick="navigate(1)" class="nav-arrow"><span class="material-symbols-outlined">chevron_right</span></button>
                        </div>
                    </div>
                    <div class="view-toggle">
                        <button id="btnDay" onclick="switchView('day')" class="toggle-btn">Day</button>
                        <button id="btnWeek" onclick="switchView('week')" class="toggle-btn active">Week</button>
                        <button id="btnMonth" onclick="switchView('month')" class="toggle-btn">Month</button>
                    </div>
                </div>
                <div id="calendarDisplay" class="calendar-body"></div>
            </div>

            <aside class="sidebar">
                <div class="sidebar-card">
                    <div class="sidebar-header">
                        <h3 id="sidebarDay" class="sidebar-title"></h3>
                        <span class="material-symbols-outlined icon-faded">event</span>
                    </div>
                    <section class="sidebar-section">
                        <h4 class="section-label">Client Information</h4>
                        <div class="approval-card" id="clientInfoCard">
                            <p class="cell-faded" style="text-align: center; padding: 10px;">Select a schedule to view client details</p>
                        </div>
                    </section>
                </div>
            </aside>
        </div>

        <div class="data-table-container">
    <div class="table-header">
        <h3 class="table-title">Recent Service Inquiries</h3>
        <a href="requests.php" class="table-link">Manage All Requests <span class="material-symbols-outlined">arrow_forward</span></a>
    </div>
    <div class="scroll-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer Contact</th>
                    <th>Service Type</th>
                    <th>Requested Date</th>
                    <th>Payment Strategy</th>
                    <th>Amount Paid</th>
                </tr>
            </thead>
            <tbody id="pendingTableBody">
                <?php foreach ($pendingRequests as $req): ?>
                <tr>
                    <td>
                        <div class="contact-cell">
                            <span class="contact-name"><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></span>
                            <span class="contact-email text-secondary"><?php echo htmlspecialchars($req['email']); ?></span>
                        </div>
                    </td>
                    <td><span class="service-badge"><?php echo htmlspecialchars($req['service_name'] ?? 'Service'); ?></span></td>
                    <td class="cell-faded"><?php echo htmlspecialchars($req['selected_date'] . ' • ' . $req['selected_time']); ?></td>
                    <td>
                        <!-- Displays strategy (downpayment/full payment) -->
                        <span class="strategy-label"><?php echo ucfirst(htmlspecialchars($req['payment_strategy'] ?? 'N/A')); ?></span>
                    </td>
                    <td class="cell-bold">
                        ₱<?php echo number_format($req['amount_paid'] ?? 0, 2); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
    </main>

    <script>
        const today = new Date();
        let currentDate = new Date(today);
        let currentView = 'week';

        function switchView(view) {
            currentView = view;
            document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(`btn${view.charAt(0).toUpperCase() + view.slice(1)}`).classList.add('active');
            renderCalendar();
        }

        function navigate(dir) {
            if (currentView === 'day') currentDate.setDate(currentDate.getDate() + dir);
            else if (currentView === 'week') currentDate.setDate(currentDate.getDate() + (dir * 7));
            else if (currentView === 'month') currentDate.setMonth(currentDate.getMonth() + dir);
            renderCalendar();
        }

        function jumpToDate(dateStr) {
            currentDate = new Date(dateStr);
            renderCalendar();
        }

        function populateDayDropdown() {
    const dropdown = document.getElementById('weekIndicator');
    dropdown.innerHTML = '';
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    if (currentView === 'day') {
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        for (let i = 1; i <= daysInMonth; i++) {
            const dateValue = new Date(year, month, i);
            const opt = document.createElement('option');
            opt.value = dateValue.toISOString().split('T')[0];
            opt.text = `${currentDate.toLocaleDateString('en-US', { month: 'short' })} ${i}`;
            if (i === currentDate.getDate()) opt.selected = true;
            dropdown.appendChild(opt);
        }
    } else if (currentView === 'week') {
        //finds the first Monday of the month to start the list
        let currentPos = new Date(year, month, 1);
        const dayOffset = currentPos.getDay() === 0 ? 6 : currentPos.getDay() - 1;
        currentPos.setDate(currentPos.getDate() - dayOffset);

        //calculates the Monday of the week currently being viewed
        const activeWeekStart = new Date(currentDate);
        const activeOffset = activeWeekStart.getDay() === 0 ? 6 : activeWeekStart.getDay() - 1;
        activeWeekStart.setDate(activeWeekStart.getDate() - activeOffset);
        activeWeekStart.setHours(0, 0, 0, 0);

        //make up to 6 weeks to cover any possible month layout
        for (let i = 0; i < 6; i++) {
            const weekEnd = new Date(currentPos);
            weekEnd.setDate(currentPos.getDate() + 6);

            if (i > 0 && currentPos.getMonth() !== month && currentPos.getTime() !== activeWeekStart.getTime()) {
                break;
            }

            const opt = document.createElement('option');
            opt.value = currentPos.toISOString().split('T')[0];
            
            //format: "May 4 - May 10"
            const startLabel = currentPos.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            const endLabel = weekEnd.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            opt.text = `${startLabel} - ${endLabel}`;

            if (currentPos.getTime() === activeWeekStart.getTime()) {
                opt.selected = true;
            }

            dropdown.appendChild(opt);
            currentPos.setDate(currentPos.getDate() + 7);
        }
    }
}

        function displayClientInfo(id) {
            let found = null;
            Object.values(appointments).forEach(day => {
                const appt = day.find(a => a.id == id);
                if (appt) found = appt;
            });
            if (found) {
                document.getElementById('clientInfoCard').innerHTML = `
                    <div style="display: flex; flex-direction: column; gap: 10px; font-size: 0.9rem;">
                        <div>
                            <p class="info-section-title">Personal Info</p>
                            <p><strong>Name:</strong> ${found.name}</p>
                            <p><strong>Email:</strong> ${found.email}</p>
                        </div>
                        <hr>
                        <div>
                            <p class="info-section-title">Schedule Details</p>
                            <p><strong>Date:</strong> ${found.date}</p>
                            <p><strong>Time:</strong> ${found.time}</p>
                            <p><strong>Service:</strong> ${found.title}</p>
                        </div>
                        <hr>
                        <div>
                            <p class="info-section-title">Payment & Requests</p>
                            <p><strong>Method:</strong> ${found.method.toUpperCase()}</p>
                            <p><strong>Paid:</strong> ₱${found.paid}</p>
                            <p style="margin-top: 6px;"><strong>Special Request:</strong></p>
                            <p class="cell-faded" style="font-style: italic; background: rgba(0,0,0,0.02); padding: 6px; border-radius: 4px;">
                                "${found.request}"
                            </p>
                        </div>
                    </div>`;
            }
        }

        function getApptHtml(dateKey, isMonth = false) {
            const data = appointments[dateKey] || [];
            return data.map(a => {
                let hoursFromStart = 0;
                if (!isMonth) {
                    const timeMatch = a.time.match(/(\d+):(\d+)\s+(AM|PM)/i);
                    if (timeMatch) {
                        let h = parseInt(timeMatch[1], 10);
                        let m = parseInt(timeMatch[2], 10);
                        if (h === 12 && timeMatch[3].toUpperCase() === 'AM') h = 0;
                        if (h < 12 && timeMatch[3].toUpperCase() === 'PM') h += 12;
                        hoursFromStart = (h - 9) + (m / 60);
                    }
                }
                return `<div class="appt ${a.type} ${isMonth ? 'month-appt' : ''}" onclick="displayClientInfo('${a.id}')" style="${!isMonth ? `top: calc(80px * ${hoursFromStart});` : ''} cursor: pointer;">${a.title} ${!isMonth ? `<br><span class="appt-time">${a.time}</span>` : ''}</div>`;
            }).join('');
        }

        function renderCalendar() {
            const display = document.getElementById('calendarDisplay');
            const label = document.getElementById('calendarLabel');
            const sidebarDay = document.getElementById('sidebarDay');
            const dropdown = document.getElementById('weekIndicator'); 

            label.innerText = currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            sidebarDay.innerText = currentDate.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' });

            if (currentView === 'day') {
                dropdown.style.display = 'block'; 
                populateDayDropdown();
                renderDayView(display);
            } else if (currentView === 'week') {
                dropdown.style.display = 'block'; 
                populateDayDropdown();
                renderWeekView(display);
            } else if (currentView === 'month') {
                dropdown.style.display = 'none';
                renderMonthView(display);
            }
        }

        function renderDayView(container) {
            let html = `<div class="calendar-grid-week" style="grid-template-columns: 80px 1fr;"><div class="time-column"><div class="time-header-spacer"></div>`;
            for (let i = 9; i <= 15; i++) {
                let h = i > 12 ? i - 12 : i;
                let ampm = i >= 12 ? 'PM' : 'AM';
                html += `<div class="time-slot">${String(h).padStart(2, '0')}:00 ${ampm}</div>`;
            }
            html += `</div>`;
            const dateKey = `${currentDate.getFullYear()}-${currentDate.getMonth() + 1}-${currentDate.getDate()}`;
            html += `<div class="day-column active"><div class="day-header-label">${currentDate.toLocaleDateString('en-US', { weekday: 'long' })}</div><div class="day-content-area">${getApptHtml(dateKey)}</div></div></div>`;
            container.innerHTML = html;
        }

        function renderWeekView(container) {
            let html = `<div class="calendar-grid-week"><div class="time-column"><div class="time-header-spacer"></div>`;
            for (let i = 9; i <= 15; i++) {
                let h = i > 12 ? i - 12 : i;
                let ampm = i >= 12 ? 'PM' : 'AM';
                html += `<div class="time-slot">${String(h).padStart(2, '0')}:00 ${ampm}</div>`;
            }
            html += `</div>`;
            const startOfWeek = new Date(currentDate);
            startOfWeek.setDate(currentDate.getDate() - currentDate.getDay() + (currentDate.getDay() === 0 ? -6 : 1));
            for (let i = 0; i < 7; i++) {
                let d = new Date(startOfWeek);
                d.setDate(startOfWeek.getDate() + i);
                const dateKey = `${d.getFullYear()}-${d.getMonth() + 1}-${d.getDate()}`;
                html += `<div class="day-column ${d.toDateString() === today.toDateString() ? 'active' : ''}"><div class="day-header-label">${d.toLocaleDateString('en-US', { weekday: 'short' })} ${d.getDate()}</div><div class="day-content-area">${getApptHtml(dateKey)}</div></div>`;
            }
            html += `</div>`;
            container.innerHTML = html;
        }

        function renderMonthView(container) {
            const start = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
            const end = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
            let html = `<div class="month-grid">`;
            ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(day => html += `<div class="month-day-name">${day}</div>`);
            let startPadding = start.getDay() === 0 ? 6 : start.getDay() - 1;
            for (let i = 0; i < startPadding; i++) html += `<div class="month-cell empty"></div>`;
            for (let d = 1; d <= end.getDate(); d++) {
                const dateKey = `${currentDate.getFullYear()}-${currentDate.getMonth() + 1}-${d}`;
                html += `<div class="month-cell ${d === today.getDate() && currentDate.getMonth() === today.getMonth() ? 'active' : ''}"><span class="month-date-num">${d}</span><div class="month-appt-container">${getApptHtml(dateKey, true)}</div></div>`;
            }
            html += `</div>`;
            container.innerHTML = html;
        }

        document.addEventListener('DOMContentLoaded', renderCalendar);
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>