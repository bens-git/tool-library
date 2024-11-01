import Axios from "axios";
import store from "@/store/store";
var server = import.meta.env.VITE_SERVER_HOST;

if (import.meta.env.VITE_SERVER_PORT != "443") {
  server = server + `:` + import.meta.env.VITE_SERVER_PORT;
}

export default () => {
  return Axios.create({
    baseURL: `https://` + server,
    headers: {
      Authorization: `Bearer ${store.state.token}`,
    },
  });
};
