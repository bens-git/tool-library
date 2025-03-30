<template>
  <v-dialog v-model="dialog" @open="onOpen">
    <template v-slot:activator="{ props: activatorProps }">
      <v-btn
        color="error"
        prepend-icon="mdi-delete"
        text="Delete Job"
        variant="tonal"
        v-bind="activatorProps"
        block
        size="small"
      ></v-btn>
    </template>
    <v-card
      v-if="localJob"
      prepend-icon="mdi-delete"
      title="Delete Job"
      :subtitle="localJob.name"
    >
      <v-card-text> Are you sure you want to delete this job? </v-card-text>
      <v-divider></v-divider>

      <v-card-actions>
        <v-spacer></v-spacer>

        <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>

        <v-btn
          color="error"
          text="Delete"
          variant="tonal"
          @click="deleteJob"
        ></v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
<script setup>
import { shallowRef, ref, watch } from "vue";
import { useJobStore } from "@/stores/job";
import { useResponseStore } from "@/stores/response";

const dialog = shallowRef(false);

const jobStore = useJobStore();
const responseStore = useResponseStore();

const localJob = ref(null);

const props = defineProps({
  job: Object,
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
  localJob.value = {
    ...props.job,
  };
};

// const emit = defineEmits(["update:modelValue", "close"]);

const onOpen = async () => {
  initialize();
  responseStore.$reset();
};

const onClose = () => {};

const deleteJob = () => {
  jobStore.deleteJob(localJob.value.id);
  dialog.value = false;
};
</script>
