/**
 * Executes once the DOM is fully loaded.
 */
document.addEventListener("DOMContentLoaded", () => {
    /**
     * Fetch user profile details on page load and populate form fields.
     */
    fetch("../php/profile.php")
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                //populate form fields with user data
                document.getElementById("first_name").value = data.user.first_name;
                document.getElementById("last_name").value = data.user.last_name;
                document.getElementById("email").value = data.user.email;

                //enable dark mode if set in user preferences
                if (data.user.dark_mode === true || data.user.dark_mode === "1") {
                    document.body.classList.add("dark-mode");
                }

                //enable dyslexic font mode if set in preferences
                if (data.user.dyslexic_mode === true || data.user.dyslexic_mode === "1") {
                    document.body.classList.add("dyslexic-mode");
                }
            }
            else {
                showToast("❌ Failed to load profile.", "error");
            }
        })
        .catch(() => showToast("❌ Error fetching user data.", "error"));

    /**
     * Handles the password change form submission.
     * 
     * @param {Event} e - Submit event
     */
    document.getElementById("password-form").addEventListener("submit", async (e) => {
        e.preventDefault();

        const current = document.getElementById("current_password").value.trim();
        const newPassword = document.getElementById("new_password").value.trim();
        const confirm = document.getElementById("confirm_password").value.trim();

        //validate required fields
        if (!current || !newPassword || !confirm) {
            alert("Please fill in all password fields.");
            return;
        }

        //validate matching passwords
        if (newPassword !== confirm) {
            alert("New passwords do not match.");
            return;
        }

        try {
            const res = await fetch("../php/update_password.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ current, new_password: newPassword })
            });

            const data = await res.json();

            if (data.success) {
                showToast(data.message, "success");

                //clear password fields after success
                document.getElementById("current_password").value = "";
                document.getElementById("new_password").value = "";
                document.getElementById("confirm_password").value = "";
            }
            else {
                showToast(data.message, "error");
            }
        }
        catch (err) {
            showToast("Error updating password.", "error");
        }
    });

    /**
     * Toggles dark mode and updates preference on the server.
     */
    document.getElementById("dark-mode-toggle").addEventListener("click", () => {
        const isActive = document.body.classList.toggle("dark-mode");
        updatePreference("dark_mode", isActive ? 1 : 0);
    });

    /**
     * Toggles dyslexic font mode and updates preference on the server.
     */
    document.getElementById("dyslexic-toggle").addEventListener("click", () => {
        const isActive = document.body.classList.toggle("dyslexic-mode");
        updatePreference("dyslexic_mode", isActive ? 1 : 0);
    });

    /**
     * Sends user mode preferences (dark mode or dyslexic mode) to the backend.
     * 
     * @param {string} setting - Either 'dark_mode' or 'dyslexic_mode'
     * @param {number} value - 0 to disable, 1 to enable
     */
    async function updatePreference(setting, value) {
        try {
            await fetch("../php/update_preference.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ setting, value })
            });
        } catch (err) {
            alert("Failed to update preference.");
        }
    }

    /**
     * Displays a temporary toast notification.
     * 
     * @param {string} message - Message to display
     * @param {string} [type="success"] - Type of toast ("success", "error", etc.)
     */
    function showToast(message, type = "success") {
        const toast = document.getElementById("toast");
        if (!toast) return;

        toast.className = `toast ${type}`;
        toast.innerText = message;
        toast.classList.remove("hidden");

        //animate toast display
        setTimeout(() => toast.classList.add("show"), 10);

        //hide toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove("show");
            setTimeout(() => toast.classList.add("hidden"), 300);
        }, 3000);
    }
});
