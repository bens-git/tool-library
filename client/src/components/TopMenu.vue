<template>
  <v-app-bar density="compact">
    <!-- Show Nav Icon Only on Mobile -->
    <v-app-bar-nav-icon v-if="mobile" @click="drawer = !drawer" />

    <v-toolbar-title>
      <v-icon left>mdi-hammer</v-icon>Tool-Library</v-toolbar-title
    >

    <v-spacer />

    <!-- Always Show User Menu -->
    <v-menu v-if="userStore.user">
      <template v-slot:activator="{ props }">
        <v-btn color="primary" v-bind="props">
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
        <v-list-item @click="myItems">
          <v-list-item-title>My Items</v-list-item-title>
        </v-list-item>
        <v-list-item @click="myMaterialArchetypes">
          <v-list-item-title>My Material Archetypes</v-list-item-title>
        </v-list-item>
        <v-list-item @click="myToolArchetypes">
          <v-list-item-title>My Tool Archetypes</v-list-item-title>
        </v-list-item>
        <v-list-item @click="myCategories">
          <v-list-item-title>My Categories</v-list-item-title>
        </v-list-item>
        <v-list-item @click="myUsages">
          <v-list-item-title>My Usages</v-list-item-title>
        </v-list-item>
        <v-list-item @click="myBrands">
          <v-list-item-title>My Brands</v-list-item-title>
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
      <LoginDialog v-if="!userStore.user" />
      <RegistrationDialog v-if="!userStore.user" />
      <v-btn v-if="!userStore.user" to="request-password-reset-form" text>
        Forgot Password
      </v-btn>

      <v-spacer />

      <div v-for="link in links" :key="link.text">
        <v-btn
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
    </template>
  </v-app-bar>

  <!-- Navigation Drawer for Mobile -->
  <v-navigation-drawer v-model="drawer" temporary>
    <v-list>
      <LoginDialog v-if="!userStore.user" />

      <RegistrationDialog v-if="!userStore.user" />
      <v-list-item
        v-if="!userStore.user"
        @click="router.push('request-password-reset-form')"
        >Forgot Password</v-list-item
      >

      <v-list-item
        v-for="link in links"
        :key="link.text"
        @click="router.push(link.route)"
        >{{ link.text }}</v-list-item
      >
    </v-list>
  </v-navigation-drawer>
</template>

<script setup>
import { ref } from "vue";
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

const links = [
  { text: "Materials & Tools", route: "archetype-list" },
  { text: "Projects", route: "project-list" },
];

// Safeguard to handle undefined or null paths
const normalizePath = (path) => {
  return path ? path.replace(/\/+$/, "").trim() : "";
};

const isActiveRoute = (linkRoute) => {
  return normalizePath(route.path) === normalizePath(linkRoute);
};

const editProfile = () => {
  router.push({ path: "/edit-user" });
};

const myItems = () => {
  router.push({ path: "/my-items" });
};

const myToolArchetypes = () => {
  router.push({ path: "/my-archetypes", query: { resource: "TOOL" } });
};

const myMaterialArchetypes = () => {
  router.push({ path: "/my-archetypes", query: { resource: "MATERIAL" } });
};

const myCategories = () => {
  router.push({ path: "/my-categories" });
};

const myUsages = () => {
  router.push({ path: "/my-usages" });
};

const myBrands = () => {
  router.push({ path: "/my-brands" });
};

const myJobs = () => {
  router.push({ path: "/my-jobs" });
};

const myProjects = () => {
  router.push({ path: "/my-projects" });
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
