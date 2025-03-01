<template>
  <div class="pl-4 text-center">
    <v-dialog v-model="dialog" @open="onOpen">
      <template v-slot:activator="{ props: activatorProps }">
        <v-btn
          :color="aim == 'edit' ? 'primary' : 'success'"
          class="text-none font-weight-regular"
          :prepend-icon="aim == 'edit' ? 'mdi-pencil' : 'mdi-plus'"
          :text="aim == 'edit' ? `Edit Category` : `Create Category`"
          variant="tonal"
          v-bind="activatorProps"
          block
        ></v-btn>
      </template>
      <v-card
        :prepend-icon="aim == 'edit' ? 'mdi-pencil' : 'mdi-plus'"
        :title="aim == 'edit' ? `Edit Category` : `Create Category`"
      >
        <v-card-text v-if="localCategory">
          <v-row dense>
            <v-col cols="12" md="4" sm="6">
              <v-text-field
                density="compact"
                v-model="localCategory.name"
                :error-messages="responseStore?.response?.errors?.name"
                label="Name"
              />
            </v-col>
           
          </v-row>
        </v-card-text>
        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>

          <v-btn text="Close" variant="plain" @click="dialog = false"></v-btn>

          <v-btn
            v-if="aim == 'edit'"
            color="primary"
            text="Save"
            variant="tonal"
            @click="save"
          ></v-btn>

          <v-btn
            v-else
            color="primary"
            text="Create"
            variant="tonal"
            @click="create"
          ></v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
<script setup>
import { shallowRef, ref, watch, onMounted } from "vue";
import { useCategoryStore } from "@/stores/category";
import { useResponseStore } from "@/stores/response";

const dialog = shallowRef(false);

const categoryStore = useCategoryStore();
const responseStore = useResponseStore();

const localCategory = ref(null);

const props = defineProps({
  aim: String,
  category: Object,
  resource: String,
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
const initializeLocalCategory = () => {
  if (props.aim == "edit" && props.category) {
    localCategory.value = {
      ...props.category,
    };
  } else {
    localCategory.value = {
      name: "",
     
    };
  }
};


const onOpen = async () => {
  initializeLocalCategory();
  responseStore.$reset();
};

const onClose = () => {
  console.log("Dialog closed");
};

const save = async () => {
  const data = await categoryStore.updateCategory(localCategory.value);

  if (data?.success) {
    dialog.value = false;
  }
};

const create = async () => {
  const data = await categoryStore.createCategory(localCategory.value);
  if (data?.success) {
    dialog.value = false;
  }
};
</script>
