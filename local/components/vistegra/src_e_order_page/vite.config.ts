import path from "path";
import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";
import fs from "fs";

// https://vite.dev/config/
export default defineConfig(({ mode }) => ({
  plugins: [react()],

  base: mode === 'development' ? '/' : '/dev-e-order/',

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