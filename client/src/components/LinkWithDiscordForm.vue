<template>
  <v-card prepend-icon="mdi-discord" title="Link with Discord">
    <v-card-text>
      <v-alert
        v-if="!userStore.user.discord_user_id"
        text="By clicking the 'Link with Discord' button, your Tool-Library account will be securely linked to your Discord account. This action will connect your account IDs and enable enhanced integration between the two platforms. This action is required prior to renting or loaning tools."
        title="Discord Not Authenticated"
        type="warning"
      ></v-alert>

      <v-alert
        v-else="!userStore.user.discord_user_id"
        text="By clicking the 'Link with Discord' button, your Tool-Library account will be securely linked to your Discord account. This action will connect your account IDs and enable enhanced integration between the two platforms. This action is required prior to renting or loaning tools."
        title="Re-Authenticate"
        type="warning"
      ></v-alert>
    </v-card-text>
    <v-divider></v-divider>

    <v-card-actions>
      <v-spacer></v-spacer>

      <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>

      <v-btn
        color="success"
        text="Link with Discord"
        variant="tonal"
        @click="userStore.loginToDiscord()"
      ></v-btn>
    </v-card-actions>
  </v-card>
</template>
<script setup>
import { shallowRef, watch } from "vue";
import { useUserStore } from "@/stores/user";
import { useResponseStore } from "@/stores/response";
const dialog = shallowRef(false);

const userStore = useUserStore();
const responseStore = useResponseStore();

const props = defineProps({});

const onClose = () => {};
</script>
