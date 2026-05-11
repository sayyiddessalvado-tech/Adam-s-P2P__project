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