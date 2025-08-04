<?php
function getClientInitials($client_name) {
    $words = preg_split('/\s+/', trim($client_name));
    $initials = '';

    foreach ($words as $word) {
        if (preg_match('/[A-Za-z]/', $word)) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
    }

    return $initials ?: 'XX';
}

function generateCaseNumber($pdo, $client_token) {
    // Get client name from DB using token
    $stmt = $pdo->prepare("SELECT name FROM clients WHERE token = ?");
    $stmt->execute([$client_token]);
    $client = $stmt->fetch();

    $client_name = $client['name'] ?? 'UNKNOWN';
    $initials = getClientInitials($client_name);
    $random = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
    $month_tag = date('Ym'); // e.g., 202507

    // Count existing cases for this client and month
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM cases 
        WHERE client_token = ? AND DATE_FORMAT(submitted_at, '%Y%m') = ?
    ");
    $stmt->execute([$client_token, $month_tag]);
    $row = $stmt->fetch();
    $count = ($row['total'] ?? 0) + 1;

    // Format count as 3-digit number, e.g., 001
    $count_str = str_pad($count, 3, '0', STR_PAD_LEFT);

    return "CASE-{$initials}-{$random}-{$month_tag}{$count_str}";
}
