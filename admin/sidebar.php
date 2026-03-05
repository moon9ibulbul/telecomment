<nav id="sidebar" class="w-64 bg-gray-900 h-screen fixed top-0 left-0 overflow-y-auto z-50 hidden md:block">
    <div class="p-6 flex justify-between items-center">
        <h1 class="text-white text-2xl font-bold">Admin Panel</h1>
        <button id="close-sidebar" class="text-gray-300 hover:text-white md:hidden">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
    <ul class="text-gray-300 mt-4">
        <li class="mb-2">
            <a href="index.php" class="block hover:bg-gray-800 hover:text-white py-2 px-6">Dashboard</a>
        </li>
        <li class="mb-2">
            <a href="settings.php" class="block hover:bg-gray-800 hover:text-white py-2 px-6">Settings</a>
        </li>
        <li class="mt-8">
            <a href="logout.php" class="block hover:bg-red-600 bg-red-700 text-white text-center py-2 px-6 mx-4 rounded">Logout</a>
        </li>
    </ul>
</nav>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const openBtn = document.getElementById('open-sidebar');
        const closeBtn = document.getElementById('close-sidebar');

        if (openBtn) {
            openBtn.addEventListener('click', function() {
                sidebar.classList.remove('hidden');
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                sidebar.classList.add('hidden');
            });
        }
    });
</script>