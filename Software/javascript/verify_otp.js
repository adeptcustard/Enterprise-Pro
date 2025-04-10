// Wait for the DOM (Document Object Model) to fully load before executing any JavaScript
document.addEventListener("DOMContentLoaded", () => {
  console.log("📩 verify_otp.js loaded");
});

/**
 * Verifies the OTP (One-Time Passcode) entered by the user.
 * This function:
 * - Validates input
 * - Sends the OTP to the server for verification
 * - Displays success/error messages
 * - Redirects if successful
 */
async function verifyOTP() {
  //retrieve the OTP input field value and trim any extra spaces
  const otp = document.getElementById("otp").value.trim();

  //get the DOM element to display error messages
  const errorMsg = document.getElementById("otp-error");

  //validate if OTP field is not empty
  if (!otp) {
    errorMsg.textContent = "OTP is required.";
    return;
  }

  try {
    //send OTP to the server for verification via a POST request
    const res = await fetch("../php/verify_otp.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      //send OTP as JSON
      body: JSON.stringify({ otp })
    });

    //parse the JSON response from the server
    const data = await res.json();

    //if OTP is valid
    if (data.success) {
      //show a success toast notification
      showToast("✅ OTP verified!", "success");

      //redirect to the next page after 1 second (e.g. first password change page)
      setTimeout(() => {
        window.location.href = data.redirect;
      }, 1000);
    }
    else {
      //display server error message or fallback text
      errorMsg.textContent = data.message || "Invalid OTP.";

      //show error toast notification
      showToast("❌ Invalid or expired OTP", "error");
    }

  }
  catch (err) {
    //log the error and show a server error message
    console.error("❌ OTP Verification Error:", err);
    errorMsg.textContent = "Server error. Please try again.";
  }
}

/**
 * Displays a toast notification on the screen.
 * @param {string} message - The message to display inside the toast.
 * @param {string} [type="success"] - Type of toast, e.g. "success" or "error".
 */
function showToast(message, type = "success") {
  const toast = document.getElementById("toast"); // Get the toast container

  //set the class and text for the toast
  toast.className = `toast ${type}`;
  toast.innerText = message;
  toast.classList.remove("hidden");

  //trigger show animation after a slight delay
  setTimeout(() => toast.classList.add("show"), 10);

  //remove toast after 3 seconds, and hide it again
  setTimeout(() => {
    toast.classList.remove("show");
    setTimeout(() => toast.classList.add("hidden"), 300);
  }, 3000);
}
