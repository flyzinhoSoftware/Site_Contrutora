/**
 * public/assets/js/main.js
 * Interações da página explicadas em linguagem simples.
 * Assinado por Flyzinho — toque próprio, feito com cuidado.
 */

// Menu principal em dispositivos pequenos.
const menuToggle = document.getElementById('menuToggle');
const nav = document.getElementById('mainNav');

menuToggle?.addEventListener('click', () => {
    nav.classList.toggle('open');
});

nav?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
        nav.classList.remove('open');
    });
});

// Widget do WhatsApp e as mensagens rápidas.
const waButton = document.getElementById('waButton');
const waPop = document.getElementById('waPop');

waButton?.addEventListener('click', () => {
    waPop.classList.toggle('show');
});

waPop?.querySelectorAll('[data-msg]').forEach((link) => {
    link.addEventListener('click', (event) => {
        event.preventDefault();
        const msg = encodeURIComponent(link.dataset.msg);

        window.open(
            `https://wa.me/${window.WA_NUMBER}?text=${msg}`,
            '_blank',
            'noopener'
        );
    });
});

document.addEventListener('click', (event) => {
    if (waPop && !event.target.closest('.wa-widget')) {
        waPop.classList.remove('show');
    }
});

// Galeria ampliada das obras em andamento.
const galleryLightbox = document.getElementById('galleryLightbox');

if (galleryLightbox) {
    const galleryImage = document.getElementById('galleryLightboxImage');
    const galleryProject = document.getElementById('galleryLightboxProject');
    const galleryCaption = document.getElementById('galleryLightboxCaption');
    const galleryCount = document.getElementById('galleryLightboxCount');
    const galleryPrevious = document.getElementById('galleryPrevious');
    const galleryNext = document.getElementById('galleryNext');
    const galleryClose = galleryLightbox.querySelector('.gallery-lightbox-close');
    let galleryItems = [];
    let galleryIndex = 0;
    let galleryOpener = null;

    function showGalleryImage() {
        const item = galleryItems[galleryIndex];

        if (!item) {
            return;
        }

        galleryImage.src = item.href;
        galleryImage.alt = item.dataset.caption || '';
        galleryProject.textContent = item.dataset.project || '';
        galleryCaption.textContent = item.dataset.caption || '';
        galleryCount.textContent = `${galleryIndex + 1} / ${galleryItems.length}`;
        galleryPrevious.disabled = galleryItems.length < 2;
        galleryNext.disabled = galleryItems.length < 2;
    }

    function closeGallery() {
        if (galleryLightbox.hidden) {
            return;
        }

        galleryLightbox.hidden = true;
        galleryLightbox.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('gallery-open');

        if (galleryOpener && galleryOpener.isConnected) {
            galleryOpener.focus();
        }

        galleryOpener = null;
    }

    function moveGallery(step) {
        if (!galleryItems.length) {
            return;
        }

        galleryIndex = (galleryIndex + step + galleryItems.length) % galleryItems.length;
        showGalleryImage();
    }

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-gallery-item]');

        if (trigger) {
            event.preventDefault();
            const grid = trigger.closest('.ongoing-photo-grid');

            if (!grid) {
                return;
            }

            galleryItems = Array.from(grid.querySelectorAll('[data-gallery-item]'));
            galleryIndex = galleryItems.indexOf(trigger);
            galleryOpener = trigger;
            showGalleryImage();
            galleryLightbox.hidden = false;
            galleryLightbox.setAttribute('aria-hidden', 'false');
            document.body.classList.add('gallery-open');
            galleryClose.focus();
            return;
        }

        if (
            event.target === galleryLightbox ||
            event.target.closest('[data-gallery-close]')
        ) {
            closeGallery();
        }
    });

    galleryPrevious.addEventListener('click', () => moveGallery(-1));
    galleryNext.addEventListener('click', () => moveGallery(1));

    document.addEventListener('keydown', (event) => {
        if (galleryLightbox.hidden) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            closeGallery();
        } else if (event.key === 'ArrowLeft') {
            event.preventDefault();
            moveGallery(-1);
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            moveGallery(1);
        } else if (event.key === 'Tab') {
            const focusable = Array.from(
                galleryLightbox.querySelectorAll('button:not([disabled])')
            );
            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });
}

// Carrossel dos parceiros.
const partnersCarousel = document.getElementById('partnersCarousel');

if (partnersCarousel) {
    const track = partnersCarousel.querySelector('.partners-track');
    const cards = Array.from(track.querySelectorAll('.partner-card'));
    const previous = document.getElementById('partnersPrevious');
    const next = document.getElementById('partnersNext');
    let partnerIndex = 0;

    function visiblePartnerCount() {
        if (!cards.length) {
            return 1;
        }

        const cardWidth = cards[0].getBoundingClientRect().width;

        return Math.max(
            1,
            Math.floor((partnersCarousel.clientWidth + 16) / (cardWidth + 16))
        );
    }

    function updatePartners() {
        if (!cards.length) {
            return;
        }

        const cardWidth = cards[0].getBoundingClientRect().width;
        const gap = parseFloat(getComputedStyle(track).gap) || 16;
        const maxIndex = Math.max(0, cards.length - visiblePartnerCount());
        partnerIndex = Math.min(partnerIndex, maxIndex);
        track.style.transform = `translateX(-${partnerIndex * (cardWidth + gap)}px)`;
        previous.disabled = partnerIndex === 0;
        next.disabled = partnerIndex === maxIndex;
    }

    previous?.addEventListener('click', () => {
        partnerIndex = Math.max(0, partnerIndex - 1);
        updatePartners();
    });

    next?.addEventListener('click', () => {
        partnerIndex++;
        updatePartners();
    });

    window.addEventListener('resize', updatePartners);
    updatePartners();
}
