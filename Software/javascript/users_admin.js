//wait for the DOM to be fully loaded before running the script
document.addEventListener("DOMContentLoaded", function () {
     //log confirmation of script load
    console.log("✅ users_admin.js loaded!");
    //fetch and display the list of users
    fetchUsers(); 
});

//function to fetch users from the server and display them - populate dropdowns for password and role changes
async function fetchUsers() {
    try {
        //send a request to fetch users
        const response = await fetch("../php/fetch_users.php");
        const data = await response.json();

        //get references to the HTML elements for displaying users
        const usersList = document.getElementById("users-list");
        const passwordUserSelect = document.getElementById("password-user-select");
        const roleUserSelect = document.getElementById("role-user-select");

        //clear existing content in the lists
        usersList.innerHTML = "";
        passwordUserSelect.innerHTML = '<input type="text" id="search-password-user" placeholder="Search user...">';
        roleUserSelect.innerHTML = '<input type="text" id="search-role-user" placeholder="Search user...">';

        //check if the request was successful and users are available
        if (data.success) {
            data.users.forEach(user => {
                //create a list item for each user
                const li = document.createElement("li");
                li.innerHTML = `
                    ${user.first_name} ${user.last_name} (${user.email}) - Role: ${user.role}
                    <button onclick="deleteUser(${user.id})">❌ Delete</button>
                `;
                //add the user to the list
                usersList.appendChild(li); 

                //populate the dropdowns for changing password and role
                const option1 = document.createElement("option");
                option1.value = user.id;
                option1.text = `${user.first_name} ${user.last_name} (${user.email})`;
                passwordUserSelect.appendChild(option1);

                //clone the option for role selection
                const option2 = option1.cloneNode(true); 
                roleUserSelect.appendChild(option2);
            });

            //enable search filtering on dropdowns
            enableSearchFilter("search-password-user", passwordUserSelect);
            enableSearchFilter("search-role-user", roleUserSelect);

        } 
        else {
            //display a message if no users are found
            usersList.innerHTML = "<p>No users found.</p>";
        }
    } 
    catch (error) {
        //log error in case of failure
        console.error("❌ Error fetching users:", error); 
    }
}

//search filter function for dropdowns
function enableSearchFilter(searchInputId, dropdown){
    document.getElementById(searchInputId).addEventListener("input", function () {
        const searchText = this.value.toLowerCase();
        for (const option of dropdown.options) {
            if (option.text.toLowerCase().includes(searchText)) {
                option.style.display = "block";
            } 
            else {
                option.style.display = "none";
            }
        }
    });
}

//function to add a new user
async function addUser() {
    //get values from input fields
    const email = document.getElementById("user-email").value;
    const password = document.getElementById("user-password").value;
    const role = document.getElementById("user-role").value;

    //ensure email and password fields are filled
    if (!email || !password) {
        alert("Email and password are required.");
        return;
    }

    try {
        //send a request to add a new user
        const response = await fetch("../php/add_user.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email, password, role })
        });

        const data = await response.json();

        //if user is successfully added, refresh the user list
        if (data.success) {
            fetchUsers();
        } 
        else {
            //show an error message
            alert("Error: " + data.message); 
        }
    } catch (error) {
        //log error in case of failure
        console.error("❌ Error adding user:", error); 
    }
}

//function to delete a user
async function deleteUser(userId) {
    //confirm before proceeding with deletion
    if (!confirm("Are you sure you want to delete this user?")) return;

    try {
        //send a request to delete the user
        const response = await fetch(`../php/delete_user.php?user_id=${userId}`, { method: "DELETE" });
        const data = await response.json();

        //if deletion is successful, refresh the user list
        if (data.success) {
            fetchUsers();
        } 
        else {
            //show an error message
            alert("Error: " + data.message); 
        }
    } catch (error) {
        //log error in case of failure
        console.error("❌ Error deleting user:", error); 
    }
}

//function to change a user's password
async function changePassword() {
    //get selected user ID and new password from input fields
    const userId = document.getElementById("password-user-select").value;
    const newPassword = document.getElementById("new-password").value;

    //ensure a new password is entered
    if (!newPassword) {
        alert("New password is required.");
        return;
    }

    try {
        //send a request to update the user's password
        const response = await fetch("../php/change_password.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ userId, newPassword })
        });

        const data = await response.json();

        //show success or error message
        alert(data.message); 
    } 
    catch (error) {
        //log error in case of failure
        console.error("❌ Error changing password:", error); 
    }
}

//function to change a user's role
async function changeUserRole() {
    //get selected user ID and new role from input fields
    const userId = document.getElementById("role-user-select").value;
    const newRole = document.getElementById("new-role").value;

    try {
        //send a request to update the user's role
        const response = await fetch("../php/change_role.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ userId, newRole })
        });

        const data = await response.json();

        //show success or error message
        alert(data.message); 

        //refresh the user list after role update
        fetchUsers(); 
    } 
    catch (error) {
        //log error in case of failure
        console.error("❌ Error changing role:", error); 
    }
}
