const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const context = vm.createContext({document: {addEventListener() {}}});
vm.runInContext(fs.readFileSync('public/js/decimal-inputs.js', 'utf8'), context);

function input(value, className = '', attributes = {}) {
    return {value, message: '',
        matches: selector => selector.split(', ').includes('.' + className),
        getAttribute: name => attributes[name] ?? null,
        hasAttribute: name => name in attributes,
        setCustomValidity(message) { this.message = message; }};
}

test('comma and canonical decimals retain all digits', () => {
    for (const [value, expected] of [['14,56', '14.56'], ['14.567', '14.567'], ['1.000,567', '1000.567'], ['0,001', '0.001']]) {
        const field = input(value);
        assert.equal(context.decimalInputValue(field), expected);
        assert.equal(context.validateDecimalInput(field), true);
    }
});

test('excess precision is rejected without modifying the value', () => {
    for (const className of ['', 'idr-input', 'doc-qty', 'item-qty', 'item-buy', 'item-sell']) {
        const field = input('14,5678', className);
        assert.equal(context.validateDecimalInput(field), false);
        assert.equal(field.value, '14,5678');
        assert.match(field.message, /3 digit/);
        field.value = '14,567';
        assert.equal(context.validateDecimalInput(field), true);
        assert.equal(field.message, '');
    }
});

test('rating, percentage, quantity and zero retain their limits', () => {
    assert.equal(context.validateDecimalInput(input('5,001', '', {min: '0', max: '5'})), false);
    assert.equal(context.validateDecimalInput(input('100,001', '', {max: '100'})), false);
    assert.equal(context.validateDecimalInput(input('0', 'item-qty')), false);
    assert.equal(context.validateDecimalInput(input('0', 'idr-input')), true);
    assert.equal(context.validateDecimalInput(input('-1')), false);
});
