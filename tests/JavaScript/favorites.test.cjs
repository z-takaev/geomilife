const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const vm = require('node:vm');

function browser(saved = null) {
    const storage = new Map(saved === null ? [] : [['guest-wishlist', saved]]);
    const stores = {};
    const context = {
        window: { addEventListener() {} },
        document: {
            addEventListener(name, callback) { if (name === 'alpine:init') callback(); },
            getElementById() { return null; },
        },
        Alpine: { store(name, value) { stores[name] = value; } },
        sessionStorage: {
            getItem(key) { return storage.get(key) ?? null; },
            setItem(key, value) { storage.set(key, value); },
            removeItem(key) { storage.delete(key); },
        },
    };
    vm.runInNewContext(readFileSync('public/assets/js/alpine.js', 'utf8'), context);
    return { storage, stores, component(wire, authenticated = false, ids = []) {
        const component = context.window.wishlistBrowser(wire, authenticated, ids);
        component.$store = stores;
        return component;
    } };
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
