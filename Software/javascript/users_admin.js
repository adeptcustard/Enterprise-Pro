/**
 * Show a toast-style popup notification to the user.
 * 
 * @param {string} message - The message to display in the toast.
 * @param {string} [type="success"] - The type of toast ("success" or "error").
 */
function showToast(message, type = "success") {
    //get the toast element from the DOM
    const toast = document.getElementById("toast");
    //exit if the toast container is not found
    if (!toast) return;
    //set toast class to apply appropriate styles
    toast.className = `toast ${type}`;
    //set the toast message text
    toast.innerText = message;
    //make toast visible
    toast.classList.remove("hidden");

    //add animation class after a brief delay for transition
    setTimeout(() => {
        toast.classList.add("show");
    }, 10);

    //hide the toast after 3 seconds
    setTimeout(() => {
        toast.classList.remove("show");
        setTimeout(() => {
            //fully hide the toast after animation
            toast.classList.add("hidden");
        }, 300);
    }, 3000);
}

//wait for the entire page to load before executing logic
document.addEventListener("DOMContentLoaded", () => {
    console.log("✅ users_admin.js loaded!");
    //load all users from the database
    fetchUsers();

    //setup collapsible sections
    document.querySelectorAll(".collapsible").forEach(btn => {
        btn.addEventListener("click", function () {
            this.classList.toggle("active"); // Toggle active styling
            const content = this.nextElementSibling;
            content.style.display = content.style.display === "block" ? "none" : "block";
        });
    });

    //search filter for existing user list
    document.getElementById("search-existing-users").addEventListener("input", function () {
        const search = this.value.toLowerCase();
        const users = document.querySelectorAll("#users-list li");
        users.forEach(user => {
            user.style.display = user.textContent.toLowerCase().includes(search) ? "flex" : "none";
        });
    });

    //search filter for password change section
    document.getElementById("search-users-change-pass").addEventListener("input", function () {
        const search = this.value.toLowerCase();
        const users = document.querySelectorAll("#password-user-list-container button");
        users.forEach(user => {
            user.style.display = user.textContent.toLowerCase().includes(search) ? "flex" : "none";
        });
    });

    //search filter for role change section
    document.getElementById("search-users-change-role").addEventListener("input", function () {
        const search = this.value.toLowerCase();
        const users = document.querySelectorAll("#role-user-list-container button");
        users.forEach(user => {
            user.style.display = user.textContent.toLowerCase().includes(search) ? "flex" : "none";
        });
    });
});

/**
 * Fetch users from the server and populate all 3 sections:
 * - View users
 * - Change password
 * - Change role
 */
async function fetchUsers() {
    try {
        const response = await fetch("../php/fetch_users.php");
        const data = await response.json();

        const usersList = document.getElementById("users-list");
        const passwordListContainer = document.getElementById("password-user-list-container");
        const roleListContainer = document.getElementById("role-user-list-container");

        if (!usersList || !passwordListContainer || !roleListContainer) {
            console.error("❌ Required container not found in DOM.");
            return;
        }

        //clear existing user entries
        usersList.innerHTML = "";
        passwordListContainer.innerHTML = "";
        roleListContainer.innerHTML = "";

        if (data.success) {
            data.users.forEach(user => {
                const userDisplay = `${user.first_name} ${user.last_name} (${user.email})`;

                //display in Users list
                const li = document.createElement("li");
                li.className = "user-list-item";
                li.innerHTML = `
                    ${userDisplay} - ${user.role}
                    <button class="delete-btn" onclick="deleteUser(${user.id})">❌</button>
                `;
                usersList.appendChild(li);

                //add to Password section
                const pwBtn = document.createElement("button");
                pwBtn.textContent = userDisplay;
                pwBtn.className = "user-select-btn";
                pwBtn.onclick = () => {
                    document.getElementById("password-selected-name").textContent = userDisplay;
                    document.getElementById("password-form").style.display = "block";
                    window.selectedPasswordUserId = user.id;
                    passwordListContainer.style.display = "none";
                    document.getElementById("search-users-change-pass").style.display = "none";
                    document.getElementById("pass-br").style.display = "none";
                };
                passwordListContainer.appendChild(pwBtn);

                //add to Role section
                const roleBtn = document.createElement("button");
                roleBtn.textContent = userDisplay;
                roleBtn.className = "user-select-btn";
                roleBtn.onclick = () => {
                    document.getElementById("role-selected-name").textContent = userDisplay;
                    document.getElementById("role-form").style.display = "block";
                    window.selectedRoleUserId = user.id;
                    roleListContainer.style.display = "none";
                    document.getElementById("search-users-change-role").style.display = "none";
                    document.getElementById("role-br").style.display = "none";
                };
                roleListContainer.appendChild(roleBtn);
            });
        }
        else {
            usersList.innerHTML = "<li>No users found.</li>";
        }
    }
    catch (error) {
        console.error("❌ Failed to fetch users:", error);
    }
}

