// eslint.config.js
import js from "@eslint/js";
import pluginVue from "eslint-plugin-vue";
import configPrettier from "eslint-config-prettier";

export default [
  js.configs.recommended,

  // Vue 3 flat recommended config (this IS an array)
  ...pluginVue.configs["flat/recommended"],

  // Prettier (this is just an object, so include it directly)
  configPrettier,

  {
    files: ["**/*.vue", "**/*.js"],
    languageOptions: {
      ecmaVersion: 2023,
      sourceType: "module",
      globals: {
        Blob: "readonly",
        cancelAnimationFrame: "readonly",
        console: "readonly",
        crypto: "readonly",
        document: "readonly",
        fetch: "readonly",
        FormData: "readonly",
        localStorage: "readonly",
        MutationObserver: "readonly",
        navigator: "readonly",
        process: "readonly",
        requestAnimationFrame: "readonly",
        setTimeout: "readonly",
        structuredClone: "readonly",
        URL: "readonly",
        window: "readonly",
        setInterval: "readonly",
        clearInterval: "readonly",
        route: "readonly"
      },
    },
    rules: {
      // example overrides:
      // 'vue/max-attributes-per-line': 'off',
    },
  },
];
