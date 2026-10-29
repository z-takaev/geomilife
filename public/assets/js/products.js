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
            this.quantity = Math.min(1000, this.quantity + 1);
        },

        async addToCart() {
            this.normalizeQuantity();
            if (this.selectedVariantId && (await this.$store.cart.add(this.selectedVariantId, this.quantity))) {
                this.quantity = 1;
            }
        },

        normalizeQuantity() {
            const quantity = Number.parseInt(this.quantity, 10);

            this.quantity = Number.isSafeInteger(quantity) ? Math.min(1000, Math.max(1, quantity)) : 1;
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

document.addEventListener('alpine:init', () => {
    Alpine.store('cart', {
        count: 0,
        busy: false,
        wire: null,
        async add(variantId, quantity) {
            if (!this.wire || this.busy) return false;
            this.busy = true;
            try {
                return await this.wire.add(variantId, quantity);
            } finally {
                this.busy = false;
            }
        },
    });

    Alpine.store('wishlist', {
        ids: [],
        ready: false,
        busy: false,
        wire: null,
        async perform(action, ...parameters) {
            if (!this.ready || this.busy) return;
            this.busy = true;
            try {
                await this.wire[action](...parameters);
            } finally {
                this.busy = false;
            }
        },
    });
});

window.wishlistBrowser = function (wire, authenticated, accountIds) {
    const storageKey = 'guest-wishlist';

    return {
        async init() {
            const store = this.$store.wishlist;
            if (store.wire === wire) return;
            store.wire = wire;
            store.ready = false;
            let guestIds = [];
            try {
                const saved = JSON.parse(sessionStorage.getItem(storageKey) ?? '[]');
                if (Array.isArray(saved)) {
                    guestIds = [...new Set(saved.filter((id) => Number.isSafeInteger(id) && id > 0))].slice(0, 1000);
                }
            } catch {
                guestIds = [];
            }
            store.ids = authenticated ? accountIds : guestIds;
            if (guestIds.length > 0) await wire.initialize(guestIds);
            store.ready = true;
        },
        update(ids) {
            this.$store.wishlist.ids = ids;
            if (!authenticated) sessionStorage.setItem(storageKey, JSON.stringify(ids));
        },
        clearGuest() {
            sessionStorage.removeItem(storageKey);
        },
    };
};

document.addEventListener('shown.bs.offcanvas', (event) => {
    if (!event.target.matches('#shoppingCart, #wishList')) return;

    const backdrop = event.target.parentElement?.querySelector(':scope > .offcanvas-backdrop');
    if (backdrop) document.body.append(backdrop);
});
