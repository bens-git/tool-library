<template>
  <v-dialog v-model="dialog" @open="onOpen">
    <template v-slot:activator="{ props: activatorProps }">
      <v-btn
        color="primary"
        prepend-icon="mdi-discord"
        text="Link with Discord"
        variant="tonal"
        v-bind="activatorProps"
      ></v-btn>
    </template>
    <LinkWithDiscordForm />
  </v-dialog>
</template>
<script setup>
import { shallowRef, watch } from "vue";
import { useUserStore } from "@/stores/user";
import { useResponseStore } from "@/stores/response";
import LinkWithDiscordForm from "./LinkWithDiscordForm.vue";

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
</script>
