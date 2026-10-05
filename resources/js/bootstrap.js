// Browser environment configurations
window.formatCurrency = function (amount) {
    const num = parseFloat(amount || 0);
    return '৳ ' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
