import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import prettierConfig from '@vue/eslint-config-prettier';
import globals from 'globals';

export default [
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    prettierConfig,
    {
        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.node,
                route: 'readonly',
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            'vue/require-default-prop': 'off',
            'no-console': ['warn', { allow: ['warn', 'error'] }],
        },
    },
    {
        ignores: ['vendor/**', 'node_modules/**', 'public/build/**', 'bootstrap/ssr/**'],
    },
];
