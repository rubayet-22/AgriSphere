<?php
/**
 * AgriSphere OTP Verification Page
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

// Check if there's a pending registration
if (!isset($_SESSION['pending_email'])) {
    redirect(BASE_URL . 'auth/register.php');
}

$email = $_SESSION['pending_email'];
$role = $_SESSION['pending_role'] ?? '';
$demoOtp = $_SESSION['demo_otp'] ?? ''; // For demo purposes only

$roleLabels = [
    'Farmer' => 'Farmer',
    'Customer' => 'Customer'
];
$roleLabel = $roleLabels[$role] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP | <?php echo SITE_NAME; ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/main.css">

    <style>
        .otp-container {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin: 24px 0;
        }

        .otp-input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            border: 2px solid var(--gray-300);
            border-radius: var(--radius);
            transition: all var(--transition-fast);
        }

        .otp-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-100);
            outline: none;
        }

        .otp-input.filled {
            border-color: var(--primary);
            background: var(--primary-50);
        }

        .email-display {
            background: var(--gray-100);
            padding: 12px 20px;
            border-radius: var(--radius);
            text-align: center;
            margin-bottom: 20px;
        }

        .email-display i {
            color: var(--primary);
            margin-right: 8px;
        }

        .timer {
            text-align: center;
            color: var(--gray-500);
            font-size: 14px;
            margin-top: 16px;
        }

        .timer.expired {
            color: var(--error);
        }

        .resend-link {
            color: var(--primary);
            cursor: pointer;
            font-weight: 600;
        }

        .resend-link:hover {
            text-decoration: underline;
        }

        .resend-link.disabled {
            color: var(--gray-400);
            cursor: not-allowed;
        }

        .demo-otp-notice {
            background: #FEF3C7;
            border: 1px solid #F59E0B;
            color: #92400E;
            padding: 16px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            text-align: center;
        }

        .demo-otp-notice strong {
            display: block;
            font-size: 24px;
            letter-spacing: 8px;
            margin-top: 8px;
        }
    </style>
</head>
<body class="auth-page">

    <div class="auth-card" style="max-width: 480px;">
        <div class="auth-header">
            <a href="<?php echo BASE_URL; ?>" class="auth-logo">
                <div class="landing-logo-icon" style="width: 48px; height: 48px;">
                    <i class="fas fa-leaf"></i>
                </div>
                <span class="landing-logo-text"><?php echo SITE_NAME; ?></span>
            </a>

            <h1 class="auth-title">Verify Your Email</h1>
            <p class="auth-subtitle">
                Enter the 6-digit OTP sent to your email
            </p>
        </div>

        <?php displayFlashMessage(); ?>

        <div class="email-display">
            <i class="fas fa-envelope"></i>
            <?php echo htmlspecialchars($email); ?>
        </div>

        <?php if ($demoOtp): ?>
        <div class="demo-otp-notice">
            <i class="fas fa-info-circle"></i> Demo Mode: Your OTP is
            <strong><?php echo $demoOtp; ?></strong>
        </div>
        <?php endif; ?>

        <form action="verify_otp_action.php" method="POST" id="otpForm">
            <div class="otp-container">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required autofocus>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
            </div>

            <input type="hidden" name="otp" id="otpHidden">

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <i class="fas fa-check-circle"></i>
                Verify OTP
            </button>
        </form>

        <div class="timer" id="timer">
            OTP expires in <span id="countdown">10:00</span>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <span class="resend-link disabled" id="resendBtn" onclick="resendOTP()">
                <i class="fas fa-redo"></i> Resend OTP
            </span>
        </div>

        <div class="auth-footer">
            <a href="register.php<?php echo $role ? '?role=' . strtolower($role) : ''; ?>">
                <i class="fas fa-arrow-left"></i> Back to Registration
            </a>
        </div>
    </div>

    <script>
        // OTP Input handling
        const otpInputs = document.querySelectorAll('.otp-input');
        const otpHidden = document.getElementById('otpHidden');
        const otpForm = document.getElementById('otpForm');

        otpInputs.forEach((input, index) => {
            input.addEventListener('input', function(e) {
                // Only allow numbers
                this.value = this.value.replace(/[^0-9]/g, '');

                if (this.value.length === 1) {
                    this.classList.add('filled');
                    // Move to next input
                    if (index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                } else {
                    this.classList.remove('filled');
                }

                // Update hidden field with complete OTP
                updateHiddenOTP();
            });

            input.addEventListener('keydown', function(e) {
                // Handle backspace
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    otpInputs[index - 1].focus();
                    otpInputs[index - 1].value = '';
                    otpInputs[index - 1].classList.remove('filled');
                }
            });

            // Handle paste
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');
                if (pastedData.length === 6) {
                    for (let i = 0; i < 6; i++) {
                        otpInputs[i].value = pastedData[i];
                        otpInputs[i].classList.add('filled');
                    }
                    updateHiddenOTP();
                    otpInputs[5].focus();
                }
            });
        });

        function updateHiddenOTP() {
            let otp = '';
            otpInputs.forEach(input => {
                otp += input.value;
            });
            otpHidden.value = otp;
        }

        // Form submission
        otpForm.addEventListener('submit', function(e) {
            updateHiddenOTP();
            if (otpHidden.value.length !== 6) {
                e.preventDefault();
                alert('Please enter all 6 digits of the OTP');
            }
        });

        // Countdown timer
        let timeLeft = 10 * 60; // 10 minutes in seconds
        const countdownEl = document.getElementById('countdown');
        const timerEl = document.getElementById('timer');
        const resendBtn = document.getElementById('resendBtn');

        function updateCountdown() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

            if (timeLeft <= 0) {
                timerEl.classList.add('expired');
                timerEl.innerHTML = 'OTP has expired. <span class="resend-link" onclick="resendOTP()">Resend OTP</span>';
                resendBtn.classList.remove('disabled');
            } else {
                timeLeft--;
                setTimeout(updateCountdown, 1000);
            }
        }

        updateCountdown();

        // Enable resend after 60 seconds
        setTimeout(() => {
            resendBtn.classList.remove('disabled');
        }, 60000);

        function resendOTP() {
            if (resendBtn.classList.contains('disabled')) return;
            window.location.href = 'resend_otp.php';
        }
    </script>
</body>
</html>
