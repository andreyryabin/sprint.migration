module.exports = {
  root: true,

  env: {
    node: true,
  },

  rules: {
    'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'off',
    'no-console': process.env.NODE_ENV === 'production' ? 'error' : 'off',
    'vue/no-confusing-v-for-v-if': 'warning',
    'vue/order-in-components': 'error',
    'vue/this-in-template': 'warning',
    'vue/camelcase': 'error',
    'vue/eqeqeq': 'error',
  },

  parserOptions: {
    parser: 'babel-eslint',
  },

  extends: [
    'plugin:vue/recommended',
  ],
};
