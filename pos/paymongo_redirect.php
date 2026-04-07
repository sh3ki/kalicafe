<?php
session_start();
include('../includes/config.php');

// Get status and source_id from URL
$status = isset($_GET['status']) ? $_GET['status'] : 'unknown';
$sourceId = isset($_GET['source_id']) ? $_GET['source_id'] : null;

// Store the result in session to be picked up by the POS
$_SESSION['paymongo_payment_status'] = $status;
$_SESSION['paymongo_source_id'] = $sourceId;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Processing - PayMongo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: moveBackground 20s linear infinite;
        }
        
        @keyframes moveBackground {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        
        .payment-container {
            position: relative;
            z-index: 10;
            background: white;
            border-radius: 25px;
            padding: 50px 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
            width: 90%;
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .payment-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .payment-logo {
            font-size: 1.2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .payment-logo i {
            margin-right: 8px;
        }
        
        .status-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            animation: scaleIn 0.5s ease-out 0.3s both;
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .status-icon.success {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            color: #ffffff;
            box-shadow: 0 10px 30px rgba(132, 250, 176, 0.4);
        }
        
        .status-icon.failed {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: #ffffff;
            box-shadow: 0 10px 30px rgba(250, 112, 154, 0.4);
        }
        
        .status-icon.processing {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .spinner-icon {
            animation: rotate 1.5s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .checkmark {
            animation: checkmark 0.5s ease-out 0.5s both;
        }
        
        @keyframes checkmark {
            0% { transform: scale(0) rotate(-45deg); }
            50% { transform: scale(1.2) rotate(-45deg); }
            100% { transform: scale(1) rotate(0deg); }
        }
        
        .status-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 15px;
            animation: fadeIn 0.5s ease-out 0.6s both;
        }
        
        .status-title.success { color: #10b981; }
        .status-title.failed { color: #ef4444; }
        .status-title.processing { color: #667eea; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .status-message {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
            animation: fadeIn 0.5s ease-out 0.8s both;
        }
        
        .progress-bar-container {
            width: 100%;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 25px;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            animation: progress 3s ease-out;
            border-radius: 2px;
        }
        
        @keyframes progress {
            from { width: 0%; }
            to { width: 100%; }
        }
        
        .redirect-text {
            color: #9ca3af;
            font-size: 0.9rem;
            margin-top: 15px;
            animation: fadeIn 0.5s ease-out 1s both;
        }
        
        .redirect-text i {
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .transaction-details {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            margin-top: 25px;
            text-align: left;
            animation: fadeIn 0.5s ease-out 1.2s both;
        }
        
        .transaction-details .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .transaction-details .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .detail-value {
            color: #1f2937;
            font-weight: 600;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <!-- Header -->
        <div class="payment-header">
            <div class="payment-logo">
                <i class="fas fa-shield-alt"></i>PayMongo Secure Payment
            </div>
            <div style="color: #9ca3af; font-size: 0.85rem;">Transaction Processing</div>
        </div>
        
        <?php if ($status === 'success'): ?>
            <!-- Success State -->
            <div class="status-icon success">
                <i class="fas fa-check checkmark"></i>
            </div>
            <h2 class="status-title success">Payment Successful!</h2>
            <p class="status-message">
                Your payment has been processed successfully.<br>
                Thank you for your purchase.
            </p>
            
            <div class="transaction-details">
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value" style="color: #10b981;">
                        <i class="fas fa-check-circle me-1"></i>Completed
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Transaction ID</span>
                    <span class="detail-value"><?php echo htmlspecialchars($sourceId ?? 'N/A'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date & Time</span>
                    <span class="detail-value"><?php echo date('M d, Y - h:i A'); ?></span>
                </div>
            </div>
            
            <div class="progress-bar-container">
                <div class="progress-bar"></div>
            </div>
            <p class="redirect-text">
                <i class="fas fa-sync-alt me-2"></i>
                Redirecting you back to POS...
            </p>
            
        <?php elseif ($status === 'failed'): ?>
            <!-- Failed State -->
            <div class="status-icon failed">
                <i class="fas fa-times"></i>
            </div>
            <h2 class="status-title failed">Payment Failed</h2>
            <p class="status-message">
                We couldn't process your payment.<br>
                Please try again or use a different payment method.
            </p>
            
            <div class="transaction-details">
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value" style="color: #ef4444;">
                        <i class="fas fa-times-circle me-1"></i>Failed
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date & Time</span>
                    <span class="detail-value"><?php echo date('M d, Y - h:i A'); ?></span>
                </div>
            </div>
            
            <div class="progress-bar-container">
                <div class="progress-bar" style="background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);"></div>
            </div>
            <p class="redirect-text">
                <i class="fas fa-sync-alt me-2"></i>
                Redirecting you back to POS...
            </p>
            
        <?php else: ?>
            <!-- Processing State -->
            <div class="status-icon processing">
                <i class="fas fa-sync-alt spinner-icon"></i>
            </div>
            <h2 class="status-title processing">Processing Payment</h2>
            <p class="status-message">
                Please wait while we verify your payment.<br>
                This may take a few moments.
            </p>
            
            <div class="transaction-details">
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value" style="color: #667eea;">
                        <i class="fas fa-clock me-1"></i>Processing
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date & Time</span>
                    <span class="detail-value"><?php echo date('M d, Y - h:i A'); ?></span>
                </div>
            </div>
            
            <div class="progress-bar-container">
                <div class="progress-bar"></div>
            </div>
            <p class="redirect-text">
                <i class="fas fa-info-circle me-2"></i>
                Do not close this window
            </p>
        <?php endif; ?>
    </div>

    <script>
        // Auto-redirect back to POS after 3 seconds
        setTimeout(function() {
            // First, check the payment status to update the database if needed
            fetch('../check_payment_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    order_id: sessionStorage.getItem('current_order_id')
                })
            })
            .then(r => r.json())
            .then(data => {
                console.log('Payment status refreshed:', data);
                // Send message to parent/opener
                window.opener.postMessage({
                    type: 'paymongo_payment_result',
                    status: '<?php echo $status; ?>',
                    source_id: '<?php echo $sourceId; ?>',
                    payment_status: data.payment_status
                }, '*');
            })
            .catch(error => {
                console.error('Error refreshing payment status:', error);
                // Still send message even if refresh fails
                window.opener.postMessage({
                    type: 'paymongo_payment_result',
                    status: '<?php echo $status; ?>',
                    source_id: '<?php echo $sourceId; ?>'
                }, '*');
            })
            .finally(() => {
                window.close();
            });
        }, 3000);
    </script>
</body>
</html>
