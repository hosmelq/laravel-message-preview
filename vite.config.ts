import babel from '@rolldown/plugin-babel'
import tailwindcss from '@tailwindcss/vite'
import react, {reactCompilerPreset} from '@vitejs/plugin-react'
import laravel from 'laravel-vite-plugin'
import {defineConfig} from 'vite'

export default defineConfig({
  base: '/vendor/message-preview/',
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
    react(),
    babel({presets: [reactCompilerPreset()]}),
    tailwindcss(),
  ],
})
