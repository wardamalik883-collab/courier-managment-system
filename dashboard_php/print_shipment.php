<?php
session_start();

// Database connection
$connect = mysqli_connect("localhost", "root", "", "courier_management");
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

$tracking_number = isset($_GET['tracking']) ? mysqli_real_escape_string($connect, $_GET['tracking']) : '';
$shipment = null;

if (!empty($tracking_number)) {
    $query = "SELECT * FROM shipments WHERE tracking_number='$tracking_number'";
    $result = mysqli_query($connect, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $shipment = mysqli_fetch_assoc($result);
    }
}

// Get company settings
$settings_query = "SELECT * FROM settings";
$settings_result = mysqli_query($connect, $settings_query);
$settings = [];
while ($row = mysqli_fetch_assoc($settings_result)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

if (!$shipment) {
    die("Shipment not found!");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Print Shipment - <?php echo htmlspecialchars($shipment['tracking_number']); ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:Arial,sans-serif;background:#f5f5f5;padding:20px;}
.print-container{max-width:800px;margin:0 auto;background:white;box-shadow:0 0 20px rgba(0,0,0,0.1);}
.print-header{background:#c8553d;color:white;padding:25px;text-align:center;}
.company-name{font-size:24px;font-weight:700;margin-bottom:5px;}
.company-info{font-size:13px;opacity:0.9;}
.shipment-title{background:#1e293b;color:white;padding:15px;text-align:center;font-size:18px;font-weight:600;}
.print-body{padding:25px;}
.info-section{margin-bottom:25px;}
.section-title{font-size:14px;color:#c8553d;font-weight:600;margin-bottom:12px;padding-bottom:8px;border-bottom:2px solid #c8553d;text-transform:uppercase;letter-spacing:1px;}
.info-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:15px;}
.info-box{background:#f8fafc;padding:12px;border-radius:6px;border-left:3px solid #c8553d;}
.info-box label{font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:4px;}
.info-box .value{font-size:14px;color:#1e293b;font-weight:600;}
.full-width{grid-column:1/-1;}
.status-highlight{background:#f0fdf4;border:2px solid #16a34a;padding:15px;border-radius:8px;text-align:center;margin-bottom:20px;}
.status-label{font-size:11px;color:#166534;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;}
.status-value{font-size:22px;color:#16a34a;font-weight:700;}
.tracking-box{background:#1e293b;color:white;padding:15px;border-radius:8px;text-align:center;margin-bottom:20px;}
.tracking-label{font-size:11px;opacity:0.8;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;}
.tracking-number{font-size:28px;font-weight:700;letter-spacing:2px;}
.details-table{width:100%;border-collapse:collapse;margin-top:15px;}
.details-table td{padding:10px;border-bottom:1px solid #e2e8f0;font-size:13px;}
.details-table td:first-child{font-weight:600;color:#64748b;width:40%;}
.details-table td:last-child{color:#1e293b;}
.print-footer{background:#f8fafc;padding:20px;text-align:center;font-size:12px;color:#64748b;border-top:1px solid #e2e8f0;}
.print-actions{text-align:center;margin:20px 0;}
.print-btn{background:#1e293b;color:white;padding:12px 30px;border:none;border-radius:6px;cursor:pointer;font-size:14px;font-weight:600;margin:0 10px;}
.print-btn.secondary{background:#64748b;}
.print-btn:hover{opacity:0.9;}
.barcode{font-family:'Courier New',monospace;font-size:10px;background:#f8fafc;padding:10px;text-align:center;border:1px dashed #c8553d;margin-top:15px;}
@media print{
    body{background:white;padding:0;}
    .print-container{box-shadow:none;max-width:100%;}
    .print-actions{display:none;}
}
</style>
</head>
<body>

<div class="print-container">
  <div class="print-header">
    <div class="company-name"><?php echo htmlspecialchars($settings['company_name'] ?? 'Calma Courier Services'); ?></div>
    <div class="company-info">
      <?php echo htmlspecialchars($settings['company_address'] ?? 'Main Office'); ?> | 
      <?php echo htmlspecialchars($settings['company_phone'] ?? '0300-0000000'); ?> | 
      <?php echo htmlspecialchars($settings['company_email'] ?? 'info@calma.com'); ?>
    </div>
  </div>

  <div class="shipment-title">📦 Shipment Tracking Details</div>

  <div class="print-body">
    
    <!-- Tracking Number Highlight -->
    <div class="tracking-box">
      <div class="tracking-label">Tracking / Consignment Number</div>
      <div class="tracking-number"><?php echo htmlspecialchars($shipment['tracking_number']); ?></div>
    </div>

    <!-- Current Status -->
    <div class="status-highlight">
      <div class="status-label">Current Status</div>
      <div class="status-value"><?php echo htmlspecialchars($shipment['status']); ?></div>
      <?php if ($shipment['delivered_date']): ?>
        <div style="margin-top:8px;font-size:13px;color:#166534;">Delivered: <?php echo $shipment['delivered_date']; ?></div>
      <?php endif; ?>
    </div>

    <!-- Sender Information -->
    <div class="info-section">
      <div class="section-title">📤 Sender Information</div>
      <div class="info-grid">
        <div class="info-box">
          <label>Name</label>
          <div class="value"><?php echo htmlspecialchars($shipment['sender_name']); ?></div>
        </div>
        <div class="info-box">
          <label>Phone</label>
          <div class="value"><?php echo htmlspecialchars($shipment['sender_phone']); ?></div>
        </div>
        <div class="info-box">
          <label>City</label>
          <div class="value"><?php echo htmlspecialchars($shipment['sender_city']); ?></div>
        </div>
        <div class="info-box">
          <label>Email</label>
          <div class="value"><?php echo htmlspecialchars($shipment['sender_email'] ?? 'N/A'); ?></div>
        </div>
        <div class="info-box full-width">
          <label>Address</label>
          <div class="value"><?php echo htmlspecialchars($shipment['sender_address'] ?? 'N/A'); ?></div>
        </div>
      </div>
    </div>

    <!-- Receiver Information -->
    <div class="info-section">
      <div class="section-title">📥 Receiver Information</div>
      <div class="info-grid">
        <div class="info-box">
          <label>Name</label>
          <div class="value"><?php echo htmlspecialchars($shipment['receiver_name']); ?></div>
        </div>
        <div class="info-box">
          <label>Phone</label>
          <div class="value"><?php echo htmlspecialchars($shipment['receiver_phone']); ?></div>
        </div>
        <div class="info-box">
          <label>City</label>
          <div class="value"><?php echo htmlspecialchars($shipment['receiver_city']); ?></div>
        </div>
        <div class="info-box">
          <label>Email</label>
          <div class="value"><?php echo htmlspecialchars($shipment['receiver_email'] ?? 'N/A'); ?></div>
        </div>
        <div class="info-box full-width">
          <label>Address</label>
          <div class="value"><?php echo htmlspecialchars($shipment['receiver_address'] ?? 'N/A'); ?></div>
        </div>
      </div>
    </div>

    <!-- Shipment Details -->
    <div class="info-section">
      <div class="section-title">📦 Shipment Details</div>
      <table class="details-table">
        <tr>
          <td>Shipment Type</td>
          <td><?php echo htmlspecialchars($shipment['type']); ?></td>
        </tr>
        <tr>
          <td>Weight</td>
          <td><?php echo $shipment['weight'] ? $shipment['weight'].' kg' : 'N/A'; ?></td>
        </tr>
        <tr>
          <td>Booking Amount</td>
          <td>Rs. <?php echo number_format($shipment['amount']); ?></td>
        </tr>
        <tr>
          <td>Payment Status</td>
          <td><?php echo htmlspecialchars($shipment['payment_status']); ?></td>
        </tr>
        <tr>
          <td>Booked Date</td>
          <td><?php echo $shipment['booked_date']; ?></td>
        </tr>
        <tr>
          <td>Expected Delivery</td>
          <td><?php echo $shipment['delivery_date'] ?? 'Not specified'; ?></td>
        </tr>
        <?php if ($shipment['notes']): ?>
        <tr>
          <td>Notes</td>
          <td><?php echo htmlspecialchars($shipment['notes']); ?></td>
        </tr>
        <?php endif; ?>
      </table>
    </div>

    <!-- Barcode -->
    <div class="barcode">
      *<?php echo htmlspecialchars($shipment['tracking_number']); ?>*<br>
      Generated: <?php echo date('Y-m-d H:i:s'); ?>
    </div>

  </div>

  <div class="print-footer">
    <p><strong><?php echo htmlspecialchars($settings['company_name'] ?? 'Calma Courier Services'); ?></strong></p>
    <p>Thank you for choosing our services!</p>
    <p style="margin-top:10px;font-size:10px;">This is a computer-generated document. No signature required.</p>
  </div>

  <div class="print-actions">
    <button class="print-btn" onclick="window.print()">🖨️ Print</button>
    <button class="print-btn secondary" onclick="window.close()">Close</button>
  </div>

</div>

</body>
</html>
