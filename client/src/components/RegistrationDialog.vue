<template>
  <v-dialog v-model="dialog" @open="onOpen">
    <template v-slot:activator="{ props: activatorProps }">
      <v-btn
        color="primary"
        prepend-icon="mdi-account-plus"
        text="Register"
        variant="tonal"
        v-bind="activatorProps"
      ></v-btn>
    </template>
    <v-card
      v-if="!userStore.user"
      prepend-icon="mdi-account-plus"
      title="Register"
    >
      <v-card-text>
        <v-text-field
          density="compact"
          v-model="name"
          label="Name"
          :error-messages="responseStore?.response?.errors?.name"
          
          required
        ></v-text-field>

        <v-text-field
          density="compact"
          v-model="email"
          label="Email"
          archetype="email"
          :error-messages="responseStore?.response?.errors?.email"
          required
        ></v-text-field>

        <v-text-field
          density="compact"
          v-model="password"
          label="Password"
          type="password"
          :error-messages="responseStore?.response?.errors?.password"
          required
          autocomplete="password"
        ></v-text-field>

        <v-text-field
          density="compact"
          v-model="repeatPassword"
          label="Repeat Password"
          type="password"
          :error-messages="responseStore?.response?.errors?.repeatPassword"
          required
          autocomplete="password"
        ></v-text-field>

        <v-text-field
          density="compact"
          v-model="city"
          label="City"
          :error-messages="responseStore?.response?.errors?.city"
          required
        ></v-text-field>

        <v-text-field
          density="compact"
          v-model="state"
          label="State"
          :error-messages="responseStore?.response?.errors?.state"
          required
        ></v-text-field>

        <v-text-field
          density="compact"
          v-model="country"
          label="Country"
          :error-messages="responseStore?.response?.errors?.country"
          required
        ></v-text-field>
      </v-card-text>
      <v-divider></v-divider>

      <v-card-actions>
        <v-spacer></v-spacer>

        <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>

        <v-btn
          color="success"
          text="Register"
          variant="tonal"
          @click="register"
        ></v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
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

const props = defineProps({});

const name = ref("");
const email = ref("");
const password = ref("");
const repeatPassword = ref("");
const city = ref("");
const state = ref("");
const country = ref("");

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

const register = async () => {
  responseStore.clearResponse(); // Clear previous responses

  const data = await userStore.register({
    name: name.value,
    email: email.value,
    password: password.value,
    repeatPassword: repeatPassword.value,
    city: city.value,
    state: state.value,
    country: country.value,
  });

  if(data?.success){
    dialog.value=false
    router.push({ path: "/item-list" });

  }
};

const onClose = () => {};


</script>
