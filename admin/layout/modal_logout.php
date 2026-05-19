<!-- Modal Logout -->
<div id="logoutModal" class="modal-overlay fixed inset-0 z-[100] hidden items-center justify-center p-4">
    <div class="modal-card bg-white w-full max-w-sm rounded-[2.5rem] shadow-2xl overflow-hidden border border-gray-100">
        <div class="p-8 text-center">
            <div class="w-20 h-20 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                <i class="fas fa-sign-out-alt"></i>
            </div>

            <h3 class="text-xl font-black text-gray-800 mb-2 uppercase tracking-tight">
                Konfirmasi Keluar
            </h3>

            <p class="text-gray-400 text-sm leading-relaxed mb-8">
                Apakah Anda yakin ingin keluar dari sistem?
            </p>

            <div class="grid grid-cols-2 gap-4">
                <button onclick="toggleLogoutModal(false)"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-4 rounded-2xl transition uppercase tracking-widest text-xs">
                    Tidak
                </button>

                <a href="../auth/logout.php"
                    class="bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-2xl transition shadow-lg shadow-red-200 uppercase tracking-widest text-xs flex items-center justify-center">
                    Iya, Keluar
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.modal-overlay {
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    transition: all 0.3s ease;
}
.modal-card {
    transform: scale(0.9);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-active .modal-card {
    transform: scale(1);
    opacity: 1;
}
</style>

<script>
function toggleLogoutModal(show) {
    const modal = document.getElementById('logoutModal');

    if (show) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        setTimeout(() => {
            modal.classList.add('modal-active');
        }, 10);

    } else {
        modal.classList.remove('modal-active');

        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }
}

// klik luar modal = close
window.onclick = function(event) {
    const modal = document.getElementById('logoutModal');
    if (event.target == modal) {
        toggleLogoutModal(false);
    }
}
</script>