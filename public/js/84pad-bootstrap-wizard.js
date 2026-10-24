/* 84PAD private setup wizard; no network requests or credential persistence. */
(() => {
    'use strict';
    const form = document.querySelector('[data-wizard]');
    if (!form) return;
    const pages = Array.from(form.querySelectorAll('[data-step]'));
    const progress = Array.from(document.querySelectorAll('[data-indicator]'));
    const modeFields = Array.from(form.querySelectorAll('[data-mode-fields]'));
    const next = form.querySelector('[data-next]');
    const back = form.querySelector('[data-back]');
    const counter = document.querySelector('[data-step-count]');
    const hostReady = form.dataset.hostReady === 'yes';
    let current = 0;

    document.documentElement.classList.add('js');

    function updateMode() {
        const choice = form.querySelector('input[name="connection_mode"]:checked')?.value || 'manual';
        for (const group of modeFields) {
            const enabled = group.getAttribute('data-mode-fields') === choice;
            group.classList.toggle('visible', enabled);
            for (const field of group.querySelectorAll('input')) {
                field.disabled = !enabled;
                field.required = enabled;
            }
        }
        const password = form.querySelector('[name="db_password"]');
        if (password) password.minLength = choice === 'cpanel' ? 16 : 0;
    }

    function show(index) {
        current = Math.max(0, Math.min(index, pages.length - 1));
        pages.forEach((page, i) => {
            const active = i === current;
            page.classList.toggle('visible', active);
            page.setAttribute('aria-hidden', String(!active));
            // Hidden steps must not block submission or keyboard focus.
            for (const element of page.querySelectorAll('input,button,select')) {
                if (element.dataset.initialDisabled === 'true') continue;
                if (page.getAttribute('data-step') === '2') element.disabled = !active;
            }
        });
        updateMode();
        progress.forEach((item, i) => {
            const active = i === current;
            item.classList.toggle('active', active);
            item.classList.toggle('complete', i < current);
            if (active) item.setAttribute('aria-current', 'step');
            else item.removeAttribute('aria-current');
        });
        if (counter) counter.textContent = 'STEP ' + String(current + 1).padStart(2, '0') + ' OF ' + String(pages.length).padStart(2, '0');
        if (next) next.disabled = current === 0 && !hostReady;
        back.hidden = current === 0;
        next.hidden = current === pages.length - 1;
        const submit = form.querySelector('button[type="submit"]');
        if (submit) submit.hidden = current !== pages.length - 1;
        if (current > 0) pages[current].querySelector('h2')?.focus({preventScroll: true});
    }

    function validateCurrent() {
        const inputs = [...pages[current].querySelectorAll('input')].filter(el => !el.disabled);
        for (const field of inputs) {
            if (!field.checkValidity()) {
                field.reportValidity();
                field.focus();
                return false;
            }
        }
        return true;
    }

    form.querySelectorAll('input[name="connection_mode"]').forEach(input => {
        input.addEventListener('change', updateMode);
    });
    next?.addEventListener('click', () => {
        if (current === 0 && !hostReady) return;
        if (validateCurrent()) show(current + 1);
    });
    back?.addEventListener('click', () => show(current - 1));
    form.addEventListener('submit', event => {
        if (current !== pages.length - 1) {
            event.preventDefault();
            if ((current !== 0 || hostReady) && validateCurrent()) show(current + 1);
        } else if (!validateCurrent()) {
            event.preventDefault();
        } else {
            const submit = form.querySelector('button[type="submit"]');
            if (submit) {
                submit.disabled = true;
                submit.textContent = 'Preparing secure setup…';
                submit.setAttribute('aria-busy', 'true');
            }
        }
    });
    show(0);
})();