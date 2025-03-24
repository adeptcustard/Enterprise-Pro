//wait for the entire document (DOM) to be fully loaded before executing the script
document.addEventListener("DOMContentLoaded", function(){
    //debugging message to confirm script execution
    console.log("✅ login.js script is loaded!"); 

    //select the login form and the error message display area
    const loginForm = document.getElementById("login-form");
    const errorMessage = document.getElementById("error-message");

    //check if the login form exists; if not, log an error and stop execution
    if (!loginForm){
        console.error("❌ Login form not found!");
        return;
    }
    
    //debugging message confirming form detection
    console.log("✅ Login form found!"); 

    //add an event listener to the form that triggers when it is submitted
    loginForm.addEventListener("submit", async function(event){
        //prevents the default form submission behavior
        event.preventDefault(); 
        //debugging message to confirm form submission
        console.log("✅ Login form submitted!"); 

        //retrieve and trim input values from the email and password fields
        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value.trim();

        //regular expression for strong password validation:
        // - At least 8 characters
        // - At least one uppercase letter
        // - At least one lowercase letter
        // - At least one number
        // - At least one special character (@$!%*?&)
        const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

        //check if email or password is empty and display a warning message
        if (!email || !password){
            //debugging warning
            console.warn("⚠️ Missing email or password!"); 
            errorMessage.textContent = "Please enter your Email and Password.";
            return;
        }

        //validate the password against the strong password pattern
        if(!passwordPattern.test(password)){
            //debugging warning
            console.warn("⚠️ Weak password!"); 
            errorMessage.textContent = "Password must be at least 8 characters long and contain an uppercase letter, lowercase letter, number, and special character.";
            return;
        }

        try{
            //debugging message before sending request
            console.log("📡 Sending login request..."); 

            //send login credentials to the server using fetch API
            const response = await fetch("../php/login.php",{
                method: "POST", 
                headers: {
                    "Accept": "application/json",  
                    "Content-Type": "application/json" 
                },
                body: JSON.stringify({ email: email, password: password }), 
            });
            //debugging message after receiving response
            console.log("📩 Fetch response received:", response); 

            //check if the response is valid ( status code is not an error)
            if(!response.ok){
                throw new Error(`HTTP error! Status: ${response.status}`); 
            }

            //convert the server's response from JSON format
            const data = await response.json();
            //debugging message to display the server response
            console.log("✅ Server Response:", data); 

            //if login is successful, redirect the user to the page specified in the response
            if(data.success){
                //redirect to the specified page after successful login
                console.log("🔄 Redirecting to:", data.redirect);
                window.location.href = data.redirect;
            } 
            else{
                //display an error message if login fails
                console.warn("⚠️ Login failed:", data.message);
                errorMessage.textContent = data.message;
            }
        } 
        catch(error){
            //catch any errors related to the request and display an appropriate message

            //debugging message for errors
            console.error("❌ Login Error:", error); 

            //display error message to user
            errorMessage.textContent = "A server error occurred. Please try again."; 
        }
    });
});
