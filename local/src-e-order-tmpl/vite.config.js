import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig(({ mode }) => ({
  // Корневая папка для исходников
  root: resolve(__dirname),

  // Папка для сборки
  build: {
    outDir: resolve(__dirname, '../templates/e-order'),
    emptyOutDir: false, // Не очищать папку шаблона при сборке
    manifest: false,
    minify: mode === 'production' ? 'esbuild' : false, // Минифицируем только в production
    cssMinify: mode === 'production', // Минифицируем CSS только в production
    rollupOptions: {
      input: {
        index: resolve(__dirname, 'js/index.js'),
      },
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          const info = assetInfo.name.split('.');
          const ext = info[info.length - 1];
          
          // Разделяем стили и другие ассеты
          if (ext === 'css') {
            return 'css/index.css';
          }
          
          return '[name].[ext]';
        }
      }
    },
    cssCodeSplit: false, // Все стили в один файл
  },

  // Пути для алиасов (если понадобятся)
  resolve: {
    alias: {
      '@': resolve(__dirname, 'js'),
      '@styles': resolve(__dirname, 'scss'),
    }
  },

  // Настройки CSS
  css: {
    preprocessorOptions: {
      scss: {
        silenceDeprecations: ['legacy-js-api'],
        api: 'modern-compiler',
      },
    },
  },
}));

