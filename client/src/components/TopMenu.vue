<template>
  <v-app-bar app dark>
    <v-app-bar-nav-icon v-if="isSmallScreen" @click="$emit('toggleDrawer')" />


    <v-app-bar-title>
      <a href="https://holdfast.group" target="_blank" rel="noopener noreferrer">
        <v-img src="@/assets/logo.png" alt="Logo" contain max-height="40" max-width="40" />
      </a>
    </v-app-bar-title>

    <v-toolbar-title>
      Tool-Library
    </v-toolbar-title>

    <v-spacer />

    <div v-for="link in links" :key="link.text">
      <!-- Debug output to help with identifying matching logic -->
      <v-btn v-if="link.route" :to="link.route" text :class="{ active: isActiveRoute(link.route) }">
        {{ link.text }}
      </v-btn>
      <v-btn v-else :href="link.url" text>
        {{ link.text }}
      </v-btn>
    </div>



    <v-spacer />

    <v-menu v-if="userStore.user">
      <template v-slot:activator="{ props }">
        <v-btn color="primary" v-bind="props">
          {{ userStore.user.discord_username ? userStore.user.discord_username : userStore.user.name }} <v-icon
            right>mdi-menu-down</v-icon>
        </v-btn>
      </template>
      <v-list>
        <v-list-item @click="myRentals">
          <v-list-item-title>My Rentals</v-list-item-title>
        </v-list-item>
        <v-list-item @click="myLoans">
          <v-list-item-title>My Loans</v-list-item-title>
        </v-list-item>
        <v-list-item @click="myMangement">
          <v-list-item-title>My Tools & Materials</v-list-item-title>
        </v-list-item>
        <v-list-item @click="editProfile">
          <v-list-item-title>Edit Profile</v-list-item-title>
        </v-list-item>
         <v-list-item @click="routeToDiscordLink">
          <v-list-item-title>Link With Discord</v-list-item-title>
        </v-list-item>
        <v-list-item @click="confirmLogout">
          <v-list-item-title>Logout</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>


    <v-btn v-if="!userStore.user" to="login-form" text>
      Login
    </v-btn>
    <v-btn v-if="!userStore.user" to="register-form" text>
      Register
    </v-btn>
    <v-btn v-if="!userStore.user" to="request-password-reset-form" text>
      Forgot Password
    </v-btn>

    <!-- Logout Confirmation Dialog -->
    <v-dialog v-model="logoutDialog" max-width="400">
      <v-card>
        <v-card-title class="text-h5">Confirm Logout</v-card-title>
        <v-card-text>Are you sure you want to log out?</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn color="red" text @click="logoutDialog = false">Cancel</v-btn>
          <v-btn color="green" text @click="logout">Yes</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-app-bar>
</template>

<script setup>
import { computed, ref } from "vue";
import { useDisplay } from "vuetify";
import { useUserStore } from "@/stores/user";
import { useRouter, useRoute } from "vue-router";


const route = useRoute();
const router = useRouter();
const { smAndDown } = useDisplay();
const isSmallScreen = computed(() => smAndDown.value);
const userStore = useUserStore();
const logoutDialog = ref(false);

const links = [
{ text: "TOOLS", route: "type-list" },
// { text: "JOBS", route: "job-list" },
// { text: "PROJECTS", route: "project-list" },
];

// Safeguard to handle undefined or null paths
const normalizePath = (path) => {
  return path ? path.replace(/\/+$/, "").trim() : "";
};

const isActiveRoute = (linkRoute) => {
  return normalizePath(route.path) === normalizePath(linkRoute);
};

const confirmLogout = () => {
  logoutDialog.value = true;
};

const logout = async () => {
  logoutDialog.value = false;
  await userStore.logout();
  router.push({ name: "login-form" });
};

const editProfile = () => {
  router.push({ name: "edit-user" });
};

const routeToDiscordLink = () => {
  router.push({ name: "route-to-discord-link" });
};


const myMangement = () => {
  router.push({ name: "my-tools" });
};

const myRentals = () => {
  router.push({ name: "my-rentals" });
};

const myLoans = () => {
  router.push({ name: "my-loans" });
};





</script>

<style>
.active {
  color: #1976d2;
  font-weight: bold;
  background-color: rgba(25, 118, 210, 0.1);
}
</style>