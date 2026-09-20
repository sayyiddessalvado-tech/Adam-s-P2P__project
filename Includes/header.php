<?php
// Includes/header.php - reusable header for EduLend pages
?>
<header class="bg-white/90 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-30 px-4 sm:px-8 py-3.5 shadow-sm transition-all duration-300">
    <div class="max-w-7xl mx-auto flex justify-between items-center">

        <div class="flex items-center gap-3 group cursor-pointer">
            <span class="text-2xl sm:text-3xl font-black bg-gradient-to-r from-blue-700 via-blue-600 to-emerald-500 bg-clip-text text-transparent tracking-tight hover:opacity-90 transition-opacity">
                EduLend
            </span>
            <span class="text-[10px] bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded-md font-extrabold uppercase tracking-widest hidden sm:inline-block">Investor Console</span>
        </div>

        <div class="flex items-center gap-3 sm:gap-5">

            <div class="flex items-center gap-2.5 bg-slate-50/80 border border-slate-200 px-3.5 py-1.5 rounded-full shadow-xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs sm:text-sm font-extrabold text-slate-800">
                    <?php echo htmlspecialchars($lender_name); ?>
                </span>

                <span class="px-2.5 py-0.5 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-full text-[10px] font-black uppercase tracking-wider">
                    <?php echo htmlspecialchars($verification_status); ?>
                </span>
            </div>

            <a href="#notifications" onclick="showNotifications(); return false;" class="relative p-2 text-slate-400 hover:text-blue-600 rounded-full hover:bg-blue-50 transition-all duration-200 hover:scale-110 active:scale-95">
                <i class="fa-solid fa-bell text-lg"></i>
            </a>

            <a href="logout.php" class="text-slate-500 hover:text-rose-600 text-xs sm:text-sm font-bold flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl hover:bg-rose-50 border border-transparent hover:border-rose-200/60 transition-all duration-200">
                <i class="fa-solid fa-right-from-bracket text-sm"></i>
                Logout
            </a>

        </div>
    </div>
</header>
