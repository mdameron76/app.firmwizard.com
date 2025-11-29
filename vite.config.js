import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/layout-fix.css',
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/assets/js/main',
                'resources/assets/js/custom',
                'resources/assets/js/custom-switcher',
                'resources/assets/js/defaultmenu',
                'resources/assets/js/sales-dashboard',
                'resources/assets/js/analytics-dashboard',
                'resources/assets/js/authentication-main',
                'resources/assets/js/simplebar',
            ],
            refresh: true,
        }),
        viteStaticCopy({
            targets: [
              {
                src: ([
                  'resources/assets/images/',
                  'resources/assets/icon-fonts/',
                  'resources/assets/js/main.js',
                  'resources/assets/js/sticky.js',
                  'resources/assets/js/show-password.js',
                ]),
                dest: 'assets/'
              }
            ]
        }),
        {
          name: 'blade',
          handleHotUpdate({ file, server }) {
            if (file.endsWith('.blade.php')) {
            server.ws.send({
                type: 'full-reload',
                path: '*',
            });
            }
          },
        }
    ],
    build: {
        chunkSizeWarningLimit: 1600,
        outDir: 'public/build',
        emptyOutDir: true,
    },
});
