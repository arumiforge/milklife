document.addEventListener('DOMContentLoaded', function () {
    const $currentYearText =  document.getElementById('currentYear');
    if ($currentYearText) {
        $currentYearText.textContent = new Date().getFullYear();
    }
});
