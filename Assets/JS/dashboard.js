/**
 * EduLend - Dashboard Interactive Controller
 * This script handles dashboard UI transitions, modal control, 
 * and dynamic rendering of the credit score gauge.
 */

document.addEventListener("DOMContentLoaded", () => {
    // 1. Initialize Dashboard Components
    animateCreditScore(80); // Defaulting score to 80% on load
});

/**
 * Toggles the visibility of specified modals
 * @param {string} modalId - The HTML ID of the target modal
 */
function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.toggle("hidden");
    }
}

/**
 * Animates the circular SVG credit score gauge based on a percentage (0-100)
 * @param {number} score - The credit rating score to render
 */
function animateCreditScore(score) {
    const circle = document.getElementById("scoreCircle");
    const scoreText = document.getElementById("scoreValue");
    const scoreStatus = document.getElementById("scoreStatus");
    
    if (!circle || !scoreText || !scoreStatus) return;

    // SVG Circle Radius is 70. Circumference = 2 * PI * r = ~440
    const circumference = 2 * Math.PI * 70; 
    
    // Calculate the offset to leave blank (for the visual countdown progress)
    const offset = circumference - (score / 100) * circumference;
    
    // Apply calculation to the SVG stroke-dashoffset properties
    circle.style.strokeDasharray = `${circumference}`;
    circle.style.strokeDashoffset = offset;
    
    // Update text labels
    scoreText.innerText = `${score}%`;

    // Dynamic color coding & status labels based on score boundaries
    if (score >= 75) {
        scoreStatus.innerText = "EXCELLENT";
        scoreStatus.className = "text-xs font-black text-emerald-500";
        circle.className = "text-emerald-500 transition-all duration-1000 ease-out";
    } else if (score >= 50) {
        scoreStatus.innerText = "FAIR";
        scoreStatus.className = "text-xs font-black text-amber-500";
        circle.className = "text-amber-500 transition-all duration-1000 ease-out";
    } else {
        scoreStatus.innerText = "CRITICAL RISK";
        scoreStatus.className = "text-xs font-black text-red-500";
        circle.className = "text-red-500 transition-all duration-1000 ease-out";
    }
}

/**
 * Clears local session cache and redirects user to landing page
 */
function logout() {
    // For now, simple redirect. Later, this will trigger a PHP logout script to clear $_SESSION.
    window.location.href = "index.html";
}