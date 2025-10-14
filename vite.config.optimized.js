import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                app: 'resources/css/app.css',
                js: 'resources/js/app.js',
            },
            output: {
                assetFileNames: 'assets/[name]-[hash][extname]',
                chunkFileNames: 'assets/[name]-[hash].js',
                entryFileNames: 'assets/[name]-[hash].js',
            },
        },
        cssCodeSplit: false,
        sourcemap: false,
        minify: 'esbuild',
        // Optimize for production builds
        target: 'es2015',
        chunkSizeWarningLimit: 1000,
        // Reduce build time
        reportCompressedSize: false,
        // Optimize dependencies
        commonjsOptions: {
            include: [/node_modules/]
        }
    },
    define: {
        'process.env.MIX_PUSHER_APP_KEY': JSON.stringify(process.env.VITE_PUSHER_APP_KEY),
        'process.env.MIX_PUSHER_APP_CLUSTER': JSON.stringify(process.env.VITE_PUSHER_APP_CLUSTER),
    },
    optimizeDeps: {
        include: ['firebase/app', 'firebase/database'],
        // Exclude heavy dependencies from pre-bundling
        exclude: ['firebase']
    },
    server: {
        hmr: {
            host: 'localhost',
        },
        https: true,
    },
    base: '/',
    // Optimize for faster builds
    esbuild: {
        target: 'es2015',
        minifyIdentifiers: true,
        minifySyntax: true,
        minifyWhitespace: true,
    }
});
