/* Small, dependency-free rules shared by menu rendering and cart recovery. */
'use strict';
const GreenSproutCatalog = Object.freeze({
  matches(product, query) {
    const text = [product.name, product.description, product.category].join(' ').toLocaleLowerCase();
    return String(query).trim().toLocaleLowerCase().split(/\s+/).every(word => text.includes(word));
  },
  restoreCart(draft) {
    if (!draft || !Array.isArray(draft.cart)) return [];
    const items = new Map();
    for (const item of draft.cart.slice(0, 100)) {
      if (!item || !Number.isSafeInteger(item.id) || item.id < 1 ||
          typeof item.name !== 'string' || typeof item.price !== 'number' ||
          !Number.isFinite(item.price) || item.price < 0 ||
          !Number.isInteger(item.quantity) || item.quantity < 1) continue;
      const previous = items.get(item.id);
      items.set(item.id, {
        id: item.id, name: item.name.slice(0, 255), price: item.price,
        image: typeof item.image === 'string' ? item.image : '',
        quantity: Math.min(20, item.quantity + (previous?.quantity || 0)),
        specialRequest: typeof item.specialRequest === 'string' ? item.specialRequest.slice(0, 500) : ''
      });
    }
    return [...items.values()];
  }
});
if (typeof module !== 'undefined') module.exports = GreenSproutCatalog;
