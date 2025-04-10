/**
 * showToast - Displays a toast-style message.
 * @param {string} message - The message to display
 */
function showToast(message) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}

//this sends the OTP to user's email
function sendOTP() {
    const email = document.getElementById('email').value.trim();
    if (!email) {
        showToast("Please enter a valid email.");
        return;
    }

    fetch('../php/send_password_otp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast("OTP sent successfully.");
                document.getElementById('email-section').classList.add('hidden');
                document.getElementById('otp-section').classList.remove('hidden');
            } else {
                showToast(data.message);
            }
        });
}

//this verifies the entered OTP
function verifyOTP() {
    const otp = document.getElementById('otp').value.trim();
    if (!otp) {
        showToast("Please enter the OTP.");
        return;
    }

    fetch('../php/verify_password_otp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ otp })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast("OTP verified.");
                document.getElementById('otp-section').classList.add('hidden');
                document.getElementById('reset-section').classList.remove('hidden');
            } else {
                showToast(data.message);
            }
        });
}

//this handles the password reset form submission
document.getElementById('reset-section').addEventListener('submit', function (e) {
    e.preventDefault();

    const newPassword = document.getElementById('new-password').value;
    const confirmPassword = document.getElementById('confirm-password').value;

    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;

    if (newPassword !== confirmPassword) {
        showToast("Passwords do not match.");
        return;
    }

    if (!passwordRegex.test(newPassword)) {
        showToast("Password must contain uppercase, lowercase, number, symbol and be at least 8 characters.");
        return;
    }
    fetch('../php/forgot_password_change.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            new_password: newPassword, 
            confirm_password: confirmPassword 
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("✅ Password changed. Redirecting...");
            setTimeout(() => window.location.href = 'login.html', 2000);
        } else {
            showToast(data.message);
        }
    });
});
