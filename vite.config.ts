import {fileURLToPath} from 'node:url'

import babel from '@rolldown/plugin-babel'
import tailwindcss from '@tailwindcss/vite'
import react, {reactCompilerPreset} from '@vitejs/plugin-react'
import laravel from 'laravel-vite-plugin'
import {defineConfig, type Plugin} from 'vite'
import {nodePolyfills} from 'vite-plugin-node-polyfills'

function caniemailData(): Plugin {
  return {
    enforce: 'pre',
    name: 'caniemail-data',
    resolveId: (source, importer) =>
      source === './json.js' && importer?.includes('/caniemail/dist/')
        ? fileURLToPath(new URL('resources/js/lib/caniemail-data.ts', import.meta.url))
        : null,
  }
}

export default defineConfig({
  base: '/vendor/message-preview/',
  define: {
    'process.platform': JSON.stringify('browser'),
    'process.version': JSON.stringify(''),
  },
  optimizeDeps: {rolldownOptions: {plugins: [caniemailData()]}},
  resolve: {tsconfigPaths: true},
  plugins: [
    laravel({
      buildDirectory: 'dist',
      detectTls: false,
      hotFile: 'resources/hot',
      input: ['resources/js/app.tsx'],
      publicDirectory: 'resources',
      refresh: ['resources/views/**', 'src/**', 'workbench/routes/**'],
    }),
    caniemailData(),
    nodePolyfills({
      globals: {Buffer: false, global: false, process: false},
      include: ['path', 'util'],
    }),
    react(),
    babel({presets: [reactCompilerPreset()]}),
    tailwindcss(),
  ],
})
