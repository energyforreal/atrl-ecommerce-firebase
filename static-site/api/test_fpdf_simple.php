<?php
/**
 * Test FPDF Generation
 * Simple test to check if FPDF is working correctly
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Load FPDF
    require_once __DIR__ . '/lib/fpdf/fpdf.php';
    
    // Create a simple test PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Add some content
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Test PDF', 0, 1, 'C');
    
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'This is a test PDF to check if FPDF is working.', 0, 1);
    
    $pdf->Cell(0, 10, 'Date: ' . date('Y-m-d H:i:s'), 0, 1);
    
    // Create test directory
    $testDir = __DIR__ . '/../invoices';
    if (!is_dir($testDir)) {
        mkdir($testDir, 0755, true);
    }
    
    // Save to file
    $filename = 'test_fpdf_' . date('YmdHis') . '.pdf';
    $filepath = $testDir . '/' . $filename;
    
    $pdf->Output('F', $filepath);
    
    if (file_exists($filepath)) {
        $fileSize = filesize($filepath);
        $fileContent = file_get_contents($filepath);
        $base64Content = base64_encode($fileContent);
        
        echo json_encode([
            'success' => true,
            'message' => 'Test PDF generated successfully',
            'filename' => $filename,
            'fileSize' => $fileSize,
            'base64Length' => strlen($base64Content),
            'pdfHeader' => substr($fileContent, 0, 10),
            'pdfHeaderHex' => bin2hex(substr($fileContent, 0, 10))
        ]);
    } else {
        throw new Exception('PDF file was not created');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
