const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const vm = require('node:vm');

function browser(saved = null) {
    const storage = new Map(saved === null ? [] : [['guest-wishlist', saved]]);
    const stores = {};
    const shown = [];
    const elements = new Map([
        ['shoppingCart', { id: 'shoppingCart' }],
        ['wishList', { id: 'wishList' }],
    ]);
    const context = {
        window: { addEventListener() {}, location: { origin: 'https://shop.test' } },
        URL,
        document: {
            addEventListener(name, callback) { if (name === 'alpine:init') callback(); },
            getElementById(id) { return elements.get(id) ?? null; },
        },
        bootstrap: {
            Offcanvas: {
                getOrCreateInstance(element) {
                    return { show() { shown.push(element.id); } };
                },
            },
        },
        Alpine: { store(name, value) { stores[name] = value; } },
        sessionStorage: {
            getItem(key) { return storage.get(key) ?? null; },
            setItem(key, value) { storage.set(key, value); },
            removeItem(key) { storage.delete(key); },
        },
    };
    vm.runInNewContext(readFileSync('public/assets/js/alpine.js', 'utf8'), context);
    return {
        storage,
        stores,
        shown,
        window: context.window,
        component(wire, authenticated = false, ids = [], itemsLoaded = false) {
            const component = context.window.wishlistBrowser(wire, authenticated, ids, itemsLoaded);
            component.$store = stores;
            return component;
        },
        cartComponent(wire, quantities = {}, count = 0, itemsLoaded = false) {
            const component = context.window.shoppingCart(wire, quantities, count, itemsLoaded);
            component.$store = stores;
            return component;
        },
        productSelector(variants, initialVariantId = null) {
            const component = context.window.productVariantSelector(variants, initialVariantId);
            component.$store = stores;
            return component;
        },
    };
}

test('гость восстанавливает список из сессии браузера и сохраняет изменения', async () => {
    const app = browser('[4,7,4,"bad",-1]');
    let received;
    const component = app.component({ async initialize(ids) { received = ids; } });
    await component.init();
    assert.equal(JSON.stringify(received), '[4,7]');
    component.update([4, 8]);
    assert.equal(app.storage.get('guest-wishlist'), '[4,8]');
    component.update([]);
    assert.equal(app.storage.get('guest-wishlist'), '[]');
});

test('вход сохраняет гостевые товары до подтверждённого переноса', async () => {
    const app = browser('[4]');
    const component = app.component({ async initialize() {} }, true, [9]);
    await component.init();
    component.update([9, 10]);
    assert.equal(app.storage.get('guest-wishlist'), '[4]');
    component.clearGuest();
    assert.equal(app.storage.has('guest-wishlist'), false);
});

test('повреждённая сессия не ломает инициализацию', async () => {
    const app = browser('broken');
    const component = app.component({ async initialize(ids) { assert.equal(ids.length, 0); } });
    await component.init();
    assert.equal(app.stores.wishlist.ready, true);
});

test('повторное нажатие блокируется до завершения запроса', async () => {
    const app = browser();
    let finish;
    let calls = 0;
    const component = app.component({
        async initialize() {},
        toggle() { calls++; return new Promise(resolve => { finish = resolve; }); },
    });
    await component.init();
    const pending = app.stores.wishlist.perform('toggle', 4);
    await app.stores.wishlist.perform('toggle', 4);
    assert.equal(calls, 1);
    finish();
    await pending;
    assert.equal(app.stores.wishlist.busy, false);
});

test('пустое гостевое избранное не создаёт начальный запрос', async () => {
    const app = browser();
    let calls = 0;
    const component = app.component({ async initialize() { calls++; } });
    await component.init();
    assert.equal(calls, 0);
    assert.equal(app.stores.wishlist.ready, true);
});

test('аккаунт без гостевого списка использует серверные данные без запроса', async () => {
    const app = browser();
    let calls = 0;
    const component = app.component({ async initialize() { calls++; } }, true, [9]);
    await component.init();
    assert.equal(calls, 0);
    assert.deepEqual(app.stores.wishlist.ids, [9]);
});

test('повторная инициализация не сбрасывает сердце и не отправляет запрос', async () => {
    const app = browser('[4]');
    let calls = 0;
    const wire = { async initialize() { calls++; } };
    const component = app.component(wire);
    await component.init();
    component.update([4, 7]);
    const ids = app.stores.wishlist.ids;
    await component.init();
    await app.component(wire).init();
    assert.equal(calls, 1);
    assert.equal(app.stores.wishlist.ids, ids);
    assert.equal(app.stores.wishlist.ready, true);
});


