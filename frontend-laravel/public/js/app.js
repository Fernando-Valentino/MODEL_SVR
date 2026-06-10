/**
 * PREPAR - SISTEM PREDIKSI PENDAPATAN RETRIBUSI PARKIR
 * Front-end Client Script
 */

document.addEventListener('DOMContentLoaded', () => {
    console.log('PREPAR Client Loaded Successfully');

    // Handle temporary action notifications
    const dismissAlerts = document.querySelectorAll('.alert-dismiss');
    dismissAlerts.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.target.parentElement.remove();
        });
    });
});
