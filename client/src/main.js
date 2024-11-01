import { createApp } from "vue";
import App from "./App.vue";

import { loadFonts } from "./plugins/webfontloader";
// Plugins
import { registerPlugins } from "@/plugins";
import "leaflet/dist/leaflet.css";

loadFonts();

const app = createApp(App);

registerPlugins(app);

app.mount("#app");
