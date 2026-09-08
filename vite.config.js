import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

const swapFontAwesomeDisplay = () => ({
    name: 'swap-font-awesome-display',
    generateBundle(_, bundle) {
        Object.values(bundle).forEach((asset) => {
            if (asset.type !== 'asset' || typeof asset.source !== 'string' || ! asset.fileName.endsWith('.css')) {
                return;
            }

            asset.source = asset.source.replace(/font-display:\s*block/g, 'font-display:swap');
        });
    },
});

export default defineConfig({
    plugins: [
        swapFontAwesomeDisplay(),
        laravel({
            input: [
                'resources/css/frontsite.css',
                'resources/js/frontsite.js',
                'resources/css/admin.css',
                'resources/js/admin.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
