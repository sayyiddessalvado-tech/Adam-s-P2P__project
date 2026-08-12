 /**
 * EduLend - Dashboard Interactive Controller
 * Handles user micro-credit request interactions and credit score gauge animations.
 */

document.addEventListener("DOMContentLoaded", () => {
    setupLoanRequests();
});

/**
 * Handles requesting micro-credits dynamically through the system backend
 */
function setupLoanRequests() {
    // Looks for the loan requesting trigger card on the student dashboard
    const requestLoanCard = document.querySelector('div[class*="hover:border-blue-600"]');

    if (requestLoanCard) {
        requestLoanCard.addEventListener("click", () => {
            const amount = parseFloat(prompt("Enter the micro-credit amount you need (₦):"));
            if (isNaN(amount) || amount <= 0) return alert("Invalid amount entered.");

            fetch('api/request_loan.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount: amount })
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    window.location.reload(); // Refresh screen to show the new live balance and debt
                }
            })
            .catch(err => {
                console.error("Loan System API Error:", err);
                alert("Could not process request. Ensure backend API node is reachable.");
            });
        });
    }
}

/**
 * Animates the circular SVG credit score gauge based on real database scores
 * @param {number} score - The credit rating score to render (0 - 100)
 */
function animateCreditScore(score) {
    const circle = document.getElementById("scoreCircle");
    const scoreText = document.getElementById("scoreValue");
    const scoreStatus = document.getElementById("scoreStatus");
    
    if (!circle || !scoreText || !scoreStatus) return;

    // SVG Circle Radius is 70. Circumference = 2 * PI * r = ~440
    const circumference = 2 * Math.PI * 70; 
    
    // Calculate how much stroke to leave empty (visual countdown indicator)
    const offset = circumference - (score / 100) * circumference;
    
    // Apply calculations smoothly to SVG properties
    circle.style.strokeDasharray = `${circumference}`;
    circle.style.strokeDashoffset = offset;
    
    // Update score percentage indicator label
    scoreText.innerText = `${score}%`;

    // Dynamic color coding & state evaluation
    if (score >= 75) {
        scoreStatus.innerText = "EXCELLENT";
        scoreStatus.className = "text-xs font-black text-emerald-500";
        circle.setAttribute("class", "text-emerald-500 transition-all duration-1000 ease-out");
    } else if (score >= 50) {
        scoreStatus.innerText = "FAIR";
        scoreStatus.className = "text-xs font-black text-amber-500";
        circle.setAttribute("class", "text-amber-500 transition-all duration-1000 ease-out");
    } else {
        scoreStatus.innerText = "CRITICAL RISK";
        scoreStatus.className = "text-xs font-black text-red-500";
        circle.setAttribute("class", "text-red-500 transition-all duration-1000 ease-out");
    }
}