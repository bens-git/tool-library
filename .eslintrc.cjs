module.exports = {
    root: true,
    env: {
        browser: true,
        node: true,
        es2021: true,
    },
    parserOptions: {
        parser: "babel-eslint",
        ecmaVersion: 2021,
        sourceType: "module",
    },
    extends: [
        "plugin:vue/vue3-essential",
        "standard",
        "plugin:prettier/recommended",
    ],
    plugins: ["vue"],
    rules: {
        // Your custom rules
        "no-console": process.env.NODE_ENV === "production" ? "warn" : "off",
        "no-debugger": process.env.NODE_ENV === "production" ? "warn" : "off",
    },
};
