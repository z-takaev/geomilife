window.productVariantSelector = function (variants, initialVariantId = null, productUrl = null) {
    const hasInitialVariant = variants.some((variant) => variant.id === initialVariantId);

    return {
        variants,
        selectedVariantId: hasInitialVariant ? initialVariantId : (variants[0]?.id ?? null),
        quantity: 1,

        get selectedVariant() {
            return this.variants.find((variant) => variant.id === this.selectedVariantId) ?? null;
        },

        get selectedProductUrl() {
            if (!productUrl) return null;
            const url = new URL(productUrl, window.location.origin);
            url.searchParams.set('variant', this.selectedVariantId);
            return url.href;
        },

        selectVariant(variantId) {
            if (this.variants.some((variant) => variant.id === variantId)) {
                this.selectedVariantId = variantId;
            }
        },

        decreaseQuantity() {
            this.normalizeQuantity();
            this.quantity = Math.max(1, this.quantity - 1);
        },

        increaseQuantity() {
            this.normalizeQuantity();
            this.quantity = Math.min(Number.MAX_SAFE_INTEGER, this.quantity + 1);
        },

        normalizeQuantity() {
            const quantity = Number.parseInt(this.quantity, 10);

            this.quantity = Number.isSafeInteger(quantity) ? Math.max(1, quantity) : 1;
        },
    };
};

window.addEventListener('catalog-filters-applied', () => {
    const filters = document.getElementById('catalogFilters');
    if (filters) bootstrap.Offcanvas.getInstance(filters)?.hide();
});

document.getElementById('canvasSearch')?.addEventListener('shown.bs.offcanvas', (event) => {
    event.target.querySelector('input[type="search"]')?.focus();
});
