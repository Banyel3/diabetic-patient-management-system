<?php
// Test SQL Server Connection
try {
    $pdo = new PDO(
        'sqlsrv:Server=.\SQLEXPRESS;Database=DiabetaCare',
        '',
        ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to SQL Server successfully!\n\n";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM patients");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Patients count: " . $result['cnt'] . "\n";
    
    // Test views
    $stmt = $pdo->query("SELECT TOP 3 * FROM vw_PatientDashboardSummary");
    echo "✓ vw_PatientDashboardSummary view works\n";
    
    // Test all tables
    $tables = ['clinics', 'users', 'patients', 'appointments', 'medications', 'lab_results'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ $table: " . $result['cnt'] . " rows\n";
    }
    
    echo "\n✓ All database tests passed!\n";
    
} catch (PDOException $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
}
