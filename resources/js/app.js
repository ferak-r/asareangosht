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

    const div = (a, b) => Math.floor(a / b);
    const gregorianToJalali = (gy, gm, gd) => {
        const gDays = [0,31,59,90,120,151,181,212,243,273,304,334];
        let gy2 = gy > 1600 ? gy - 1600 : gy - 621;
        let jy = gy > 1600 ? 979 : 0;
        if (gm > 2) gy2++;
        let days = 365 * gy2 + div(gy2 + 3, 4) - div(gy2 + 99, 100) + div(gy2 + 399, 400) - 80 + gd + gDays[gm - 1];
        jy += 33 * div(days, 12053); days %= 12053;
        jy += 4 * div(days, 1461); days %= 1461;
        if (days > 365) { jy += div(days - 1, 365); days = (days - 1) % 365; }
        const jm = days < 186 ? 1 + div(days, 31) : 7 + div(days - 186, 30);
        const jd = 1 + (days < 186 ? days % 31 : (days - 186) % 30);
        return [jy, jm, jd];
    };
    const jalaliToGregorian = (jy, jm, jd) => {
        let jYear = jy - 979;
        let days = 365 * jYear + div(jYear, 33) * 8 + div((jYear % 33) + 3, 4) + 78 + jd + (jm < 7 ? (jm - 1) * 31 : (jm - 7) * 30 + 186);
        let gy = 1600 + 400 * div(days, 146097); days %= 146097;
        let leap = true;
        if (days >= 36525) { days--; gy += 100 * div(days, 36524); days %= 36524; if (days >= 365) days++; else leap = false; }
        gy += 4 * div(days, 1461); days %= 1461;
        if (days >= 366) { leap = false; days--; gy += div(days, 365); days %= 365; }
        const lengths = [31, leap ? 29 : 28,31,30,31,30,31,31,30,31,30,31];
        let gm = 0; while (days >= lengths[gm]) days -= lengths[gm++];
        return [gy, gm + 1, days + 1];
    };
    const pad = value => String(value).padStart(2, '0');
    const formatJalali = iso => {
        const match = String(iso || '').match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/);
        if (!match) return '';
        const [jy, jm, jd] = gregorianToJalali(+match[1], +match[2], +match[3]);
        return `${jy}/${pad(jm)}/${pad(jd)}${match[4] ? ` ${match[4]}:${match[5]}${match[6] ? `:${match[6]}` : ''}` : ''}`;
    };
    document.querySelectorAll('[data-jalali-value]').forEach(element => {
        const formatted = formatJalali(element.dataset.jalaliValue);
        if (formatted) element.textContent = formatted;
    });
    document.querySelectorAll('input.jalali-date').forEach(input => {
        const name = input.name;
        const original = input.value;
        const hidden = document.createElement('input');
        hidden.type = 'hidden'; hidden.name = name; input.name = '';
        input.after(hidden);
        const setFromIso = iso => {
            if (!iso) { hidden.value = ''; return; }
            const match = iso.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!match) return;
            const [jy, jm, jd] = gregorianToJalali(+match[1], +match[2], +match[3]);
            input.value = `${jy}/${pad(jm)}/${pad(jd)}`; hidden.value = iso;
        };
        if (original) setFromIso(original);
        input.addEventListener('change', () => {
            const digits = input.value.replace(/[۰-۹]/g, char => '۰۱۲۳۴۵۶۷۸۹'.indexOf(char)).replace(/-/g, '/');
            const match = digits.match(/^(1[34]\d\d)\/(\d{1,2})\/(\d{1,2})$/);
            if (!match || +match[2] < 1 || +match[2] > 12 || +match[3] < 1 || +match[3] > 31) { hidden.value = ''; input.setCustomValidity('تاریخ را به شکل ۱۴۰۵/۰۵/۲۲ وارد کنید.'); return; }
            const [gy, gm, gd] = jalaliToGregorian(+match[1], +match[2], +match[3]);
            hidden.value = `${gy}-${pad(gm)}-${pad(gd)}`; input.value = `${match[1]}/${pad(match[2])}/${pad(match[3])}`; input.setCustomValidity('');
        });
    });

    const filterOptions = (select, selector, visibleValue, projectId = null) => {
        [...select.options].forEach(option => {
            if (!option.value) return;
            option.hidden = option.dataset[selector] !== visibleValue || (projectId && option.dataset.project !== projectId);
        });
        if (select.selectedOptions[0]?.hidden) select.value = '';
    };
    const projectSelect = document.querySelector('[data-project-select]');
    const contractSelect = document.querySelector('[data-contract-select]');
    projectSelect?.addEventListener('change', () => filterOptions(contractSelect, 'project', projectSelect.value));
    if (projectSelect && contractSelect) filterOptions(contractSelect, 'project', projectSelect.value);
    const allocationType = document.querySelector('[data-allocation-type]');
    const allocationTarget = document.querySelector('[data-allocation-target]');
    const filterAllocationTargets = () => filterOptions(allocationTarget, 'type', allocationType.value, projectSelect?.value || null);
    allocationType?.addEventListener('change', filterAllocationTargets);
    projectSelect?.addEventListener('change', filterAllocationTargets);
    if (allocationType && allocationTarget) filterAllocationTargets();
});
