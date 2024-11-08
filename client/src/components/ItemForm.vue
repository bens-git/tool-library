<template>
  <v-card>
    <v-card-title>{{ isEdit ? 'Edit Item' : 'Create New Item' }}</v-card-title>
    <v-card-text>

      <v-autocomplete density="compact" v-model="localItem.type_id" :items="typeStore.allTypes" label="Item Type"
        item-title="name" item-value="id" :rules="[typeRules]" :error-messages="typeErrors" hint="e.g., soldering iron"
        :persistent-hint="true"></v-autocomplete><br>

      <v-autocomplete density="compact" v-model="localItem.brand_id" :items="brandStore.brands" label="Brand" clearable
        item-title="name" item-value="id" :rules="[typeRules]" :error-messages="brandErrors"></v-autocomplete>


      <v-textarea density="compact" v-model="localItem.description" label="Description"
        placeholder="e.g., this soldering iron is like no other in the collection. Its handle is worn smooth from years of use, and faint scorch marks trace stories of intricate repairs and ambitious builds. It’s storied and irreplaceable."></v-textarea>

      <v-text-field density="compact" v-model="localItem.serial" label="Serial"></v-text-field>
      <v-text-field density="compact" v-model="localItem.purchase_value" label="Purchase Value" type="number"
        :rules="[valueRules]" :error-messages="purchaseValueErrors"></v-text-field>

      <v-date-input density="compact" v-model="localItem.purchased_at" label="Purchase Date" prepend-icon=""
        persistent-placeholder></v-date-input>

      <v-date-input density="compact" v-model="localItem.manufactured_at" label="Manufacture Date" prepend-icon=""
        persistent-placeholder></v-date-input>



      <div v-if="localItem.images && localItem.images.length">
        <v-row>
          <v-col v-for="(image, index) in localItem.images" :key="index" cols="4">
            <v-img :src="fullImageUrl(image.path)" class="mb-2" aspect-ratio="1">
              <v-btn icon color="red" @click="removeImage(index)" class="mt-2">
                <v-icon>mdi-delete</v-icon>
              </v-btn>
            </v-img>
          </v-col>
        </v-row>
      </div>

      <v-file-input density="compact" @change="handleFileChange" label="Upload Image" prepend-icon="mdi-camera"
        accept="image/*" multiple></v-file-input>
    </v-card-text>
    <v-card-actions>
      <v-btn color="primary" @click="saveItem">{{ isEdit ? 'Update' : 'Create' }}</v-btn>
      <v-btn text @click="closeModal">Cancel</v-btn>
    </v-card-actions>
  </v-card>
</template>
<script setup>
import { ref, watch, onMounted } from 'vue';
import { useTypeStore } from '@/stores/type';
import { useItemStore } from '@/stores/item';
import { useBrandStore } from '@/stores/brand';
import { useResponseStore } from '@/stores/response';
import { format } from 'date-fns'; // Import the format function
const testDate = new Date();

const itemStore = useItemStore();
const typeStore = useTypeStore();
const brandStore = useBrandStore();

const apiBaseUrl = process.env.VUE_APP_API_HOST;

// Initialize localItem without newImages and removedImages



const newImages = ref([]);
const removedImages = ref([]);


onMounted(() => {
  typeStore.fetchAllTypes();
  brandStore.fetchBrands();
});


const props = defineProps({
  showItemModal: Boolean,
  isEdit: Boolean,
  item: Object,
});

const emit = defineEmits(['update-item', 'create-item', 'update:showItemModal', 'close-modal']);

watch(() => props.item, (newItem) => {
  localItem.value = { ...newItem, images: [...(newItem.images || [])] };
}, { deep: true });


const localItem = ref({
  ...props.item,

});

const valueRules = [v => !!v || 'Purchase Value is required'];
const typeRules = [v => !!v || 'Item Type is required'];
const brandRules = [];

const purchaseValueErrors = ref([]);
const typeErrors = ref([]);
const brandErrors = ref([]);

const saveItem = async () => {
  purchaseValueErrors.value = valueRules.map(rule => rule(localItem.value.purchase_value)).filter(error => error !== true);
  typeErrors.value = typeRules.map(rule => rule(localItem.value.type_id)).filter(error => error !== true);
  brandErrors.value = brandRules.map(rule => rule(localItem.value.brand_id)).filter(error => error !== true);

  if (purchaseValueErrors.value.length === 0 && typeErrors.value.length === 0 && brandErrors.value.length === 0) {
    const responseStore = useResponseStore();
    const formData = new FormData();

    // Format dates and append all properties of localItem except images handling
    for (const [key, value] of Object.entries(localItem.value)) {
      if (key === 'purchased_at' || key === 'manufactured_at') {
        // Ensure value is a valid date
        const dateValue = value ? new Date(value) : null;
        const formattedDate = dateValue && !isNaN(dateValue.getTime()) ? format(dateValue, 'yyyy-MM-dd') : '';
        formData.append(key, formattedDate);
      } else {
        // Append all other fields
        formData.append(key, value === null || value === undefined ? '' : value);
      }
    }

    // Append new images
    if (newImages.value.length > 0) {
      newImages.value.forEach((file, index) => {
        formData.append(`newImages[${index}]`, file);
      });
    }

    // Append removed images
    if (removedImages.value.length > 0) {
      removedImages.value.forEach((id) => {
        formData.append('removedImages[]', id);
      });
    }

    try {
      if (props.isEdit) {
        await itemStore.updateItem(formData);
      } else {
        await itemStore.createItem(formData);
      }

      if (responseStore.response.success) {
        closeModal();
        itemStore.fetchUserItems();
      } else {
        console.log('Error:', responseStore.response.message);
      }
    } catch (error) {
      console.error('Unexpected Error:', error);
      responseStore.setResponse(false, error.response?.data?.message || 'An unexpected error occurred', [error.response?.data?.errors || []]);
    }
  }
};

const fullImageUrl = (imagePath) => {
  return `https://${apiBaseUrl}/${imagePath}`;
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
  emit('close-modal');
};
</script>
