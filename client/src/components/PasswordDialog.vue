<template>
  <v-dialog v-model="dialog" @open="onOpen">
    <template v-slot:activator="{ props: activatorProps }">
      <v-btn
        color="primary"
        prepend-icon="mdi-lock-reset"
        text="Change Password"
        variant="tonal"
        v-bind="activatorProps"
      ></v-btn>
    </template>
    <v-card prepend-icon="mdi-lock-reset" title="Change Password">
      <v-card-text>
        <!-- Current Password -->
        <v-text-field
          v-model="currentPassword"
          label="Current Password"
          type="password"
          required
          autocomplete="current-password"
        ></v-text-field>

        <!-- New Password -->
        <v-text-field
          v-model="newPassword"
          label="New Password"
          type="password"
          required
        ></v-text-field>

        <!-- Confirm New Password -->
        <v-text-field
          v-model="confirmPassword"
          label="Confirm New Password"
          type="password"
          required
        ></v-text-field>
      </v-card-text>
      <v-divider></v-divider>

      <v-card-actions>
        <v-spacer></v-spacer>

        <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>

        <v-btn
          color="success"
          text="Save"
          variant="tonal"
          @click="save"
        ></v-btn>

      
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
<script setup>
import { shallowRef, watch } from "vue";
import { useUserStore } from "@/stores/user";
import { useResponseStore } from "@/stores/response";
const dialog = shallowRef(false);

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

const save = async () => {
  // Implement your password change logic here
  const data = await userStore.changePassword(
    currentPassword.value,
    newPassword.value,
    confirmPassword.value
  );

  if (data?.success) {
    dialog.value = false;
  }
};
</script>
