// assets/js/auth.js

// Function to open/close the Login Modal
function toggleModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.toggle('hidden');
    }
}

// Function to switch between Login and Signup forms
function switchTab(type) {
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');
    const loginTab = document.getElementById('loginTabBtn');
    const signupTab = document.getElementById('signupTabBtn');

    if (type === 'login') {
        // Show Login, Hide Signup
        loginForm.classList.remove('hidden');
        signupForm.classList.add('hidden');

        // Update Tab Styles
        loginTab.className = "w-1/2 pb-3 font-bold text-blue-600 border-b-2 border-blue-600 text-center cursor-pointer";
        signupTab.className = "w-1/2 pb-3 font-medium text-gray-400 text-center cursor-pointer";
    } else {
        // Show Signup, Hide Login
        loginForm.classList.add('hidden');
        signupForm.classList.remove('hidden');

        // Update Tab Styles
        signupTab.className = "w-1/2 pb-3 font-bold text-blue-600 border-b-2 border-blue-600 text-center cursor-pointer";
        loginTab.className = "w-1/2 pb-3 font-medium text-gray-400 text-center cursor-pointer";
    }
}

// Close modal if user clicks outside of the white box
window.onclick = function (event) {
    const modal = document.getElementById('loginModal');
    if (event.target == modal) {
        modal.classList.add('hidden');
    }
}

// Inside your login form submission event handler in auth.js
function handleUserLogin(inputEmail, inputPassword) {

    // 1. SYSTEM ADMINISTRATOR OVERRIDE CHECK
    if (inputEmail === "admin@edulend.com" && inputPassword === "admin123") {
        localStorage.setItem("edulend_session", JSON.stringify({
            id: "ADMIN001",
            name: "System Administrator",
            role: "admin"
        }));

        alert("Administrative authentication successful. Redirecting to Governance Panel...");
        window.location.href = "admin_dashboard.html"; // Jumps into the admin folder
        return true; // Return true if handled
    }

    // 2. STANDARD USER / LENDER CHECK CONTINUES BELOW...
    return false; // Return false to let standard logic proceed
}

// ==========================================
// NEW ADDITION: THE EVENT INTERCEPTOR (THE STOP SIGN)
// ==========================================
document.addEventListener("DOMContentLoaded", () => {
    const loginFormElement = document.getElementById('loginForm');

    if (loginFormElement) {
        loginFormElement.addEventListener('submit', function (e) {

            // 1. Look up your actual input fields in the HTML
            // Note: If your HTML inputs have different IDs/names, match them here!
            const emailInput = loginFormElement.querySelector('input[type="email"]') || document.getElementById('emailInput');
            const passwordInput = loginFormElement.querySelector('input[type="password"]') || document.getElementById('passwordInput');

            if (emailInput && passwordInput) {
                const emailValue = emailInput.value.trim();
                const passwordValue = passwordInput.value;

                // 2. Run your admin override check
                const isAdmin = handleUserLogin(emailValue, passwordValue);

                // 3. If it's an admin, STOP the form from going to api/auth_handler.php
                if (isAdmin) {
                    e.preventDefault();
                }
                // If it's NOT an admin, the code drops through, 
                // e.preventDefault() isn't called, and it submits to the PHP file normally!
            }
        });
    }
}); 