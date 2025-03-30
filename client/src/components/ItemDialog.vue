<template>
  <v-dialog v-model="dialog" @open="onOpen">
    <template v-slot:activator="{ props: activatorProps }">
      <v-btn
        :color="aim == 'edit' || aim == 'view' ? 'primary' : 'success'"
        :prepend-icon="
          aim == 'edit' ? 'mdi-pencil' : aim == 'view' ? 'mdi-eye' : 'mdi-plus'
        "
        :text="
          aim == 'edit'
            ? 'Edit Item'
            : aim == 'view'
              ? 'View Item'
              : 'Create Item'
        "
        variant="tonal"
        block
        v-bind="activatorProps"
        size="small"
      ></v-btn>
    </template>
    <v-card
      v-if="localItem"
      :prepend-icon="
        aim == 'edit' ? 'mdi-pencil' : aim == 'view' ? 'mdi-eye' : 'mdi-plus'
      "
      :title="
        aim == 'edit'
          ? 'Edit Item'
          : aim == 'view'
            ? 'View Item'
            : 'Create Item'
      "
      :subtitle="localItem?.code"
    >
      <v-card-text>
        <v-autocomplete
          density="compact"
          v-model="localItem.archetype"
          :items="autocompleteArchetypes"
          label="Archetype"
          item-title="name"
          item-value="id"
          hide-no-data
          :return-object="true"
          :readonly="aim == 'view'"
          @update:search="debouncedAutocompleteArchetypeSearch"
          :error-messages="responseStore.response?.errors?.['archetype.id']"
        ></v-autocomplete>

        <ArchetypeDialog
          aim="edit"
          :archetype="localItem.archetype"
          v-if="localItem.archetype?.created_by == userStore.user.id"
        />
        <br v-if="localItem.archetype?.created_by == userStore.user.id" />

        <v-autocomplete
          density="compact"
          v-model="localItem.brand"
          :items="autocompleteBrands"
          label="Brand"
          clearable
          item-title="name"
          item-value="id"
          hide-no-data
          hide-details
          :return-object="true"
          :readonly="aim == 'view'"
          @update:search="debouncedAutocompleteBrandSearch"
        ></v-autocomplete>
        <br />

        <v-textarea
          density="compact"
          v-model="localItem.description"
          :readonly="aim == 'view'"
          label="Description"
          placeholder="e.g., this soldering iron is like no other in the collection. Its handle is worn smooth from years of use, and faint scorch marks trace stories of intricate repairs and ambitious builds. It’s storied and irreplaceable."
        ></v-textarea>

        <v-checkbox
          v-model="localItem.make_item_unavailable"
          label="Make item unavailable"
          density="compact"
          :true-value="1"
          :false-value="0"
        ></v-checkbox>

        <v-text-field
          density="compact"
          v-model="localItem.serial"
          label="Serial"
          :readonly="aim == 'view'"
        ></v-text-field>

        <v-text-field
          density="compact"
          v-model="localItem.purchase_value"
          label="Purchase Value"
          type="number"
          :readonly="aim == 'view'"
          :error-messages="responseStore.response?.errors?.purchase_value"
        ></v-text-field>

        <v-date-input
          density="compact"
          :disabled="true"
          :readonly="aim == 'view'"
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
          :readonly="aim == 'view'"
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
                  v-if="aim != 'view'"
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
          v-if="aim != 'view'"
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
        <RentalDatesDialog :item="localItem" v-if="aim == 'view'" />
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
<script setup>
import { shallowRef, ref, watch } from "vue";
import { useItemStore } from "@/stores/item";
import { useArchetypeStore } from "@/stores/archetype";
import { useBrandStore } from "@/stores/brand";
import { useResponseStore } from "@/stores/response";
import { useUserStore } from "@/stores/user";
import _ from "lodash";
import useApi from "@/stores/api";
import ArchetypeDialog from "./ArchetypeDialog.vue";
import RentalDatesDialog from "./RentalDatesDialog.vue";

const { fullImageUrl } = useApi();

const dialog = shallowRef(false);

const itemStore = useItemStore();
const archetypeStore = useArchetypeStore();
const brandStore = useBrandStore();
const responseStore = useResponseStore();
const userStore = useUserStore();

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

const refreshLocalItem = async () => {
  localItem.value = await itemStore.show(props.item.id);
};

// Function to initialize
const initialize = () => {
  if (props.aim == "edit" && props.item) {
    refreshLocalItem();
  } else {
    localItem.value = {
      archetype: itemStore.itemListFilters.archetype
        ? itemStore.itemListFilters.archetype
        : null,
      created_at: props.item?.created_at || new Date().toISOString(),
    };
  }
};

const onOpen = async () => {
  initialize();
  newImages.value=[]
  responseStore.$reset();
  autocompleteArchetypes.value = await archetypeStore.indexForAutocomplete();
  autocompleteBrands.value = await brandStore.indexForAutocomplete();
};

const onClose = () => {};

const createItem = async () => {
  const data = await itemStore.store(localItem.value);
  if (data?.success && data.data.id) {
    localItem.value = data.data;

    //add new images
    for (const image of newImages.value) {
      await itemStore.addMyItemImage(localItem.value.id, image);
    }
    await itemStore.index();

    dialog.value = false;
  }
};

const saveItem = async () => {
  const data = await itemStore.update(localItem.value);

  //add new images
  if (data?.success) {
    for (const image of newImages.value) {
      //console.log(image)
      await itemStore.addMyItemImage(localItem.value.id, image);
    }
    await itemStore.index();

    dialog.value = false;
  }
};

// Autocomplete Archetype Search handler
const onAutocompleteArchetypeSearch = async (query) => {
  autocompleteArchetypes.value =
    await archetypeStore.indexForAutocomplete(query);
};

// Autocomplete brand Search handler
const onAutocompleteBrandSearch = async (query) => {
  autocompleteBrands.value = await brandStore.indexForAutocomplete(query);
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
