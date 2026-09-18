// Use the same catalogue and discounted prices as the ordering page.
(() => {
    const container = document.getElementById('home-products');
    const node = (tag, className, text) => {
        const element = document.createElement(tag);
        element.className = className;
        if (text !== undefined) element.textContent = text;
        return element;
    };
    async function load() {
        container.textContent = 'Loading café favourites…';
        try {
            const response = await GS.fetch('get_products.php');
            if (!response.ok) throw new Error('Menu unavailable');
            const products = await response.json();
            if (!Array.isArray(products)) throw new Error('Invalid menu');
            container.replaceChildren();
            products.sort((a,b) => Number(b.featured) - Number(a.featured)).slice(0,3).forEach(product => {
                const card = node('article','menu-item');
                const picture = node('div','menu-image');
                const img = node('img','');
                const fallback = 'assets/images/dish-placeholder.svg';
                img.src = fallback;
                try {
                    const url = new URL(product.image, location.href);
                    if (product.image && ['http:','https:'].includes(url.protocol)) img.src = url.href;
                } catch { /* Keep the dish visible when its photo is unavailable. */ }
                img.addEventListener('error', () => { img.src = fallback; }, {once:true});
                img.alt = product.name; img.loading = 'lazy';
                picture.append(img);
                const content = node('div','menu-content');
                const heading = node('div','menu-item-header');
                heading.append(node('h3','',product.name));
                const price = Math.round(Number(product.price) * (product.discounted ? 1 - Number(product.discountPercent) / 100 : 1) * 100) / 100;
                const footer = node('div','menu-item-footer');
                const link = node('a','dish-link','View dish →');
                link.href = 'menu.html?product=' + encodeURIComponent(product.id);
                link.setAttribute('aria-label','View ' + product.name);
                footer.append(node('strong','dish-price','RM' + price.toFixed(2)),link);
                content.append(heading,node('p','',product.description),footer);
                card.append(picture,content); container.append(card);
            });
            if (!products.length) container.textContent = 'Our menu is being refreshed. Please check back soon.';
        } catch {
            container.textContent = 'We couldn’t load today’s dishes. ';
            const retry = node('button','dish-link','Try again');
            retry.type = 'button'; retry.addEventListener('click',load); container.append(retry);
        }
    }
    load();
})();
