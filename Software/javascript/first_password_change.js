/**
 * wait for the DOM content to fully load before running any JavaScript logic
 */
document.addEventListener("DOMContentLoaded", () => {
    //select DOM elements
    const form = document.getElementById("password-change-form");
    const newPasswordInput = document.getElementById("new-password");
    const confirmPasswordInput = document.getElementById("confirm-password");
    const message = document.getElementById("error-message");

    /**
     * Handles the form submission to change the user's password
     * Validates input before sending to server
     * 
     * @param {Event} e - The submit event triggered by the form
     */
    form.addEventListener("submit", async (e) => {
        //prevent default form submission
        e.preventDefault(); 

        //get trimmed values from inputs
        const newPassword = newPasswordInput.value.trim();
        const confirmPassword = confirmPasswordInput.value.trim();

        //define a strong password pattern (min 8 chars, 1 upper, 1 lower, 1 number, 1 special)
        const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

        //validation: Check if all fields are filled
        if (!newPassword || !confirmPassword) {
            message.textContent = "Both fields are required.";
            return;
        }

        //validation: Check if both passwords match
        if (newPassword !== confirmPassword) {
            message.textContent = "Passwords do not match.";
            return;
        }

        //validation: Check if new password meets strength requirements
        if (!passwordPattern.test(newPassword)) {
            message.textContent = "Password must be at least 8 characters and contain uppercase, lowercase, number, and special character.";
            return;
        }

        //attempt to send the password update to the backend
        try {
            const response = await fetch("../php/reset_password.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                //send new password in JSON body
                body: JSON.stringify({ newPassword }) 
            });

            const data = await response.json();

            if (data.success) {
                showToast("✅ Password changed successfully! Redirecting to login...", "success");

                //redirect to login after 3 seconds
                setTimeout(() => {
                    window.location.href = "../html/login.html";
                }, 3000);
            } else {
                message.textContent = data.message || "❌ Failed to change password.";
            }
        } catch (err) {
            console.error("❌ Error:", err);
            message.textContent = "An error occurred. Please try again.";
        }
    });
});

/**
 * Displays a toast-style message temporarily on screen
 *
 * @param {string} msg - The message to display
 * @param {string} [type="success"] - Type of toast ("success", "error", etc.)
 */
function showToast(msg, type = "success") {
    const toast = document.getElementById("toast");
    toast.className = `toast ${type}`;
    toast.textContent = msg;
    toast.classList.remove("hidden");

    //add transition effect
    setTimeout(() => toast.classList.add("show"), 10);

    //hide the toast after 3 seconds
    setTimeout(() => {
        toast.classList.remove("show");
        setTimeout(() => toast.classList.add("hidden"), 300);
    }, 3000);
}
