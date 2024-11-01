// Styles
import "@mdi/font/css/materialdesignicons.css";
import "vuetify/styles";

// Vuetify
import { createVuetify } from "vuetify";
import * as directives from "vuetify/directives";
import { VDateInput } from "vuetify/labs/VDateInput";

const myCustomLightTheme = {
  dark: true,
};

export default createVuetify(
  // https://vuetifyjs.com/en/introduction/why-vuetify/#feature-guides
  {
    theme: {
      defaultTheme: "myCustomLightTheme",
      themes: {
        myCustomLightTheme,
      },
    },
    // locale: 'en-CA',
    components: { VDateInput },
    directives,
  },
);
