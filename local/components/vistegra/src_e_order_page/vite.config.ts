import path from "path";
import react from "@vitejs/plugin-react";
import {defineConfig} from "vite";
import fs from "fs";
import {VitePWA} from 'vite-plugin-pwa';

const BITRIX_TEMPLATE_PATH = '/local/components/vistegra/e.order.page/templates/.default/';
const BASE_PATH = '/dev-e-order/'

// https://vite.dev/config/
export default defineConfig(({mode}) => ({
  plugins: [
    react(),
    VitePWA({
      registerType: 'autoUpdate',
      strategies: 'generateSW',

      // Настройки генерации
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
        // чтобы SW не пытался кэшировать index.html, которого нет в папке билда
        navigateFallback: null,
      },

      // Имя выходного файла
      filename: 'sw.js',

      // Настройки манифеста
      manifest: {
        name: 'Ligron E-Order',
        short_name: 'E-Order',
        description: 'Система оформления заказов Ligron',
        theme_color: '#ffffff',
        background_color: '#ffffff',
        display: 'standalone',

        scope: BASE_PATH,
        start_url: BASE_PATH,
        orientation: 'portrait',

        icons: [
          {
            src: `${BITRIX_TEMPLATE_PATH}pwa-icons/pwa-192x192.png`,
            sizes: '192x192',
            type: 'image/png',
            purpose: 'any maskable'
          },
          {
            src: `${BITRIX_TEMPLATE_PATH}pwa-icons/pwa-512x512.png`,
            sizes: '512x512',
            type: 'image/png'
          },
          {
            src: `${BITRIX_TEMPLATE_PATH}pwa-icons/pwa-512x512.png`,
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any maskable'
          }
        ]
      },
    })
  ],

  base: mode === 'development' ? '/' : BASE_PATH,

  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },

  server: {
    https: {
      key: fs.readFileSync(path.resolve(__dirname, "localhost-key.pem")),
      cert: fs.readFileSync(path.resolve(__dirname, "localhost.pem")),
    },
    port: 5173,
    host: "localhost",
  },

  build: {
    // Куда собираем
    outDir: path.resolve(__dirname, "../e.order.page/templates/.default"),

    // НЕ чистим папку — там лежит template.php
    emptyOutDir: false,

    rollupOptions: {
      // Указываем только JS входные точки, без HTML
      input: {
        main: path.resolve(__dirname, "src/main.tsx"), // или main.ts
      },

      output: {
        // Один JS-файл
        entryFileNames: "script.js",
        chunkFileNames: "script.js",

        // Один CSS-файл
        assetFileNames: (asset) => {
          if (/\.css$/.test(asset.name ?? "")) {
            return "style.css";
          }
          return "[name].[ext]";
        },
      },
      preserveEntrySignatures: "strict",
    },
  },
}));