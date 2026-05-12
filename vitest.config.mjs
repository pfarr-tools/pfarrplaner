import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: { '@': path.resolve(__dirname, 'resources/js') },
        extensions: ['.mjs', '.js', '.jsx', '.json', '.vue'],
    },
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['tests/js/setup.js'],
        include: ['tests/js/**/*.{test,spec}.js'],
        coverage: {
            provider: 'v8',
            reportsDirectory: 'tests/js/coverage',
            reporter: ['text', 'html', 'clover'],
            include: ['resources/js/**'],
            exclude: [
                'resources/js/inertia-app.js',
                'resources/js/bootstrap.js',
                'resources/js/app.js',
                'resources/js/vue-app.js',
                'resources/js/compat/**',
            ],
        },
    },
})
