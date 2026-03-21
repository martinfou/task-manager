import path from 'path';
import { fileURLToPath } from 'url';
import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    test: {
        environment: 'node',
        include: ['resources/js/**/*.test.js'],
    },
});
