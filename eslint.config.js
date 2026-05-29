import js from "@eslint/js";
import pluginVue from "eslint-plugin-vue";
import configPrettier from "eslint-config-prettier";
import globals from "globals";

export default [
    {
        ignores: [
            "node_modules/**",
            "vendor/**",
            "public/build/**",
            "**/build/**",
            "**/dist/**",
            "**/*.min.js",
            "bootstrap/cache/**",
            "storage/**",
            "tests/e2e-pw/**",
        ],
    },

    js.configs.recommended,
    ...pluginVue.configs["flat/recommended"],
    configPrettier,

    {
        files: ["**/*.js", "**/*.vue"],
        languageOptions: {
            ecmaVersion: "latest",
            sourceType: "module",
            globals: {
                ...globals.browser,
                ...globals.node,
                ...globals.es2021,
            },
        },
        rules: {
            "no-unused-vars": "warn",
            "no-undef": "warn",
            "no-useless-escape": "warn",
            "no-misleading-character-class": "warn",
            "vue/multi-word-component-names": "off",
        },
    },
];
