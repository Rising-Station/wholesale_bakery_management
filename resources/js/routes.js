/**
 * Simple client-side route resolver for named application routes.
 */
const routePatterns = {
    // Auth & Dashboard
    'dashboard': '/dashboard',
    'login': '/login',
    'logout': '/logout',
    'profile': '/profile',
    'profile.edit': '/profile',
    'profile.update': '/profile',
    'profile.password': '/profile/password',

    // Products & Categories
    'products.index': '/products',
    'products.store': '/products',
    'products.update': (id) => `/products/${id}`,
    'products.destroy': (id) => `/products/${id}`,
    'products.categories.store': '/products/categories',
    'products.categories.update': (id) => `/products/categories/${id}`,
    'products.categories.destroy': (id) => `/products/categories/${id}`,

    // Stock & Inventory
    'stock.index': '/stock',
    'stock.movements': '/stock/movements',
    'stock-adjustments.index': '/stock-adjustments',
    'stock-adjustments.store': '/stock-adjustments',

    // Suppliers & Purchases
    'suppliers.index': '/suppliers',
    'suppliers.store': '/suppliers',
    'suppliers.show': (id) => `/suppliers/${id}`,
    'suppliers.update': (id) => `/suppliers/${id}`,
    'suppliers.destroy': (id) => `/suppliers/${id}`,
    'suppliers.payments.store': '/suppliers/payments',
    'purchases.index': '/purchases',
    'purchases.store': '/purchases',

    // Roads & Retailers
    'roads.index': '/roads',
    'roads.store': '/roads',
    'roads.update': (id) => `/roads/${id}`,
    'roads.destroy': (id) => `/roads/${id}`,
    'retailers.index': '/retailers',
    'retailers.store': '/retailers',
    'retailers.show': (id) => `/retailers/${id}`,
    'retailers.update': (id) => `/retailers/${id}`,
    'retailers.destroy': (id) => `/retailers/${id}`,
    'retailers.collect-due': (id) => `/retailers/${id}/collect-due`,

    // Trips & Vehicles
    'trips.index': '/trips',
    'trips.store': '/trips',
    'trips.show': (id) => `/trips/${id}`,
    'trips.dispatch': (id) => `/trips/${id}/dispatch`,
    'trips.reconcile': (id) => `/trips/${id}/reconcile`,
    'trips.reconcile.store': (id) => `/trips/${id}/reconcile`,

    // Memos & Billing
    'memos.index': '/memos',
    'memos.store': '/memos',
    'memos.show': (id) => `/memos/${id}`,
    'memos.collect-due': '/memos/collect-due',

    // Damage & Returns
    'damage-returns.index': '/damage-returns',
    'damage-returns.store': '/damage-returns',
    'damage-returns.claim': '/damage-returns/claim',

    // Reports
    'reports.road-settlement': '/reports/road-settlement',

    // Expenses
    'expenses.index': '/expenses',
    'expenses.store': '/expenses',
    'expenses.destroy': (id) => `/expenses/${id}`,

    // Users & Roles
    'users.index': '/users',
    'users.store': '/users',
    'users.update': (id) => `/users/${id}`,
    'users.destroy': (id) => `/users/${id}`,
    'users.toggle-status': (id) => `/users/${id}/toggle-status`,
    'roles.index': '/roles',
    'roles.store': '/roles',
    'roles.update': (id) => `/roles/${id}`,
    'roles.destroy': (id) => `/roles/${id}`,

    // Settings & Backup
    'settings.index': '/settings',
    'settings.update': '/settings',
    'backup.index': '/backup',
    'backup.store': '/backup',
    'backup.destroy': (id) => `/backup/${id}`,
    'backup.download': (id) => `/backup/${id}/download`,
};

export function route(name, params = null) {
    if (!name) return '/';

    // If already a URL or path, return as is
    if (typeof name === 'string' && (name.startsWith('/') || name.startsWith('http://') || name.startsWith('https://'))) {
        return name;
    }

    const handler = routePatterns[name];
    if (typeof handler === 'function') {
        const id = typeof params === 'object' && params !== null ? (params.id || params) : params;
        return handler(id);
    }

    if (typeof handler === 'string') {
        if (typeof params === 'object' && params !== null) {
            const query = new URLSearchParams(params).toString();
            return query ? `${handler}?${query}` : handler;
        }
        return handler;
    }

    // Fallback: replace dot notation with slashes
    return `/${name.replace(/\./g, '/')}`;
}
