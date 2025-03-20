<template>
  <v-dialog v-model="dialog" @open="onOpen">
    <template v-slot:activator="{ props: activatorProps }">
      <v-btn
        color="primary"
        prepend-icon="mdi-pencil"
        text="Edit Profile"
        variant="tonal"
        block
        v-bind="activatorProps"
      ></v-btn>
    </template>
    <v-card prepend-icon="mdi-pencil" title="Edit Profile">
      <v-card-text>
        <!-- Name Field -->
        <v-text-field
          v-model="name"
          label="Name"
          :error-messages="responseStore?.response?.errors?.name"
        ></v-text-field>

        <!-- City -->
        <v-text-field
          v-model="city"
          label="City"
          :error-messages="responseStore?.response?.errors?.city"
        ></v-text-field>

        <!-- State -->
        <v-text-field
          v-model="state"
          label="State"
          :error-messages="responseStore?.response?.errors?.state"
        ></v-text-field>

        <!-- Country -->
        <v-text-field
          v-model="country"
          label="Country"
          :error-messages="responseStore?.response?.errors?.country"
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

        <DeleteAccountDialog />

        <PasswordDialog />
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
<script setup>
import { shallowRef, ref, watch } from "vue";
import { useUserStore } from "@/stores/user";
import { useLocationStore } from "@/stores/location";
import { useResponseStore } from "@/stores/response";
import PasswordDialog from "./PasswordDialog.vue";
import DeleteAccountDialog from "./DeleteAccountDialog.vue";

const dialog = shallowRef(false);

const userStore = useUserStore();
const responseStore = useResponseStore();
const locationStore = useLocationStore();

const name = ref("");
const location = ref("");
const city = ref("");
const state = ref("");
const country = ref("");
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
  name.value = userStore.user.name;
  city.value = userStore.user.location.city;
  state.value = userStore.user.location.state;
  country.value = userStore.user.location.country;
};

const onClose = () => {};

// Save user and location data
const save = async () => {
  // Save location first to get the ID
  await locationStore.updateUserLocation({
    id: userStore.user.location.id,
    city: city.value,
    state: state.value,
    country: country.value,
  });

  // Update user with new location ID
  await userStore.updateUser({ name: name.value });
};
</script>
