document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('.dashboard');
    if (!root || root.dataset.preview !== 'true') return;

    const withPreview = (href) => {
        const url = new URL(href, window.location.href);
        if (url.origin === window.location.origin && url.pathname.includes('/staff/')) {
            url.searchParams.set('preview', '1');
        }
        return url;
    };

    document.querySelectorAll('a[href]').forEach((link) => {
        if (!link.href || link.href.startsWith('javascript:')) return;
        link.href = withPreview(link.href).toString();
    });

    document.querySelectorAll('form').forEach((form) => {
        const method = (form.method || 'get').toLowerCase();
        if (method === 'get') {
            let field = form.querySelector('input[name="preview"]');
            if (!field) {
                field = document.createElement('input');
                field.type = 'hidden';
                field.name = 'preview';
                form.append(field);
            }
            field.value = '1';
        } else if (!form.querySelector('input[name="preview"]')) {
            const field = document.createElement('input');
            field.type = 'hidden';
            field.name = 'preview';
            field.value = '1';
            form.append(field);
        }
    });

    document.addEventListener('submit', (event) => {
        if ((event.target.method || 'get').toLowerCase() === 'post') {
            event.preventDefault();
            window.alert('Demo preview is display-only. No changes were saved.');
        }
    }, true);
});