/**
 * Adds a new user by sending form data to server
 */
async function addUser() {
    const firstName = document.getElementById("user-first-name").value;
    const lastName = document.getElementById("user-last-name").value;
    const email = document.getElementById("user-email").value;
    const password = document.getElementById("user-password").value;
    const role = document.getElementById("user-role").value;

    if (!firstName || !lastName || !email || !password) {
        showToast("All fields are required.", "error");
        return;
    }

    try {
        const res = await fetch("../php/add_user.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ first_name: firstName, last_name: lastName, email, password, role })
        });

        const data = await res.json();
        if (data.success) {
            showToast("✅ User added successfully!", "success");
            fetchUsers();
        }
        else {
            showToast(data.message || "Failed to add user.", "error");
        }
    }
    catch (err) {
        console.error("❌ Error adding user:", err);
        showToast("❌ Error adding user.", "error");
    }
}

/**
 * Deletes a user by ID after confirmation
 * @param {number} userId - ID of the user to delete
 */
async function deleteUser(userId) {
    if (!confirm("Are you sure you want to delete this user?")) return;

    try {
        const res = await fetch("../php/delete_user.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ user_id: userId })
        });

        const data = await res.json();
        if (data.success) {
            showToast("🗑️ User deleted successfully", "success");
            fetchUsers();
        }
        else {
            showToast(data.message, "error");
        }
    }
    catch (err) {
        console.error("❌ Error deleting user:", err);
        showToast("❌ Failed to delete user.", "error");
    }
}

/**
 * Changes password of the selected user
 */
async function changePassword() {
    const userId = window.selectedPasswordUserId;
    const newPassword = document.getElementById("new-password").value.trim();

    if (!newPassword) {
        showToast("⚠️ New password is required.", "error");
        return;
    }

    //validate password with strong rules
    const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/;
    if (!passwordPattern.test(newPassword)) {
        showToast("⚠️ Password must be at least 8 characters and contain uppercase, lowercase, number, and special character.", "error");
        return;
    }

    try {
        const res = await fetch("../php/change_users_password.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ user_id: userId, new_password: newPassword })
        });

        const data = await res.json();
        showToast(data.message, data.success ? "success" : "error");

        if (data.success) resetPasswordSelection();
    }
    catch (err) {
        console.error("❌ Error changing password:", err);
        showToast("❌ Failed to change password.", "error");
    }
}

/**
 * Changes role of the selected user
 */
async function changeUserRole() {
    const user_id = window.selectedRoleUserId;
    const new_role = document.getElementById("new-role").value;

    try {
        const res = await fetch("../php/change_user_role.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ user_id, new_role })
        });
        const data = await res.json();
        showToast(data.message, data.success ? "success" : "error");
        fetchUsers();
        resetRoleSelection();
    }
    catch (err) {
        console.error("❌ Error changing role:", err);
        showToast("❌ Failed to change role.", "error");
    }
}

/**
 * Reset UI after password change is complete
 */
function resetPasswordSelection() {
    document.getElementById("password-form").style.display = "none";
    document.getElementById("password-user-list-container").style.display = "block";
    document.getElementById("search-users-change-pass").style.display = "block";
    document.getElementById("pass-br").style.display = "block";
}

/**
 * Reset UI after role change is complete
 */
function resetRoleSelection() {
    document.getElementById("role-form").style.display = "none";
    document.getElementById("role-user-list-container").style.display = "block";
    document.getElementById("search-users-change-role").style.display = "block";
    document.getElementById("role-br").style.display = "block";
}
