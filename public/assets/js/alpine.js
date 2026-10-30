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

window.shoppingCart = function (wire, count) {
    return {
        init() {
            this.$store.cart.wire = wire;
            this.$store.cart.count = count;
        },
    };
};

window.telegramConnect = function () {
    return {
        connecting: false,
        async connect() {
            const telegramWindow = window.open('about:blank', '_blank');
            if (!telegramWindow) return;
            telegramWindow.opener = null;
            this.connecting = true;
            try {
                const url = await this.$wire.connectTelegram();
                if (url) {
                    telegramWindow.location.replace(url);
                } else {
                    telegramWindow.close();
                }
            } catch (error) {
                telegramWindow.close();
            } finally {
                this.connecting = false;
            }
        },
    };
};

window.notifications = function () {
    return {
        message: '',
        type: 'success',
        show(detail) {
            if (typeof detail.message !== 'string' || !detail.message.trim()) return;
            this.message = detail.message;
            this.type = ['success', 'error', 'warning'].includes(detail.type) ? detail.type : 'success';
            this.$nextTick(() => bootstrap.Toast.getOrCreateInstance(this.$refs.toast).show());
        },
    };
};

window.citySearch = function () {
    return {
        suggestionsOpen: false,
        clear() {
            this.$wire.$set('query', '');
            this.$refs.citySearch.focus();
            this.suggestionsOpen = false;
        },
    };
};

window.favoriteButton = function (productId) {
    return {
        get favorite() {
            return this.$store.wishlist.ids.includes(productId);
        },
        toggle() {
            return this.$store.wishlist.perform('toggle', productId);
        },
    };
};
