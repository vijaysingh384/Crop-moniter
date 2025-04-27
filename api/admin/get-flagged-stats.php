<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

try {
    // Get last 6 months of data
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(claim_date, '%b') as month,
            COUNT(CASE WHEN claim_status = 'flagged' THEN 1 END) as flagged_count,
            COUNT(CASE WHEN claim_status = 'resolved' THEN 1 END) as resolved_count
        FROM pension_claims
        WHERE claim_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(claim_date, '%Y-%m'), DATE_FORMAT(claim_date, '%b')
        ORDER BY claim_date ASC
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $months = [];
    $flagged = [];
    $resolved = [];
    
    foreach ($results as $row) {
        $months[] = $row['month'];
        $flagged[] = (int)$row['flagged_count'];
        $resolved[] = (int)$row['resolved_count'];
    }
    
    echo json_encode([
        'months' => $months,
        'flagged' => $flagged,
        'resolved' => $resolved
    ]);
    
} catch (PDOException $e) {
    error_log("Error in get-flagged-stats.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
} 