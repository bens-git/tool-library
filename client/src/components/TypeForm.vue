<template>
  <v-card>
    <v-card-title>{{ isEdit ? 'Edit Type' : 'Create New Type' }}</v-card-title>
    <v-card-text>
      <!-- Form Inputs for Creating/Updating a Type -->
      <v-text-field v-model="localType.name" :error-messages="responseStore?.response?.errors[0]?.name" label="Name" />
      <v-textarea v-model="localType.description" label="Description"></v-textarea>
      <v-textarea v-model="localType.notes" label="Notes"></v-textarea>
      <v-text-field v-model="localType.code" label="Code"></v-text-field>

      <v-autocomplete :multiple=true v-model="localType.category_ids" :items="categoryStore.categories" label="Category"
        item-title="name" item-value="id"
        :error-messages="responseStore?.response?.errors[0]?.category_ids"></v-autocomplete>

      <v-autocomplete :multiple=true v-model="localType.usage_ids" :items="usageStore.usages" label="Usage"
        item-title="name" item-value="id"
        :error-messages="responseStore?.response?.errors[0]?.usage_ids"></v-autocomplete>

      <div v-if="localType.images && localType.images.length">
        <v-row>
          <v-col v-for="(image, index) in localType.images" :key="index" cols="4">
            <v-img :src="fullImageUrl(image.path)" class="mb-2" aspect-ratio="1">
              <v-btn icon color="red" @click="removeImage(index)" class="mt-2">
                <v-icon>mdi-delete</v-icon>
              </v-btn>
            </v-img>
          </v-col>
        </v-row>
      </div>

      <v-file-input @change="handleFileChange" label="Upload Image" prepend-icon="mdi-camera" accept="image/*"
        multiple></v-file-input>
    </v-card-text>
    <v-card-actions>
      <v-btn color="primary" @click="saveType">{{ isEdit ? 'Update' : 'Create' }}</v-btn>
      <v-btn text @click="closeModal">Cancel</v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useResponseStore } from '@/stores/response';
import { useTypeStore } from '@/stores/type';
import { useCategoryStore } from '@/stores/category';
import { useUsageStore } from '@/stores/usage';

const typeStore = useTypeStore();
const categoryStore = useCategoryStore();
const usageStore = useUsageStore();
const responseStore = useResponseStore();

const apiBaseUrl = process.env.VUE_APP_API_HOST;

const newImages = ref([]);
const removedImages = ref([]);




// Define props
const props = defineProps({
  showTypeModal: Boolean,
  isEdit: Boolean,
  type: Object,
});

const emit = defineEmits(['update-type', 'create-type', 'update:showTypeModal', 'close-modal']);


const localType = ref({});

// Function to initialize localType
const initializeLocalType = () => {
  if (props.isEdit && props.type) {
    localType.value = {
      ...props.type,
      images: [...(props.type.images || [])], // Handle images array if exists
      category_ids: props.type.category_ids || [], // Default to empty array if undefined
      usage_ids: props.type.usage_ids || [], // Default to empty array if undefined
    };
  } else {
    localType.value = {
      name: '',
      description: '',
      notes: '',
      code: '',
      category_ids: [], // Default to empty array for new type
      usage_ids: [], // Default to empty array for new type
      images: [], // Initialize images as an empty array
    };
  }
};

// Initialize localType on component mount or when the type changes
onMounted(() => {
  initializeLocalType();
  categoryStore.fetchCategories();
  usageStore.fetchUsages();
});

watch(() => props.type, () => {
  initializeLocalType();
}, { deep: true });




const saveType = async () => {



  const responseStore = useResponseStore();
  const formData = new FormData();

  // Append all properties of local type except images handling
  for (const [key, value] of Object.entries(localType.value)) {
    // Skip keys with null values or replace them with actual null
    if (value === null || value === undefined) {
      formData.append(key, '');
    } else {
      formData.append(key, value);
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
      await typeStore.updateType(formData);
    } else {
      await typeStore.createType(formData);
    }
    if (responseStore.response.success) {
      closeModal();
      typeStore.fetchUserTypes();
    } else {
      console.log('Error:', responseStore.response.message);
    }
  } catch (error) {
    console.error('Unexpected Error:', error);
    responseStore.setResponse(false, error.response.data.message, [error.response.data.errors]);
  }

}


const fullImageUrl = (imagePath) => {
  return `https://${apiBaseUrl}/${imagePath}`;
};

const removeImage = (index) => {
  if (index >= 0 && index < localType.value.images.length) {
    const removedImage = localType.value.images.splice(index, 1)[0];
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

// Close modal logic
const closeModal = () => {
  emit('close-modal');
};
</script>

<style scoped>
/* Add any styles specific to this component here */
</style>