test('сбой запроса освобождает блокировку избранного для повторной попытки', async () => {
    const app = browser();
    let calls = 0;
    const component = app.component({
        async toggle() { if (++calls === 1) throw new Error('network'); },
    });
    await component.init();
    await assert.rejects(app.stores.wishlist.perform('toggle', 4), /network/);
    assert.equal(app.stores.wishlist.busy, false);
    await app.stores.wishlist.perform('toggle', 4);
    assert.equal(calls, 2);
});

test('гостевой список ограничивается тысячей корректных идентификаторов', async () => {
    const app = browser(JSON.stringify(Array.from({ length: 1005 }, (_, index) => index + 1)));
    let received;
    await app.component({ async initialize(ids) { received = ids; } }).init();
    assert.equal(received.length, 1000);
    assert.equal(received[999], 1000);
});

test('сбой запроса корзины освобождает блокировку и допускает повтор', async () => {
    const app = browser();
    let calls = 0;
    app.stores.cart.wire = { async add() { if (++calls === 1) throw new Error('network'); return true; } };
    await assert.rejects(app.stores.cart.add(4, 2), /network/);
    assert.equal(app.stores.cart.busy, false);
    assert.equal(await app.stores.cart.add(4, 2), true);
    assert.equal(calls, 2);
});

test('корзина инициализирует количества и суммарный счётчик', () => {
    const app = browser();

    app.cartComponent({}, { 4: 2, 7: 3 }, 5).init();

    assert.equal(app.stores.cart.quantity(4), 2);
    assert.equal(app.stores.cart.quantity(7), 3);
    assert.equal(app.stores.cart.count, 5);
});

test('корзина открывается только после первой загрузки товаров', async () => {
    const app = browser();
    let loaded = false;
    app.stores.cart.wire = { async loadItems() { loaded = true; } };

    await app.stores.cart.open();

    assert.equal(loaded, true);
    assert.deepEqual(app.shown, ['shoppingCart']);
});

test('избранное открывается только после первой загрузки товаров', async () => {
    const app = browser();
    let loaded = false;
    await app.component({ async loadItems() { loaded = true; } }).init();

    await app.stores.wishlist.open();

    assert.equal(loaded, true);
    assert.deepEqual(app.shown, ['wishList']);
});


test('выбор фасовки проверяет идентификатор и сохраняет параметры ссылки', () => {
    const app = browser();
    const selector = app.window.productVariantSelector([{ id: 1 }, { id: 2 }], 999, '/products/oil?source=home');
    assert.equal(selector.selectedVariantId, 1);
    selector.selectVariant(999);
    assert.equal(selector.selectedVariantId, 1);
    selector.selectVariant(2);
    assert.equal(selector.selectedVariant.id, 2);
    assert.equal(selector.selectedProductUrl, 'https://shop.test/products/oil?source=home&variant=2');
});

test('кнопка добавляет одну единицу и заменяется актуальным количеством', async () => {
    const app = browser();
    const calls = [];
    const wire = {
        async add(...parameters) {
            calls.push(parameters);
            return { quantities: { 2: 1 }, count: 1 };
        },
    };
    app.cartComponent(wire).init();
    const selector = app.productSelector([{ id: 2 }], 2);

    assert.equal(selector.isInCart, false);
    await selector.addToCart();

    assert.equal(selector.isInCart, true);
    assert.equal(selector.quantity, 1);
    assert.deepEqual(calls, [[2, 1]]);
});

test('переключатель синхронизирует все экземпляры товара и удаляет последнюю единицу', async () => {
    const app = browser();
    const wire = {
        async changeQuantity(variantId, change) {
            const quantity = app.stores.cart.quantity(variantId) + change;
            const quantities = quantity > 0 ? { [variantId]: quantity } : {};

            return { quantities, count: Math.max(0, quantity) };
        },
    };
    app.cartComponent(wire, { 2: 1 }, 1).init();
    const first = app.productSelector([{ id: 2 }], 2);
    const second = app.productSelector([{ id: 2 }], 2);

    await first.increaseQuantity();
    assert.equal(second.quantity, 2);

    await second.decreaseQuantity();
    await second.decreaseQuantity();
    assert.equal(first.isInCart, false);
    assert.equal(app.stores.cart.count, 0);
});
