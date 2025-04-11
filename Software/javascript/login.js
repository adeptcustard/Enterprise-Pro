/**
 * wait for the DOM to fully load before executing the login logic
 */
document.addEventListener("DOMContentLoaded", function () {
    console.log("✅ login.js script is loaded!");

    //select the login form and error message display container
    const loginForm = document.getElementById("login-form");
    const errorMessage = document.getElementById("error-message");

    //exit early if login form is not found in DOM
    if (!loginForm) {
        console.error("❌ Login form not found!");
        return;
    }

    console.log("✅ Login form found!");

    /**
     * Handles form submission, validates user input,
     * and sends a login request to the server.
     * 
     * @param {SubmitEvent} event - The form submission event
     */
    loginForm.addEventListener("submit", async function (event) {
        //prevent default form submission
        event.preventDefault();
        console.log("✅ Login form submitted!");

        //get and trim user input values
        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value.trim();

        //regex for password strength:
        //at least 8 characters, one upper, one lower, one digit, one special character
        const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

        //validation: All fields filled
        if (!email || !password) {
            console.warn("⚠️ Missing email or password!");
            errorMessage.textContent = "Please enter your Email and Password.";
            return;
        }

        //validation: Password strength
        if (!passwordPattern.test(password)) {
            console.warn("⚠️ Weak password!");
            errorMessage.textContent = "Password must be at least 8 characters long and contain an uppercase letter, lowercase letter, number, and special character.";
            return;
        }

        try {
            console.log("📡 Sending login request...");

            //send login request to server
            const response = await fetch("../php/login.php", {
                method: "POST",
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ email, password })
            });

            console.log("📩 Fetch response received:", response);

            //handle fetch error responses
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }

            const data = await response.json();
            console.log("✅ Server Response:", data);

            //redirect based on login result
            if (data.success) {
                if (data.must_change_password) {
                    console.log("🔐 First-time login. Redirecting to reset password page...");
                    window.location.href = "../html/first_password_change.html";
                }
                else {
                    console.log("🔄 Redirecting to:", data.redirect);
                    window.location.href = data.redirect;
                }
            }
            else {
                console.warn("⚠️ Login failed:", data.message);
                errorMessage.textContent = data.message;
            }

        } catch (error) {
            console.error("❌ Login Error:", error);
            errorMessage.textContent = "A server error occurred. Please try again.";
        }
    });

    /**
     * Displays a toast notification message.
     * 
     * @param {string} message - The message to display
     * @param {string} [type="success"] - The type of toast ("success", "error", etc.)
     */
    function showToast(message, type = "success") {
        const toast = document.getElementById("toast");
        if (!toast) return;

        toast.className = `toast ${type}`;
        toast.innerText = message;
        toast.classList.remove("hidden");

        //add visible class for animation
        setTimeout(() => {
            toast.classList.add("show");
        }, 10);

        //hide toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove("show");
            setTimeout(() => {
                toast.classList.add("hidden");
            }, 300);
        }, 3000);
    }
});
