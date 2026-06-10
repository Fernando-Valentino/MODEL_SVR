/**
 * PREPAR - SISTEM PREDIKSI PENDAPATAN RETRIBUSI PARKIR
 * Front-end Client Script
 */

// Global Reusable SweetAlert2 Toast Helper
window.showToast = function(icon, title) {
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 is not loaded!');
        return;
    }
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    return Toast.fire({
        icon: icon,
        title: title
    });
};

document.addEventListener('DOMContentLoaded', () => {
    console.log('PREPAR Client Loaded Successfully');

    // Handle temporary action notifications
    const dismissAlerts = document.querySelectorAll('.alert-dismiss');
    dismissAlerts.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.target.parentElement.remove();
        });
    });

    // Realtime Clock Updater
    const clockDate = document.getElementById('clock-date');
    const clockTime = document.getElementById('clock-time');

    if (clockDate && clockTime) {
        const updateClock = () => {
            const now = new Date();
            
            // Format Date: "Hari, Tanggal Bulan Tahun" in Indonesian (id-ID)
            const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
            const formattedDate = now.toLocaleDateString('id-ID', options);
            
            // Format Time: HH:mm:ss
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const formattedTime = `${hours}:${minutes}:${seconds}`;

            clockDate.textContent = formattedDate;
            clockTime.textContent = formattedTime;
        };

        // Initialize immediately and update every second
        updateClock();
        setInterval(updateClock, 1000);
    }
});
