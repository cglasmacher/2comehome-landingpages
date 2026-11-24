/**
 * Letter photos are displayed small in an A4 PDF. Shrink before FormData
 * submission so a four-photo upload stays well below PHP's usual post_max_size.
 * The original file on the user's device is not changed.
 */
const PHOTO_MAX_BYTES = 320 * 1024;
const SOURCE_MAX_BYTES = 8 * 1024 * 1024;

const jpegBlob = (canvas, quality) => new Promise((resolve, reject) => {
    canvas.toBlob((blob) => {
        if (!blob) {
            reject(new Error('Das Foto konnte nicht komprimiert werden.'));
            return;
        }
        resolve(blob);
    }, 'image/jpeg', quality);
});

async function decodePhoto(file) {
    if (typeof createImageBitmap === 'function') {
        const bitmap = await createImageBitmap(file);
        return {
            image: bitmap,
            width: bitmap.width,
            height: bitmap.height,
            dispose: () => bitmap.close(),
        };
    }

    const objectUrl = URL.createObjectURL(file);
    const image = new Image();

    try {
        await new Promise((resolve, reject) => {
            image.onload = resolve;
            image.onerror = () => reject(new Error('Das Foto konnte nicht gelesen werden.'));
            image.src = objectUrl;
        });

        return {
            image,
            width: image.naturalWidth,
            height: image.naturalHeight,
            dispose: () => URL.revokeObjectURL(objectUrl),
        };
    } catch (error) {
        URL.revokeObjectURL(objectUrl);
        throw error;
    }
}

export async function prepareValuationLetterPhoto(file) {
    if (!(file instanceof File)) {
        throw new Error('Bitte wähle ein gültiges Foto aus.');
    }

    if (!['image/jpeg', 'image/png'].includes(file.type)) {
        throw new Error('Bitte verwende ein JPG- oder PNG-Foto.');
    }

    if (file.size > SOURCE_MAX_BYTES) {
        throw new Error('Das Originalfoto darf maximal 8 MB groß sein.');
    }

    const decoded = await decodePhoto(file);

    try {
        if (!decoded.width || !decoded.height) {
            throw new Error('Das Foto hat keine gültigen Bildabmessungen.');
        }

        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d', { alpha: false });

        if (!ctx) {
            throw new Error('Der Browser kann dieses Foto nicht bearbeiten.');
        }

        // Higher resolutions and qualities are preferred; size is bounded
        // independently of the original's megapixels and PNG encoding.
        const dimensions = [1500, 1300, 1100, 950, 800, 650];
        const qualities = [0.8, 0.7, 0.6, 0.5];

        for (const maxEdge of dimensions) {
            const scale = Math.min(1, maxEdge / Math.max(decoded.width, decoded.height));
            canvas.width = Math.max(1, Math.round(decoded.width * scale));
            canvas.height = Math.max(1, Math.round(decoded.height * scale));

            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(decoded.image, 0, 0, canvas.width, canvas.height);

            for (const quality of qualities) {
                const result = await jpegBlob(canvas, quality);

                if (result.size <= PHOTO_MAX_BYTES) {
                    const base = file.name.replace(/\.[^.]+$/, '').slice(0, 100) || 'immobilie';
                    return new File([result], `${base}.jpg`, {
                        type: 'image/jpeg',
                        lastModified: Date.now(),
                    });
                }
            }
        }

        throw new Error('Das Foto konnte nicht ausreichend verkleinert werden. Bitte ein anderes Motiv verwenden.');
    } finally {
        decoded.dispose();
    }
}
