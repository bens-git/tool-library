<template>
  <v-app-bar density="compact">
    <!-- Show Nav Icon Only on Mobile -->
    <v-app-bar-nav-icon @click="drawer = !drawer" />

    <v-app-bar-title v-if="!mobile">
      <v-icon left>mdi-hammer</v-icon>Tool-Library
    </v-app-bar-title>

    <!-- Always Show User Menu -->
    <v-menu v-if="userStore.user">
      <template v-slot:activator="{ props }">
        <v-btn color="primary" v-bind="props" size="small">
          {{
            userStore.user.discord_username
              ? userStore.user.discord_username
              : userStore.user.name
          }}
          <v-icon right>mdi-menu-down</v-icon>
        </v-btn>
      </template>
      <v-list>
        <v-list-item @click="myRentals">
          <v-list-item-title>My Rentals</v-list-item-title>
        </v-list-item>
        <v-list-item @click="myLoans">
          <v-list-item-title>My Loans</v-list-item-title>
        </v-list-item>

        <v-list-item>
          <EditProfileDialog />
        </v-list-item>
        <v-list-item><LinkWithDiscordDialog /></v-list-item>
        <v-list-item>
          <LogoutDialog />
        </v-list-item>
      </v-list>
    </v-menu>

    <!-- Additional Links on Larger Screens -->
    <template v-if="!mobile">
      <LoginDialog v-if="!userStore.user && !isActiveRoute('/login-form')" />
      <RegistrationDialog v-if="!userStore.user" />
      <v-btn v-if="!userStore.user" to="request-password-reset-form" text>
        Forgot Password
      </v-btn>
    </template>

    <v-spacer />

    <div v-for="link in links" :key="link.text">
      <v-btn
        size="small"
        v-if="link.route"
        :to="link.route"
        text
        :class="{ active: isActiveRoute(link.route) }"
      >
        {{ link.text }}
      </v-btn>
      <v-btn v-else :href="link.url" text>
        {{ link.text }}
      </v-btn>
    </div>

    <v-spacer />
  </v-app-bar>

  <!-- Navigation Drawer for Mobile -->
  <v-navigation-drawer v-model="drawer" temporary>
    <v-list>
      <LoginDialog v-if="!userStore.user && !isActiveRoute('/login-form')" />

      <RegistrationDialog v-if="!userStore.user" />
      <v-list-item
        v-if="!userStore.user"
        @click="router.push('request-password-reset-form')"
        >Forgot Password</v-list-item
      >

      <v-list-item
        v-for="link in drawerLinks"
        :key="link.text"
        @click="router.push(link.route)"
        >{{ link.text }}</v-list-item
      >
    </v-list>
  </v-navigation-drawer>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import { useDisplay } from "vuetify";
import { useUserStore } from "@/stores/user";
import { useRouter, useRoute } from "vue-router";
import LogoutDialog from "./LogoutDialog.vue";
import RegistrationDialog from "./RegistrationDialog.vue";
import LoginDialog from "./LoginDialog.vue";
import LinkWithDiscordDialog from "./LinkWithDiscordDialog.vue";
import EditProfileDialog from "./EditProfileDialog.vue";

const route = useRoute();
const router = useRouter();
const { mobile } = useDisplay();
const drawer = ref(false);
const userStore = useUserStore();

const links = ref([]);
const drawerLinks = ref([]);

onMounted(async () => {
  setupLinks();
});

const setupLinks = () => {
  if (userStore.user) {
    links.value = [
      { text: "Items", route: "item-list" },
    ];

    drawerLinks.value = [
      { text: "Items", route: "item-list" },
      { text: "Archetypes", route: "archetype-list" },
      { text: "Categories", route: "category-list" },
      { text: "Usages", route: "usage-list" },
      { text: "Brands", route: "brand-list" },
    ];
  } else {
    links.value = [];
    drawerLinks.value = [];
  }
};

// Watch for changes in the responseStore to display the appropriate snackbar
watch(
  () => userStore.user,
  (newUser) => {
    setupLinks();
  },
  { deep: true }
);

// Safeguard to handle undefined or null paths
const normalizePath = (path) => {
  return path ? path.replace(/\/+$/, "").trim() : "";
};

const isActiveRoute = (linkRoute) => {
  return normalizePath(route.path) === normalizePath(linkRoute);
};

const myRentals = () => {
  router.push({ path: "/my-rentals" });
};

const myLoans = () => {
  router.push({ path: "/my-loans" });
};
</script>

<style>
.active {
  color: #1976d2;
  font-weight: bold;
  background-color: rgba(25, 118, 210, 0.1);
}
</style>
