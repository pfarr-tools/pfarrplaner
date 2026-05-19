export function submitReportForm(action, data = {}) {
    const form = document.createElement('form');
    form.method = 'post';
    form.action = action;
    form.style.display = 'none';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        appendInput(form, '_token', csrfToken);
    }

    Object.entries(data).forEach(([key, value]) => {
        appendValue(form, key, value);
    });

    document.body.appendChild(form);
    form.submit();
    form.remove();
}

function appendValue(form, key, value) {
    if (value === undefined || value === null) return;

    if (Array.isArray(value)) {
        value.forEach(item => appendValue(form, `${key}[]`, item));
        return;
    }

    if (isMoment(value) || value instanceof Date) {
        appendInput(form, key, normalizeValue(value));
        return;
    }

    if (typeof value === 'object') {
        Object.entries(value).forEach(([nestedKey, nestedValue]) => {
            appendValue(form, `${key}[${nestedKey}]`, nestedValue);
        });
        return;
    }

    appendInput(form, key, normalizeValue(value));
}

function appendInput(form, name, value) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    form.appendChild(input);
}

function normalizeValue(value) {
    if (typeof value === 'boolean') return value ? '1' : '0';
    if (typeof value === 'number') return `${value}`;
    if (isMoment(value)) return value.toISOString();
    if (value instanceof Date) return value.toISOString();
    return value;
}

function isMoment(value) {
    return Boolean(window.moment && window.moment.isMoment && window.moment.isMoment(value));
}
