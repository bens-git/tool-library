<template>
    <v-dialog v-model="dialog" @open="onOpen">
      <template v-slot:activator="{ props: activatorProps }">
        <v-btn
          color="success"
          prepend-icon="mdi-login"
          text="Login"
          variant="tonal"
          v-bind="activatorProps"
          
        ></v-btn>
      </template>
      <LoginForm @logged_in="dialog=false"/>
    </v-dialog>
</template>
<script setup>
import { shallowRef, watch } from "vue";
import { useUserStore } from "@/stores/user";
import { useResponseStore } from "@/stores/response";
import { useRouter } from "vue-router";
import LoginForm from "./LoginForm.vue";
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


</script>
