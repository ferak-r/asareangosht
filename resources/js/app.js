document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', () => {
        const shell = document.querySelector('#app-shell');
        const sidebar = document.querySelector('#sidebar');
        if (window.matchMedia('(max-width: 900px)').matches) {
            sidebar?.classList.toggle('is-open');
        } else {
            shell?.classList.toggle('sidebar-collapsed');
        }
    });

    const jalaliDate = document.querySelector('[data-jalali-date]');
    if (jalaliDate) {
        jalaliDate.textContent = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
        }).format(new Date());
    }
});
