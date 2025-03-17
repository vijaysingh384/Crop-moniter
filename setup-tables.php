<?php
// Database setup script
require_once 'includes/config.php';

// Check if tables need to be created
$tablesNeeded = [
    'pension_schemes',
    'pension_claims'
];

$existingTables = [];
try {
    $result = $db->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $existingTables[] = $row[0];
    }
} catch (PDOException $e) {
    die("Error checking existing tables: " . $e->getMessage());
}

$tablesToCreate = array_diff($tablesNeeded, $existingTables);

if (count($tablesToCreate) > 0) {
    echo "<h2>Creating Missing Tables</h2>";
    
    // Create pension_schemes table if it doesn't exist
    if (in_array('pension_schemes', $tablesToCreate)) {
        try {
            $db->exec("
                CREATE TABLE pension_schemes (
                    scheme_id INT AUTO_INCREMENT PRIMARY KEY,
                    scheme_name VARCHAR(255) NOT NULL,
                    scheme_provider VARCHAR(255) NOT NULL,
                    description TEXT,
                    eligibility_criteria TEXT,
                    benefit_amount VARCHAR(100) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            echo "<p>Created pension_schemes table</p>";
            
            // Insert some sample pension schemes
            $db->exec("
                INSERT INTO pension_schemes (scheme_name, scheme_provider, description, eligibility_criteria, benefit_amount) VALUES
                ('Old Age Pension', 'Ministry of Social Welfare', 'Pension for citizens above 60 years of age', 'Age 60+, income below poverty line', '₹1,500 per month'),
                ('Widow Pension', 'Ministry of Women and Child Development', 'Financial assistance for widows', 'Widowed, age 40+, income below poverty line', '₹1,200 per month'),
                ('Disability Pension', 'Department of Empowerment of Persons with Disabilities', 'Support for individuals with disabilities', 'Disability certificate showing 40%+ disability', '₹1,400 per month'),
                ('Farmers Pension', 'Ministry of Agriculture', 'Pension scheme for farmers who are 60 years or older', 'Age 60+, small and marginal farmers', '₹3,000 per month')
            ");
            echo "<p>Added sample pension schemes</p>";
        } catch (PDOException $e) {
            echo "<p>Error creating pension_schemes table: " . $e->getMessage() . "</p>";
        }
    }
    
    // Create pension_claims table if it doesn't exist
    if (in_array('pension_claims', $tablesToCreate)) {
        try {
            $db->exec("
                CREATE TABLE pension_claims (
                    claim_id INT AUTO_INCREMENT PRIMARY KEY,
                    citizen_id INT NOT NULL,
                    scheme_id INT NOT NULL,
                    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    claim_status ENUM('pending', 'approved', 'rejected', 'flagged') DEFAULT 'pending',
                    notes TEXT,
                    admin_comments TEXT,
                    reviewed_by INT,
                    reviewed_at TIMESTAMP NULL,
                    FOREIGN KEY (scheme_id) REFERENCES pension_schemes(scheme_id),
                    FOREIGN KEY (citizen_id) REFERENCES citizen_profiles(citizen_id)
                )
            ");
            echo "<p>Created pension_claims table</p>";
        } catch (PDOException $e) {
            echo "<p>Error creating pension_claims table: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>Table setup complete!</h3>";
    echo "<p><a href='index.php'>Go back to the main site</a></p>";
} else {
    echo "<h2>All required tables already exist</h2>";
    echo "<p><a href='index.php'>Go back to the main site</a></p>";
}
?>
