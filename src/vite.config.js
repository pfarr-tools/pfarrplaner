import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import path from 'path'
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'dev.pfarrplaner.de',
            protocol: 'wss',
            clientPort: 443,
        },
    },
    plugins: [
        laravel({ input: ['resources/js/inertia-app.js'], refresh: true }),
        vue({ template: { transformAssetUrls: { base: null, includeAbsolute: false } } }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
        extensions: ['.mjs', '.js', '.ts', '.jsx', '.tsx', '.json', '.vue'],
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    // Admin sub-chunks — specific rules before the catch-all
                    if (id.includes('/Pages/Admin/User/')) return 'Chunk-Admin-User'
                    if (id.includes('/Pages/Admin/City/') ||
                        id.includes('/Pages/Admin/Location/') ||
                        id.includes('/Pages/Admin/Parish/')) return 'Chunk-Admin-City'
                    if (id.includes('/Pages/Admin/')) return 'Chunk-Admin'
                    if (id.includes('/Pages/Calendar/') ||
                        id.includes('/components/Calendar/')) return 'Chunk-Calendar'
                    if (id.includes('/Pages/serviceEditor') ||
                        id.includes('/components/ServiceEditor/')) return 'Chunk-ServiceEditor'
                    if (id.includes('/Pages/liturgyEditor') ||
                        id.includes('/Pages/Liturgy/') ||
                        id.includes('/components/LiturgyEditor/')) return 'Chunk-Liturgy'
                    if (id.includes('/Pages/sermonEditor') ||
                        id.includes('/Pages/Sermon/') ||
                        id.includes('/components/SermonEditor/')) return 'Chunk-Sermon'
                    if (id.includes('/Pages/Rites/') ||
                        id.includes('/components/RiteEditors/')) return 'Chunk-Rites'
                    if (id.includes('/Pages/Report/') ||
                        id.includes('/Pages/Reports/')) return 'Chunk-Reports'
                    if (id.includes('/Pages/HomeScreen') ||
                        id.includes('/Pages/Dash') ||
                        id.includes('/components/HomeScreen/')) return 'Chunk-Home'
                    if (id.includes('node_modules/quill')) return 'Vendor-Quill'
                },
            },
        },
    },
})

