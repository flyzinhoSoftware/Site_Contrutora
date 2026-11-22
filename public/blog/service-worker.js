/**
 * public/blog/service-worker.js
 * Interações da página explicadas em linguagem simples.
 * Assinado por Flyzinho — toque próprio, feito com cuidado.
 */

self.addEventListener('push', event => {
    if (!event.data) return;
    let data;
    try {
        data = event.data.json();
    } catch (error) {
        data = {title: 'Novo artigo no blog', body: event.data.text()};
    }

    event.waitUntil(self.registration.showNotification(data.title || 'Novo artigo no blog', {
        body: data.body || 'Foi publicado um novo conteúdo.',
        data: {url: data.url || self.registration.scope}
    }));
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const target = new URL(event.notification.data.url, self.location.origin);
    if (target.origin !== self.location.origin) return;

    event.waitUntil(clients.matchAll({type: 'window', includeUncontrolled: true}).then(windows => {
        for (const windowClient of windows) {
            if (windowClient.url.startsWith(self.location.origin) && 'focus' in windowClient) {
                return windowClient.navigate(target.href).then(() => windowClient.focus());
            }
        }
        return clients.openWindow(target.href);
    }));
});
