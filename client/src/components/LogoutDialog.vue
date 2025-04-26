<template>
  <v-dialog v-model="dialog" @open="onOpen">
    <template v-slot:activator="{ props: activatorProps }">
      <v-btn
        color="error"
        prepend-icon="mdi-logout"
        text="Logout"
        variant="tonal"
        v-bind="activatorProps"
        block
      ></v-btn>
    </template>
    <v-card
      v-if="userStore.user"
      prepend-icon="mdi-logout"
      title="Logout"
      :subtitle="userStore.user.name"
    >
      <v-card-text> Are you sure you want to logout? </v-card-text>
      <v-divider></v-divider>

      <v-card-actions>
        <v-spacer></v-spacer>

        <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>

        <v-btn
          color="error"
          text="Logout"
          variant="tonal"
          @click="logout"
        ></v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
<script setup>
import { shallowRef, watch } from "vue";
import { useUserStore } from "@/stores/user";
import { useResponseStore } from "@/stores/response";
import { useRouter } from "vue-router";

const dialog = shallowRef(false);
const router = useRouter();

const userStore = useUserStore();
const responseStore = useResponseStore();

const props = defineProps({});

// Watch the dialog's state
watch(dialog, (newVal) => {
  if (newVal) {
    onOpen();
  } else {
    onClose();
  }
});

// const emit = defineEmits(["update:modelValue", "close"]);

const onOpen = async () => {
  responseStore.$reset();
};

const onClose = () => {};

const logout = async () => {
  dialog.value = false;
  await userStore.logout();
  router.push({ path: "/login-form" });
};
</script>
