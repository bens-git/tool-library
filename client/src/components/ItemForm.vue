<template>
  <v-card>
    <v-card-title>{{ isEdit ? "Edit Item" : "Create New Item" }}</v-card-title>
    <v-card-text>
      <v-autocomplete
        density="compact"
        v-model="localItem.type"
        :items="autocompleteTypes"
        label="Select a type"
        item-title="name"
        item-value="id"
        hide-no-data
        :return-object="true"
        @update:search="debouncedAutocompleteTypeSearch"
        :error-messages="responseStore.response?.errors?.type"
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
        v-model="localItem.purchased_at"
        label="Purchased At"
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
              <v-btn icon color="red" @click="removeImage(index)" class="mt-2">
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
    <v-card-actions>
      <v-btn color="primary" @click="saveItem">{{
        isEdit ? "Update" : "Create"
      }}</v-btn>
      <v-btn text @click="closeModal">Cancel</v-btn>
    </v-card-actions>
  </v-card>
</template>
<script setup>
import { ref, watch, onMounted } from "vue";
import { useTypeStore } from "@/stores/type";
import { useItemStore } from "@/stores/item";
import { useBrandStore } from "@/stores/brand";
import { useResponseStore } from "@/stores/response";
import useApi from "@/stores/api";
import debounce from "lodash/debounce";

const itemStore = useItemStore();
const typeStore = useTypeStore();
const brandStore = useBrandStore();
const responseStore = useResponseStore();

const apiBaseUrl = process.env.VUE_APP_API_HOST;

const newImages = ref([]);
const removedImages = ref([]);
const types = ref([]);
const autocompleteTypes = ref([]);
const autocompleteBrands = ref([]);
const { fullImageUrl } = useApi();

// Fetch initial types
const fetchInitialTypes = async () => {
  const initialTypes = await typeStore.fetchAutocompleteSelectTypes();
  types.value = initialTypes;
};

// Autocomplete Type Search handler
const onAutocompleteTypeSearch = async (query) => {
  autocompleteTypes.value = await typeStore.fetchAutocompleteSelectTypes(query);
};

// Autocomplete Brand Search handler
const onAutocompleteBrandSearch = async (query) => {
  autocompleteBrands.value =
    await brandStore.fetchAutocompleteSelectBrands(query);
};

// Debounced search function
const debouncedAutocompleteTypeSearch = debounce(onAutocompleteTypeSearch, 300);
const debouncedAutocompleteBrandSearch = debounce(
  onAutocompleteBrandSearch,
  300
);

const props = defineProps({
  showItemModal: Boolean,
  isEdit: Boolean,
  item: Object,
});

const emit = defineEmits([
  "update-item",
  "create-item",
  "update:showItemModal",
  "close-modal",
]);

const localItem = ref({});

// Function to initialize
const initializeLocalItem = () => {
  if (props.isEdit && props.item) {
    localItem.value = {
      ...props.item,
      images: [...(props.item.images || [])], // Handle images array if exists
    };
  } else {
    localItem.value = {
      type_id: null,
      brand_id: null,
      description: null,
      serial: null,
      purchase_value: null,
      purchased_at: new Date(),
      manufactured_at: null,
      code: null,
    };
  }
};

// Initialize localType on component mount or when the type changes
onMounted(() => {
  initializeLocalItem();
});

watch(
  () => props.item,
  () => {
    initializeLocalItem();
  },
  { deep: true }
);

const saveItem = async () => {
  if (props.isEdit) {
    await itemStore.updateMyItem(localItem.value);
  } else {
    const newItem = await itemStore.createItem(localItem.value);
    if (newItem && newItem.id) {
      localItem.value = newItem;
    }
  }

  //add new images
  if (localItem.value.id) {
    for (const image of newImages.value) {
      await itemStore.addMyItemImage(localItem.value.id, image);
    }
  }

  if (responseStore.response.success) {
    closeModal();
    itemStore.fetchMyItems();
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

const handleFileChange = (event) => {
  const files = event.target.files;
  if (files.length) {
    newImages.value.push(...Array.from(files));
  }
};

const closeModal = () => {
  emit("close-modal");
};
</script>
