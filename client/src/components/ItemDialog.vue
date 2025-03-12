<template>
  <div class="pl-4 text-center">
    <v-dialog v-model="dialog" @open="onOpen">
      <template v-slot:activator="{ props: activatorProps }">
        <v-btn
          :color="aim == 'edit' ? 'primary' : 'success'"
          class="text-none font-weight-regular"
          :prepend-icon="aim == 'edit' ? 'mdi-pencil' : 'mdi-plus'"
          :text="aim == 'edit' ? 'Edit Item' : 'Create Item'"
          variant="tonal"
          block
          v-bind="activatorProps"
        ></v-btn>
      </template>
      <v-card
        v-if="localItem"
        :prepend-icon="aim == 'edit' ? 'mdi-pencil' : 'mdi-plus'"
        :title="aim == 'edit' ? 'Edit Item' : 'Create Item'"
        :subtitle="localItem?.code"
      >
        <v-card-text>
          <v-autocomplete
            density="compact"
            v-model="localItem.archetype"
            :items="autocompleteArchetypes"
            label="Select an archetype"
            item-title="name"
            item-value="id"
            hide-no-data
            :return-object="true"
            @update:search="debouncedAutocompleteArchetypeSearch"
            :error-messages="responseStore.response?.errors?.['archetype.id']"
          ></v-autocomplete>
          <br />

          <v-autocomplete
            density="compact"
            v-model="localItem.brand"
            :items="autocompleteBrands"
            label="Select a brand"
            clearable
            item-title="name"
            item-value="id"
            hide-no-data
            hide-details
            :return-object="true"
            @update:search="debouncedAutocompleteBrandSearch"
          ></v-autocomplete>

          <v-textarea
            density="compact"
            v-model="localItem.description"
            label="Description"
            placeholder="e.g., this soldering iron is like no other in the collection. Its handle is worn smooth from years of use, and faint scorch marks trace stories of intricate repairs and ambitious builds. It’s storied and irreplaceable."
          ></v-textarea>

          <v-text-field
            density="compact"
            v-model="localItem.serial"
            label="Serial"
          ></v-text-field>

          <v-text-field
            density="compact"
            v-model="localItem.purchase_value"
            label="Purchase Value"
            type="number"
            :error-messages="responseStore.response?.errors?.purchase_value"
          ></v-text-field>

          <v-date-input
            density="compact"
            :disabled="true"
            v-model="localItem.created_at"
            label="Created At"
            prepend-icon=""
            persistent-placeholder
            :error-messages="responseStore.response?.errors?.purchased_at"
          ></v-date-input>

          <v-date-input
            density="compact"
            v-model="localItem.manufactured_at"
            label="Manufactured At"
            prepend-icon=""
            persistent-placeholder
          ></v-date-input>
          <div v-if="localItem.images && localItem.images.length">
            <v-row>
              <v-col
                v-for="(image, index) in localItem.images"
                :key="index"
                cols="4"
              >
                <v-img
                  :src="fullImageUrl(image.path)"
                  class="mb-2"
                  aspect-ratio="1"
                >
                  <v-btn
                    icon
                    color="red"
                    @click="removeImage(index)"
                    class="mt-2"
                  >
                    <v-icon>mdi-delete</v-icon>
                  </v-btn>
                </v-img>
              </v-col>
            </v-row>
          </div>

          <v-file-input
            density="compact"
            @change="handleFileChange"
            label="Upload Image"
            prepend-icon="mdi-camera"
            accept="image/*"
            multiple
          ></v-file-input>
        </v-card-text>
        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>

          <v-btn text="Cancel" variant="plain" @click="dialog = false"></v-btn>

          <v-btn
            v-if="aim == 'edit'"
            color="success"
            text="Save"
            variant="tonal"
            @click="saveItem"
          ></v-btn>

          <v-btn
            v-if="aim == 'create'"
            color="success"
            text="Create"
            variant="tonal"
            @click="createItem"
          ></v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
<script setup>
import { shallowRef, ref, watch } from "vue";
import { useItemStore } from "@/stores/item";
import { useArchetypeStore } from "@/stores/archetype";
import { useBrandStore } from "@/stores/brand";
import { useResponseStore } from "@/stores/response";
import _ from "lodash";
import useApi from "@/stores/api";

const { fullImageUrl } = useApi();

const dialog = shallowRef(false);

const itemStore = useItemStore();
const archetypeStore = useArchetypeStore();
const brandStore = useBrandStore();
const responseStore = useResponseStore();

const localItem = ref(null);
const autocompleteArchetypes = ref([]);
const autocompleteBrands = ref([]);
const newImages = ref([]);
const removedImages = ref([]);

const props = defineProps({
  item: Object,
  aim: String,
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
  localItem.value = {
    ...props.item,
    created_at: props.item?.created_at || new Date().toISOString(),
  };
};

// const emit = defineEmits(["update:modelValue", "close"]);

const onOpen = async () => {
  initialize();
  responseStore.$reset();
  autocompleteArchetypes.value =
    await archetypeStore.fetchAutocompleteArchetypes();
  autocompleteBrands.value = await brandStore.fetchAutocompleteSelectBrands();
};

const onClose = () => {};

const createItem = async () => {
  const data = await itemStore.createItem(localItem.value);
  if (data?.success && data.data.id) {
    localItem.value = data.data;

    //add new images
    for (const image of newImages.value) {
      await itemStore.addMyItemImage(localItem.value.id, image);
    }
    await itemStore.fetchMyItems();

    dialog.value = false;
  }
};

const saveItem = async () => {
  const data = await itemStore.updateMyItem(localItem.value);

  //add new images
  if (data?.success) {
    for (const image of newImages.value) {
      await itemStore.addMyItemImage(localItem.value.id, image);
    }
    await itemStore.fetchMyItems();

    dialog.value = false;
  }
};

// Autocomplete Archetype Search handler
const onAutocompleteArchetypeSearch = async (query) => {
  autocompleteArchetypes.value =
    await archetypeStore.fetchAutocompleteArchetypes(query);
};

// Autocomplete brand Search handler
const onAutocompleteBrandSearch = async (query) => {
  autocompleteBrands.value =
    await brandStore.fetchAutocompleteSelectBrands(query);
};

// Debounced search function
const debouncedAutocompleteArchetypeSearch = _.debounce(
  onAutocompleteArchetypeSearch,
  300
);
const debouncedAutocompleteBrandSearch = _.debounce(
  onAutocompleteBrandSearch,
  300
);

const handleFileChange = (event) => {
  const files = event.target.files;
  if (files.length) {
    newImages.value.push(...Array.from(files));
  }
};

const removeImage = (index) => {
  if (index >= 0 && index < localItem.value.images.length) {
    const removedImage = localItem.value.images.splice(index, 1)[0];
    if (removedImage && removedImage.id) {
      removedImages.value.push(removedImage.id);
    }
  }
};
</script>
