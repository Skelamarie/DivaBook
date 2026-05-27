<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0'); 

try {
    date_default_timezone_set('Asia/Manila');
    $now = new DateTime();
    $todayFormatted = $now->format('F j, Y');
    $current_admin_id = $_SESSION['admin_id'] ?? '';
    $allBookings = [];
    $cursor = $bookingsCollection->find(['admin_id' => $current_admin_id]);
    foreach ($cursor as $doc) {
        $allBookings[] = $doc;
    }
    $totalBookings = count($allBookings);

    function parseBookingDate($dateStr)
    {
        $dt = DateTime::createFromFormat('F j, Y', $dateStr);
        if (!$dt)
            $dt = DateTime::createFromFormat('M j, Y', $dateStr);
        if (!$dt)
            $dt = DateTime::createFromFormat('Y-m-d', $dateStr); // fallback
        return $dt ?: null;
    }

    // categorize bookings by parsed date=================.
    // --- WEEKLY ---
    $weekStart = (clone $now)->modify('Monday this week')->setTime(0, 0, 0);
    $weekEnd = (clone $now)->modify('Sunday this week')->setTime(23, 59, 59);
    $weeklyDayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $weeklyDays = array_fill(0, 7, 0);
    $weeklyTotal = 0;

    // Last week for trend
    $lastWeekStart = (clone $now)->modify('Monday last week')->setTime(0, 0, 0);
    $lastWeekEnd = (clone $now)->modify('Sunday last week')->setTime(23, 59, 59);
    $lastWeekTotal = 0;

    // --- MONTHLY ---
    $monthStart = (clone $now)->modify('first day of this month')->setTime(0, 0, 0);
    $monthEnd = (clone $now)->modify('last day of this month')->setTime(23, 59, 59);
    $daysInMonth = (int) $now->format('t');

    $monthlyDayLabels = [];
    $monthlyDays = array_fill(0, $daysInMonth, 0);
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $monthlyDayLabels[] = (string) $d;
    }
    $monthlyTotal = 0;

    $lastMonthStart = (clone $now)->modify('first day of last month')->setTime(0, 0, 0);
    $lastMonthEnd = (clone $now)->modify('last day of last month')->setTime(23, 59, 59);
    $lastMonthTotal = 0;

    // --- YEARLY ---
    $yearStart = (new DateTime($now->format('Y') . '-01-01'))->setTime(0, 0, 0);
    $yearEnd = (new DateTime($now->format('Y') . '-12-31'))->setTime(23, 59, 59);

    $yearlyMonthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $yearlyMonths = array_fill(0, 12, 0);
    $yearlyTotal = 0;

    $lastYear = (int) $now->format('Y') - 1;
    $lastYearStart = (new DateTime("$lastYear-01-01"))->setTime(0, 0, 0);
    $lastYearEnd = (new DateTime("$lastYear-12-31"))->setTime(23, 59, 59);
    $lastYearTotal = 0;

    // --- STATS ---
    $pendingApprovals = 0;
    $cancelledToday = 0;
    $todayAppointmentsCount = 0;
    $todayAppts = [];
    $pendingReqs = [];

    // SINGLE PASS through all bookings
    foreach ($allBookings as $doc) {
        $dateStr = $doc['selected_date'] ?? '';
        $status = $doc['status'] ?? 'pending';
        $parsedDate = parseBookingDate($dateStr);

        // --- Pending count ---
        if ($status === 'pending') {
            $pendingApprovals++;
            $pendingReqs[] = [
                'id' => (string) $doc['_id'],
                'name' => htmlspecialchars(($doc['first_name'] ?? '') . ' ' . ($doc['last_name'] ?? '')),
                'service' => htmlspecialchars($doc['service_name'] ?? $doc['service'] ?? 'Service'),
                'date' => htmlspecialchars($dateStr),
                'time' => htmlspecialchars($doc['selected_time'] ?? 'N/A')
            ];
        }

        if (!$parsedDate)
            continue;

        // --- Today checks ---
        if ($parsedDate->format('Y-m-d') === $now->format('Y-m-d')) {
            if ($status === 'declined' || $status === 'cancelled') {
                $cancelledToday++;
            }
            if ($status !== 'declined') {
                $todayAppointmentsCount++;
                $todayAppts[] = [
                    'id' => (string) $doc['_id'],
                    'name' => htmlspecialchars(($doc['first_name'] ?? '') . ' ' . ($doc['last_name'] ?? '')),
                    'service' => htmlspecialchars($doc['service_name'] ?? $doc['service'] ?? 'Service'),
                    'time' => htmlspecialchars($doc['selected_time'] ?? 'N/A'),
                    'status' => htmlspecialchars($status)
                ];
            }
        }

        // --- Weekly breakdown ---
        if ($parsedDate >= $weekStart && $parsedDate <= $weekEnd) {
            $dayOfWeek = (int) $parsedDate->format('N') - 1;
            $weeklyDays[$dayOfWeek]++;
            $weeklyTotal++;
        }
        if ($parsedDate >= $lastWeekStart && $parsedDate <= $lastWeekEnd) {
            $lastWeekTotal++;
        }

        // --- Monthly breakdown ---
        if ($parsedDate >= $monthStart && $parsedDate <= $monthEnd) {
            $dayOfMonth = (int) $parsedDate->format('j') - 1;
            $monthlyDays[$dayOfMonth]++;
            $monthlyTotal++;
        }
        if ($parsedDate >= $lastMonthStart && $parsedDate <= $lastMonthEnd) {
            $lastMonthTotal++;
        }

        // --- Yearly breakdown ---
        if ($parsedDate >= $yearStart && $parsedDate <= $yearEnd) {
            $monthIndex = (int) $parsedDate->format('n') - 1;
            $yearlyMonths[$monthIndex]++;
            $yearlyTotal++;
        }
        if ($parsedDate >= $lastYearStart && $parsedDate <= $lastYearEnd) {
            $lastYearTotal++;
        }
    }

    // --- Sort pending by created_at descending ---
    usort($pendingReqs, function ($a, $b) {
        return 0; }); // already in cursor order

    // --- Trends ---
    $weeklyTrend = $lastWeekTotal > 0 ? round((($weeklyTotal - $lastWeekTotal) / $lastWeekTotal) * 100, 1) : 0;
    $weeklyTrendIcon = $weeklyTrend >= 0 ? 'trending_up' : 'trending_down';
    $weeklyTrendClass = $weeklyTrend >= 0 ? 'text-success' : 'text-danger';

    $monthlyTrend = $lastMonthTotal > 0 ? round((($monthlyTotal - $lastMonthTotal) / $lastMonthTotal) * 100, 1) : 0;
    $monthlyTrendIcon = $monthlyTrend >= 0 ? 'trending_up' : 'trending_down';
    $monthlyTrendClass = $monthlyTrend >= 0 ? 'text-success' : 'text-danger';

    $yearlyTrend = $lastYearTotal > 0 ? round((($yearlyTotal - $lastYearTotal) / $lastYearTotal) * 100, 1) : 0;
    $yearlyTrendIcon = $yearlyTrend >= 0 ? 'trending_up' : 'trending_down';
    $yearlyTrendClass = $yearlyTrend >= 0 ? 'text-success' : 'text-danger';

    echo json_encode([
        'success' => true,
        'timestamp' => time(),
        'totalBookings' => $totalBookings,
        'weekly' => [
            'count' => $weeklyTotal,
            'labels' => $weeklyDayLabels,
            'data' => $weeklyDays,
            'trend' => $weeklyTrend,
            'trendIcon' => $weeklyTrendIcon,
            'trendClass' => $weeklyTrendClass
        ],
        'monthly' => [
            'count' => $monthlyTotal,
            'labels' => $monthlyDayLabels,
            'data' => $monthlyDays,
            'trend' => $monthlyTrend,
            'trendIcon' => $monthlyTrendIcon,
            'trendClass' => $monthlyTrendClass
        ],
        'yearly' => [
            'count' => $yearlyTotal,
            'labels' => $yearlyMonthLabels,
            'data' => $yearlyMonths,
            'trend' => $yearlyTrend,
            'trendIcon' => $yearlyTrendIcon,
            'trendClass' => $yearlyTrendClass
        ],
        'pendingApprovals' => $pendingApprovals,
        'cancelledToday' => $cancelledToday,
        'todayAppointments' => [
            'count' => $todayAppointmentsCount,
            'items' => $todayAppts
        ],
        'pendingServiceApprovals' => [
            'count' => $pendingApprovals,
            'items' => $pendingReqs
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>