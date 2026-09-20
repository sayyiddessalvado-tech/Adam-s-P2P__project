<?php
// Includes/sidebar.php - reusable sidebar for EduLend admin layout
?>
<aside class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:w-72 lg:flex lg:flex-col bg-slate-900/95 text-white border-r border-slate-800 z-40">
    <div class="p-6 border-b border-slate-800/60">
        <div class="text-2xl font-black tracking-tight">EduLend</div>
        <div class="text-xs text-slate-300 mt-1">Investor Console</div>
    </div>

    <nav class="flex-1 p-4 space-y-1 text-sm">
        <a href="#dashboard" class="block px-3 py-2 rounded-xl hover:bg-slate-800/60">Dashboard</a>
        <a href="#deposit-withdraw" class="block px-3 py-2 rounded-xl hover:bg-slate-800/60">Deposit / Withdraw</a>
        <button id="showNotificationsBtn" onclick="showNotifications();" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-800/60">Notifications</button>
        <a href="#reports" class="block px-3 py-2 rounded-xl hover:bg-slate-800/60">Reports</a>
        <a href="#settings" class="block px-3 py-2 rounded-xl hover:bg-slate-800/60">Settings</a>
    </nav>

    <div class="px-4 pb-4 space-y-3">
        <div class="rounded-2xl border border-slate-700 bg-slate-800/70 p-4">
            <div class="flex items-center justify-between text-[11px] font-black uppercase tracking-wider text-slate-400">
                <span>Available Capital</span>
                <i class="fa-solid fa-coins text-emerald-400"></i>
            </div>
            <div class="mt-2 text-xl font-black text-white">₦<?php echo number_format($available_withdraw, 2); ?></div>
            <div class="mt-1 text-[11px] text-slate-400">Ready to allocate or withdraw</div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-slate-700 bg-slate-800/50 p-3">
                <div class="text-[10px] font-black uppercase tracking-wide text-slate-500">Students</div>
                <div class="mt-1 text-lg font-black text-amber-300"><?php echo $students_sponsored; ?></div>
            </div>
            <div class="rounded-2xl border border-slate-700 bg-slate-800/50 p-3">
                <div class="text-[10px] font-black uppercase tracking-wide text-slate-500">Recovery</div>
                <div class="mt-1 text-lg font-black text-teal-300"><?php echo $recovery_rate; ?>%</div>
            </div>
        </div>
    </div>

    <div class="p-4 border-t border-slate-800/60">
        <div class="text-xs text-slate-400">Signed in as</div>
        <div class="font-bold mt-1"><?php echo htmlspecialchars($lender_name); ?></div>
        <div class="text-[11px] mt-2 text-slate-300">Balance: ₦<?php echo number_format($wallet_balance,2); ?></div>
    </div>
</aside>
