(function () {
    'use strict';

    const button = document.querySelector('[data-print-document]');
    if (!button) return;

    button.addEventListener('click', async function () {
        button.disabled = true;
        button.textContent = 'Menyiapkan dokumen...';

        try {
            const images = Array.from(document.images).filter((image) => !image.complete);
            await Promise.all(images.map((image) => new Promise((resolve) => {
                image.addEventListener('load', resolve, { once: true });
                image.addEventListener('error', resolve, { once: true });
            })));

            window.focus();
            window.print();
        } finally {
            button.disabled = false;
            button.textContent = 'Cetak / Simpan PDF';
        }
    });
}());
