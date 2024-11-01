const { defineConfig } = require("@vue/cli-service");
const { readFileSync } = require("fs");
const webpack = require("webpack");

module.exports = defineConfig({
  transpileDependencies: true,

  pluginOptions: {
    vuetify: {
      // https://github.com/vuetifyjs/vuetify-loader/tree/next/packages/vuetify-loader
    },
  },

  configureWebpack: {
    plugins: [
      new webpack.DefinePlugin({
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: JSON.stringify(false),
      }),
    ],
  },

  devServer: {
    client: false,
    host: "192.168.0.146",
    port: 8081,
    // https: {
    //   key: readFileSync("/var/www/certs/192.168.0.146.key"),
    //   cert: readFileSync("/var/www/certs/192.168.0.146.crt"),
    // },
  },
});
