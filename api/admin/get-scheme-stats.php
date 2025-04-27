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
    // Get scheme distribution data
    $stmt = $db->prepare("
        SELECT 
            ps.scheme_name,
            COUNT(pc.claim_id) as claim_count
        FROM pension_schemes ps
        LEFT JOIN pension_claims pc ON ps.scheme_id = pc.scheme_id
        GROUP BY ps.scheme_id, ps.scheme_name
        ORDER BY claim_count DESC
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $schemes = [];
    foreach ($results as $row) {
        $schemes[$row['scheme_name']] = (int)$row['claim_count'];
    }
    
    echo json_encode(['schemes' => $schemes]);
    
} catch (PDOException $e) {
    error_log("Error in get-scheme-stats.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
} 