<template>

  <v-card>
    <v-card-title>{{ isEdit ? 'Edit Type' : 'Create New Category' }}</v-card-title>
    <v-card-text>
      <v-text-field v-model="localCategory.name" :error-messages="responseStore?.response?.errors[0]?.name" label="Name" 
       ></v-text-field>
   

    </v-card-text>
    <v-card-actions>
      <v-btn color="primary" @click="saveCategory">{{ isEdit ? 'Update' : 'Create' }}</v-btn>
      <v-btn text @click="closeModal">Cancel</v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useResponseStore } from '@/stores/response';
import { useCategoryStore } from '@/stores/category';

const categoryStore = useCategoryStore();
const responseStore = useResponseStore();

// Define props
const props = defineProps({
  showCategoryModal: Boolean,
  isEdit: Boolean,
  category: Object,
});

const emit = defineEmits(['update-category', 'create-category', 'update:showCategoryModal', 'close-modal']);

watch(() => props.category, (newCategory) => {
  localCategory.value = { ...newCategory };
}, { deep: true });

const localCategory = ref({ ...props.category });



const saveCategory = async () => {

    const responseStore = useResponseStore();
    const formData = new FormData();

    // Append all properties of local type except images handling
    for (const [key, value] of Object.entries(localCategory.value)) {
      // Skip keys with null values or replace them with actual null
      if (value === null || value === undefined) {
        formData.append(key, '');
      } else {
        formData.append(key, value);
      }
    }

   
    try {
      if (props.isEdit) {
        await categoryStore.updateCategory(formData);
      } else {
        await categoryStore.createCategory(formData);
      }
      if (responseStore.response.success) {
        closeModal();
        categoryStore.fetchUserCategories();
      } else {
        console.log('Error:', responseStore.response.message);
      }
    } catch (error) {
      console.error('Unexpected Error:', error);
      responseStore.setResponse(false, error.response.data.message, [error.response.data.errors]);
    }
  
}



// Close modal logic
const closeModal = () => {
  emit('close-modal');
};
</script>

