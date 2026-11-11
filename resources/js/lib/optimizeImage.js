function toBlob(canvas, type, quality) {
    return new Promise((resolve) => canvas.toBlob(resolve, type, quality));
}

async function loadBitmap(file) {
    if (typeof createImageBitmap === 'function') {
        return createImageBitmap(file);
    }

    const url = URL.createObjectURL(file);
    const image = new Image();
    image.src = url;

    try {
        await image.decode();
        return image;
    } finally {
        URL.revokeObjectURL(url);
    }
}

export async function optimizeImage(file, { maxWidth = 1600, maxHeight = 2000, quality = 0.85 } = {}) {
    if (!file || !file.type || !file.type.startsWith('image/')) {
        return file;
    }

    try {
        const source = await loadBitmap(file);
        const width = source.width;
        const height = source.height;
        const scale = Math.min(1, maxWidth / width, maxHeight / height);

        if (scale >= 1 && file.size <= 900 * 1024) {
            source.close?.();
            return file;
        }

        const targetWidth = Math.max(1, Math.round(width * scale));
        const targetHeight = Math.max(1, Math.round(height * scale));
        const canvas = document.createElement('canvas');
        canvas.width = targetWidth;
        canvas.height = targetHeight;
        canvas.getContext('2d').drawImage(source, 0, 0, targetWidth, targetHeight);
        source.close?.();

        let blob = await toBlob(canvas, 'image/webp', quality);
        let type = 'image/webp';

        if (!blob) {
            blob = await toBlob(canvas, 'image/jpeg', quality);
            type = 'image/jpeg';
        }

        if (!blob || blob.size >= file.size) {
            return file;
        }

        const extension = type === 'image/webp' ? '.webp' : '.jpg';
        const name = file.name.replace(/\.[^.]+$/, '') + extension;

        return new File([blob], name, { type, lastModified: Date.now() });
    } catch (error) {
        return file;
    }
}
