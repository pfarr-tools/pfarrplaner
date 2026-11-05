import eslint from '@eslint/js'
import jsdoc from 'eslint-plugin-jsdoc'
import vue from 'eslint-plugin-vue'

const globals = {
    __dirname: 'readonly',
    console: 'readonly',
    define: 'readonly',
    describe: 'readonly',
    document: 'readonly',
    expect: 'readonly',
    it: 'readonly',
    Node: 'readonly',
    process: 'readonly',
    vi: 'readonly',
    window: 'readonly',
}

export default [
    {
        ignores: [
            'node_modules/**',
            'public/build/**',
            'storage/**',
            'tests/js/coverage/**',
            'vendor/**',
        ],
    },
    eslint.configs.recommended,
    ...vue.configs['flat/recommended'],
    {
        files: ['**/*.{js,mjs,vue}'],
        languageOptions: { globals },
        plugins: { jsdoc },
        rules: {
            'no-var': 'error',
            'prefer-const': 'error',
            eqeqeq: ['error', 'always'],
            'no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
            'vue/component-name-in-template-casing': ['error', 'PascalCase'],
            'vue/define-macros-order': [
                'error',
                { order: ['defineOptions', 'defineProps', 'defineEmits'] },
            ],
            'vue/multi-word-component-names': 'off',
            'jsdoc/check-alignment': 'error',
            'jsdoc/check-tag-names': 'error',
            'jsdoc/no-undefined-types': 'off',
            'jsdoc/require-description': 'error',
            'jsdoc/require-jsdoc': [
                'warn',
                {
                    require: {
                        ArrowFunctionExpression: true,
                        ClassDeclaration: true,
                        FunctionDeclaration: true,
                        MethodDefinition: true,
                    },
                },
            ],
        },
    },
]
