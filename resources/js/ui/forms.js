function ensureErrorNode(field) {
    const wrapper = field.closest('label') || field.parentElement;
    if (!wrapper) {
        return null;
    }

    let node = wrapper.querySelector('[data-field-error]');
    if (!node) {
        node = document.createElement('span');
        node.className = 'wp-helper wp-helper-error hidden';
        node.setAttribute('data-field-error', '');
        wrapper.appendChild(node);
    }

    if (!field.id) {
        field.id = `field-${Math.random().toString(36).slice(2, 10)}`;
    }

    node.id = `${field.id}-error`;
    return node;
}

export function clearFieldError(field) {
    if (!field) {
        return;
    }

    field.classList.remove('wp-input-error');
    field.setAttribute('aria-invalid', 'false');
    const errorNode = field.closest('label')?.querySelector('[data-field-error]');
    if (errorNode) {
        errorNode.textContent = '';
        errorNode.classList.add('hidden');
    }
}

export function setFieldError(field, message) {
    if (!field) {
        return;
    }

    const errorNode = ensureErrorNode(field);
    field.classList.add('wp-input-error');
    field.setAttribute('aria-invalid', 'true');

    if (errorNode) {
        errorNode.textContent = message;
        errorNode.classList.remove('hidden');
        field.setAttribute('aria-describedby', errorNode.id);
    }
}

export function clearFormErrors(fields = []) {
    fields.forEach((field) => clearFieldError(field));
}
