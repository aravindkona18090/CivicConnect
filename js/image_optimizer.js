/**
 * js/image_optimizer.js - Client-Side Auto Image Compression & Anti-Spam Middleware
 * Compresses citizen camera photos client-side to ensure lightning fast uploads and save server bandwidth.
 */

window.CivicImageOptimizer = {
    /**
     * Compress an image file to a targeted max dimension and quality
     * @param {File} file - Original file from input[type=file]
     * @param {number} maxDimension - Max width or height (default 1280px)
     * @param {number} quality - JPEG compression quality 0.1 to 1.0 (default 0.82)
     * @returns {Promise<File>}
     */
    compress: function(file, maxDimension = 1280, quality = 0.82) {
        return new Promise((resolve, reject) => {
            if (!file || !file.type.match(/image.*/)) {
                return resolve(file);
            }

            // Skip compression if already under 350KB
            if (file.size <= 350 * 1024) {
                return resolve(file);
            }

            const reader = new FileReader();
            reader.readAsDataURL(file);

            reader.onload = function(event) {
                const img = new Image();
                img.src = event.target.result;

                img.onload = function() {
                    let width = img.width;
                    let height = img.height;

                    if (width > height) {
                        if (width > maxDimension) {
                            height = Math.round((height * maxDimension) / width);
                            width = maxDimension;
                        }
                    } else {
                        if (height > maxDimension) {
                            width = Math.round((width * maxDimension) / height);
                            height = maxDimension;
                        }
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;

                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob(function(blob) {
                        if (!blob) return resolve(file);
                        const optimizedFile = new File([blob], file.name.replace(/\.[^/.]+$/, ".jpg"), {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });
                        resolve(optimizedFile);
                    }, 'image/jpeg', quality);
                };

                img.onerror = function() {
                    resolve(file);
                };
            };

            reader.onerror = function() {
                resolve(file);
            };
        });
    },

    /**
     * Auto-attach compression listener to any file input element
     */
    attach: function(inputElementSelector) {
        const input = document.querySelector(inputElementSelector);
        if (!input) return;

        input.addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const compressed = await CivicImageOptimizer.compress(file);
            
            // Create DataTransfer object to replace input file
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(compressed);
            input.files = dataTransfer.files;
        });
    }
};
