<template>
  <div class="pl-4 text-center">
    <v-dialog v-model="dialog" @open="onOpen">
      <template v-slot:activator="{ props: activatorProps }">
        <v-btn
          color="error"
          prepend-icon="mdi-delete"
          text="Delete Account"
          variant="tonal"
          v-bind="activatorProps"
          block
        ></v-btn>
      </template>
      <v-card
        v-if="userStore.user"
        prepend-icon="mdi-delete"
        title="Delete Account"
        :subtitle="userStore.user.name"
      >
        <v-card-text>
          Are you sure you want to delete your account? This action can not be
          undone!
        </v-card-text>
        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>

          <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>

          <v-btn
            color="error"
            text="Delete"
            variant="tonal"
            @click="deleteUser"
          ></v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
<script setup>
import { shallowRef, ref, watch } from "vue";
import { useUserStore } from "@/stores/user";
import { useResponseStore } from "@/stores/response";
import { useRouter } from "vue-router";

const dialog = shallowRef(false);
const router = useRouter();

const userStore = useUserStore();
const responseStore = useResponseStore();


const props = defineProps({
  brand: Object,
});

// Watch the dialog's state
watch(dialog, (newVal) => {
  if (newVal) {
    onOpen();
  } else {
    onClose();
  }
});

// Function to initialize
const initialize = () => {
 
};

// const emit = defineEmits(["update:modelValue", "close"]);

const onOpen = async () => {
  initialize();
  responseStore.$reset();
};

const onClose = () => {};

// Delete the user account
const deleteUser = async () => {
  const data = await userStore.deleteUser();

  if (data?.success) {
    dialog.value = false;
    router.push("/login-form");
  }
};
</script>
