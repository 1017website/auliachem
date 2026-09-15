function decimalInputValue(input) {
    const value = input.value.trim();
    const localized = input.matches('.idr-input, .doc-qty, .item-qty, .item-buy, .item-sell');
    return localized || value.includes(',') ? value.replace(/\./g, '').replace(',', '.') : value;
}

function validateDecimalInput(input) {
    const value = decimalInputValue(input);
    const minimum = input.matches('.doc-qty, .item-qty') ? 0.001 : Number(input.getAttribute('min') || 0);
    const maximum = input.hasAttribute('max') ? Number(input.getAttribute('max')) : Infinity;
    let message = '';
    if (value !== '' && !/^\d+(?:\.\d{1,3})?$/.test(value)) {
        message = 'Gunakan angka dengan maksimal 3 digit desimal.';
    } else if (value !== '' && (Number(value) < minimum || Number(value) > maximum)) {
        message = `Nilai minimal ${minimum}` + (Number.isFinite(maximum) ? ` dan maksimal ${maximum}` : '') + '.';
    }
    input.setCustomValidity(message);
    return message === '';
}

document.addEventListener('input', function (event) {
    if (event.target.matches('[data-decimal-input], .idr-input, .doc-qty, .item-qty, .item-buy, .item-sell')) {
        validateDecimalInput(event.target);
    }
});
