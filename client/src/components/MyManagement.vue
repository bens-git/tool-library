<template>
  <v-container class="d-flex justify-center">
    <v-card title="Management" flat style="min-width:90vw; min-height:90vh">
      <v-tabs v-model="activeTab" grow>
        <v-tab>My Items</v-tab>
        <v-tab>My Types</v-tab>
        <v-tab>My Categories</v-tab>
        <v-tab>My Usages</v-tab>
        <v-tab>My Brands</v-tab>
      </v-tabs>

      <!-- My Items Tab -->
      <div v-if="activeTab === 0">
        <v-card>
          <v-card-title>
            My Items
            <v-btn class="ms-auto" @click="[isEdit = false, selectedItem = null, showItemModal = true]" color="primary"
              dark>Create
              Item</v-btn>
          </v-card-title>
          <v-card-subtitle>
            Manage individual units of tools—each item is a specific instance of a tool type, like two 'Mitre Saws'
            bought at different times.
          </v-card-subtitle>
          <v-card-text>
            <v-row>
              <v-col cols="4" md="4">
                <v-text-field density="compact" v-model="itemStore.search" label="Search"
                  prepend-inner-icon="mdi-magnify" variant="outlined" hide-details single-line
                  @input="debounceSearch('items')"></v-text-field>
              </v-col>
              <v-col cols="4" md="4">
                <v-select density="compact" v-model="typeStore.selectedTypeId" :items="typeStore.allTypes" label="Type"
                  item-title="name" item-value="id" :clearable="true" @update:modelValue="debounceSearch('items')" />
              </v-col>
              <v-col cols="4" md="4">
                <v-select density="compact" v-model="typeStore.selectedBrandId" :items="brandStore.brands" label="Brand"
                  item-title="name" item-value="id" :clearable="true" @update:modelValue="debounceSearch('items')" />
              </v-col>

            </v-row>

            <v-data-table-server :search="itemStore.search" v-model:items-per-page="itemStore.itemsPerPage"
              :items-length="itemStore.totalItems" :headers="itemHeaders" @update:options="itemStore.updateOptions"
              :items="itemStore.items" item-value="name" mobile-breakpoint="sm">
              <template v-slot:[`item.actions`]="{ item }">
                <v-icon v-if="userStore.user && (item.owned_by == userStore.user.id)" class="me-2" size="small" @click="() => {
                  selectedItem = item;
                  showAvailabilityModal = true;
                }">
                  mdi-calendar
                </v-icon>
                <v-icon v-if="userStore.user && (item.owned_by == userStore.user.id)" class="me-2" size="small" @click="() => {
                  isEdit = true;
                  selectedItem = item;
                  showItemModal = true;
                }">
                  mdi-pencil
                </v-icon>
                <v-icon v-if="userStore.user && (item.owned_by === userStore.user.id)" size="small"
                  @click="confirmDeleteItem(item)">
                  mdi-delete
                </v-icon>
                <v-dialog v-model="isDeleteItemDialogVisible" max-width="400">
                  <v-card>
                    <v-card-title class="headline">Confirm Deletion</v-card-title>
                    <v-card-text>Are you sure you want to delete this item?</v-card-text>
                    <v-card-actions>
                      <v-btn color="primary" text @click="isDeleteItemDialogVisible = false">Cancel</v-btn>
                      <v-btn color="red" text @click="deleteItem(item)">Delete</v-btn>
                    </v-card-actions>
                  </v-card>
                </v-dialog>
              </template>
            </v-data-table-server>
          </v-card-text>
        </v-card>
      </div>

      <!-- My Types Tab -->
      <div v-if="activeTab === 1">
        <v-card>
          <v-card-title>
            My Types
            <v-btn class="ms-auto" @click="[isEdit = false, selectedType = null, showTypeModal = true]" color="primary"
              dark>Create
              Type</v-btn>
          </v-card-title>

          <v-card-subtitle>
            Define tool types, such as 'Mitre Saw' or 'Hammer,' to group similar tools in your inventory
          </v-card-subtitle>


          <v-card-text>
            <v-text-field v-model="typeStore.search" label="Search" prepend-inner-icon="mdi-magnify" variant="outlined"
              hide-details single-line @input="debounceSearch('types')"></v-text-field>
            <v-data-table-server @update:options="typeStore.updateUserTypeOptions"
              v-model:items-per-page="typeStore.itemsPerPage" :headers="typeHeaders" :items="typeStore.userTypes"
              :items-length="typeStore.totalUserTypes" item-value="name" mobile-breakpoint="sm">
              <template v-slot:[`item.actions`]="{ item }">
                <v-icon v-if="userStore.user && (item.created_by == userStore.user.id)" class="me-2" size="small"
                  @click="() => {
                    isEdit = true;
                    selectedType = item;
                    showTypeModal = true;
                  }">
                  mdi-pencil
                </v-icon>
                <v-icon v-if="userStore.user && (item.created_by === userStore.user.id)" size="small"
                  @click="confirmDeleteType(item)">
                  mdi-delete
                </v-icon>
                <v-dialog v-model="isDeleteTypeDialogVisible" max-width="400">
                  <v-card>
                    <v-card-title class="headline">Confirm Deletion</v-card-title>
                    <v-card-text>Are you sure you want to delete this type?</v-card-text>
                    <v-card-actions>
                      <v-btn color="primary" text @click="isDeleteTypeDialogVisible = false">Cancel</v-btn>
                      <v-btn color="red" text @click="deleteType(item)">Delete</v-btn>
                    </v-card-actions>
                  </v-card>
                </v-dialog>
              </template>
            </v-data-table-server>
          </v-card-text>
        </v-card>
      </div>

      <!-- My Categories Tab -->
      <div v-if="activeTab === 2">
        <v-card>
          <v-card-title>
            My Categories
            <v-btn class="ms-auto" @click="[isEdit = false, selectedCategory = null, showCategoryModal = true]"
              color="primary" dark>Create
              Category</v-btn>
          </v-card-title>
          <v-card-text>
            <v-text-field v-model="categoryStore.search" label="Search" prepend-inner-icon="mdi-magnify"
              variant="outlined" hide-details single-line @input="debounceSearch('categories')"></v-text-field>
            <v-data-table-server @update:options="categoryStore.updateUserCategoriesOptions"
              v-model:items-per-page="categoryStore.itemsPerPage" :headers="categoryHeaders"
              :items="categoryStore.userCategories" :items-length="categoryStore.totalUserCategories" item-value="name"
              mobile-breakpoint="sm">
              <template v-slot:[`item.actions`]="{ item }">

                <v-icon v-if="userStore.user && (item.created_by == userStore.user.id)" class="me-2" size="small"
                  @click="() => {
                    isEdit = true;
                    selectedCategory = item;
                    showCategoryModal = true;
                  }">
                  mdi-pencil
                </v-icon>
                <v-icon v-if="userStore.user && (item.created_by === userStore.user.id)" size="small"
                  @click="confirmDeleteCategory(item)">
                  mdi-delete
                </v-icon>
                <v-dialog v-model="isDeleteCategoryDialogVisible" max-width="400">
                  <v-card>
                    <v-card-title class="headline">Confirm Deletion</v-card-title>
                    <v-card-text>Are you sure you want to delete this category?</v-card-text>
                    <v-card-actions>
                      <v-btn color="primary" text @click="isDeleteCategoryDialogVisible = false">Cancel</v-btn>
                      <v-btn color="red" text @click="deleteCategory(item)">Delete</v-btn>
                    </v-card-actions>
                  </v-card>
                </v-dialog>
              </template>
            </v-data-table-server>
          </v-card-text>
        </v-card>
      </div>

      <!-- My Usages Tab -->
      <div v-if="activeTab === 3">
        <v-card>
          <v-card-title>
            My Usages
            <v-btn class="ms-auto" @click="[isEdit = false, selectedUsage = null, showUsageModal = true]"
              color="primary" dark>Create
              Usage</v-btn>
          </v-card-title>
          <v-card-text>
            <v-text-field v-model="usageStore.search" label="Search" prepend-inner-icon="mdi-magnify" variant="outlined"
              hide-details single-line @input="debounceSearch('usages')"></v-text-field>
            <v-data-table-server @update:options="usageStore.updateUserUsagesOptions"
              v-model:items-per-page="usageStore.itemsPerPage" :headers="usageHeaders" :items="usageStore.userUsages"
              :items-length="usageStore.totalUserUsages" item-value="name" mobile-breakpoint="sm">
              <template v-slot:[`item.actions`]="{ item }">

                <v-icon v-if="userStore.user && (item.created_by == userStore.user.id)" class="me-2" size="small"
                  @click="() => {
                    isEdit = true;
                    selectedUsage = item;
                    showUsageModal = true;
                  }">
                  mdi-pencil
                </v-icon>

                <v-icon v-if="userStore.user && (item.created_by === userStore.user.id)" size="small"
                  @click="confirmDeleteUsage(item)">
                  mdi-delete
                </v-icon>
                <v-dialog v-model="isDeleteUsageDialogVisible" max-width="400">
                  <v-card>
                    <v-card-title class="headline">Confirm Deletion</v-card-title>
                    <v-card-text>Are you sure you want to delete this usage?</v-card-text>
                    <v-card-actions>
                      <v-btn color="primary" text @click="isDeleteUsageDialogVisible = false">Cancel</v-btn>
                      <v-btn color="red" text @click="deleteUsage(item)">Delete</v-btn>
                    </v-card-actions>
                  </v-card>
                </v-dialog>
              </template>
            </v-data-table-server>
          </v-card-text>
        </v-card>
      </div>

      <!-- My Brands -->
      <div v-if="activeTab === 4">
        <v-card>
          <v-card-title>
            My Brands
            <v-btn class="ms-auto" @click="[isEdit = false, selectedBrand = null, showBrandModal = true]"
              color="primary" dark>Create
              Brand</v-btn>
          </v-card-title>
          <v-card-text>
            <v-text-field v-model="brandStore.search" label="Search" prepend-inner-icon="mdi-magnify" variant="outlined"
              hide-details single-line @input="debounceSearch('brands')"></v-text-field>
            <v-data-table-server @update:options="brandStore.updateUserBrandsOptions"
              v-model:items-per-page="brandStore.itemsPerPage" :headers="brandHeaders" :items="brandStore.userBrands"
              :items-length="brandStore.totalUserBrands" item-value="name" mobile-breakpoint="sm">
              <template v-slot:[`item.actions`]="{ item }">

                <v-icon v-if="userStore.user && (item.created_by == userStore.user.id)" class="me-2" size="small"
                  @click="() => {
                    isEdit = true;
                    selectedBrand = item;
                    showBrandModal = true;
                  }">
                  mdi-pencil
                </v-icon>

                <v-icon v-if="userStore.user && (item.created_by === userStore.user.id)" size="small"
                  @click="confirmDeleteBrand(item)">
                  mdi-delete
                </v-icon>
                <v-dialog v-model="isDeleteBrandDialogVisible" max-width="400">
                  <v-card>
                    <v-card-title class="headline">Confirm Deletion</v-card-title>
                    <v-card-text>Are you sure you want to delete this brand?</v-card-text>
                    <v-card-actions>
                      <v-btn color="primary" text @click="isDeleteBrandDialogVisible = false">Cancel</v-btn>
                      <v-btn color="red" text @click="deleteBrand(item)">Delete</v-btn>
                    </v-card-actions>
                  </v-card>
                </v-dialog>
              </template>
            </v-data-table-server>
          </v-card-text>
        </v-card>
      </div>

      <v-dialog v-model="showItemModal" max-width="600px">
        <ItemForm :showItemModal="showItemModal" :isEdit="isEdit" :item="selectedItem"
          @close-modal="showItemModal = false" />
      </v-dialog>

      <v-dialog v-model="showAvailabilityModal" max-width="600px">
        <AvailabilityForm :showAvailabilityModal="showAvailabilityModal" :item="selectedItem"
          @close-modal="showAvailabilityModal = false" />
      </v-dialog>

      <v-dialog v-model="showTypeModal" max-width="600px">
        <TypeForm :showTypeModal="showTypeModal" :isEdit="isEdit" :type="selectedType"
          @close-modal="showTypeModal = false" />
      </v-dialog>

      <v-dialog v-model="showCategoryModal" max-width="600px">
        <CategoryForm :showCategoryModal="showCategoryModal" :isEdit="isEdit" :category="selectedCategory"
          @close-modal="showCategoryModal = false" />
      </v-dialog>

      <v-dialog v-model="showUsageModal" max-width="600px">
        <UsageForm :showUsageModal="showUsageModal" :isEdit="isEdit" :usage="selectedUsage"
          @close-modal="showUsageModal = false" />
      </v-dialog>

      <v-dialog v-model="showBrandModal" max-width="600px">
        <BrandForm :showBrandModal="showBrandModal" :isEdit="isEdit" :brand="selectedBrand"
          @close-modal="showBrandModal = false" />
      </v-dialog>





    </v-card>
  </v-container>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useItemStore } from '@/stores/item';
import { useTypeStore } from '@/stores/type';
import { useCategoryStore } from '@/stores/category';
import { useBrandStore } from '@/stores/brand';
import { useUsageStore } from '@/stores/usage';
import { useUserStore } from '@/stores/user';
import debounce from 'lodash/debounce';
import ItemForm from './ItemForm.vue';
import AvailabilityForm from './AvailabilityForm.vue';
import TypeForm from './TypeForm.vue';
import CategoryForm from './CategoryForm.vue';
import UsageForm from './UsageForm.vue';
import BrandForm from './BrandForm.vue';

const itemStore = useItemStore();
const typeStore = useTypeStore();
const categoryStore = useCategoryStore();
const usageStore = useUsageStore();
const brandStore = useBrandStore();
const userStore = useUserStore();

const activeTab = ref(0);
const isEdit = ref('');


const itemHeaders = [
  { title: 'Name', value: 'item_name' },
  { title: 'Type', value: 'type_name' },
  { title: 'Brand', value: 'brand_name' },
  { title: 'Actions', value: 'actions', sortable: false },
];
const typeHeaders = [
  { title: 'Name', value: 'name' },
  {
    title: 'Categories',
    align: 'start',
    sortable: false,
    key: 'categories',
  },
  {
    title: 'Usages',
    align: 'start',
    sortable: false,
    key: 'usages',
  },
  { title: 'Actions', value: 'actions', sortable: false },
];
const categoryHeaders = [
  { title: 'Name', value: 'name' },
  { title: 'Actions', value: 'actions', sortable: false },
];
const usageHeaders = [
  { title: 'Name', value: 'name' },
  { title: 'Actions', value: 'actions', sortable: false },
];

const brandHeaders = [
  { title: 'Name', value: 'name' },
  { title: 'Actions', value: 'actions', sortable: false },
];



const isDeleteItemDialogVisible = ref(false);
const isDeleteTypeDialogVisible = ref(false);
const isDeleteCategoryDialogVisible = ref(false);
const isDeleteUsageDialogVisible = ref(false);
const isDeleteBrandDialogVisible = ref(false);
const selectedItem = ref(null);
const selectedType = ref(null);
const selectedCategory = ref(null);
const selectedUsage = ref(null);
const selectedBrand = ref(null);


onMounted(() => {
  itemStore.fetchUserItems();
  typeStore.fetchUserTypes();
  typeStore.fetchAllTypes();
  categoryStore.fetchUserCategories();
  usageStore.fetchUserUsages();
  brandStore.fetchUserBrands();
  brandStore.fetchBrands();
});



const debounceSearch = debounce((type) => {
  switch (type) {
    case 'items':
      itemStore.page = 1;
      itemStore.fetchUserItems();
      break;
    case 'types':
      typeStore.page = 1;
      typeStore.fetchUserTypes();
      break;
    case 'categories':
      categoryStore.page = 1;
      categoryStore.fetchUserCategories();
      break;
    case 'usages':
      usageStore.page = 1;
      usageStore.fetchUserUsages();
      break;
    case 'brands':
      brandStore.page = 1;
      brandStore.fetchUserBrands();
      break;
  }
}, 300);



const showItemModal = ref(false);
const showTypeModal = ref(false);
const showCategoryModal = ref(false);
const showUsageModal = ref(false);
const showBrandModal = ref(false);
const showAvailabilityModal = ref(false);



const confirmDeleteItem = (item) => {
  selectedItem.value = item;
  isDeleteItemDialogVisible.value = true;
};

const confirmDeleteType = (type) => {
  selectedType.value = type;
  isDeleteTypeDialogVisible.value = true;
};

const confirmDeleteCategory = (category) => {
  selectedItem.value = category;
  isDeleteCategoryDialogVisible.value = true;
};

const confirmDeleteUsage = (usage) => {
  selectedItem.value = usage;
  isDeleteUsageDialogVisible.value = true;
};

const confirmDeleteBrand = (usage) => {
  selectedItem.value = usage;
  isDeleteBrandDialogVisible.value = true;
};

const deleteItem = async (item) => {
  isDeleteItemDialogVisible.value = false;
  try {
    await itemStore.deleteItem(item.id);
    console.log('Item deleted successfully');
  } catch (error) {
    console.error('Failed to delete item:', error.message);
  }
};
const deleteType = async (type) => {
  isDeleteTypeDialogVisible.value = false;
  try {
    await typeStore.deleteType(type.id);
    console.log('Type deleted successfully');
  } catch (error) {
    console.error('Failed to delete type:', error.message);
  }
};

const deleteCategory = async (category) => {
  isDeleteCategoryDialogVisible.value = false;
  try {
    await categoryStore.deleteCategory(category.id);
    console.log('Category deleted successfully');
  } catch (error) {
    console.log(error)
    console.error('Failed to delete category:', error.message);
  }
};

const deleteUsage = async (usage) => {
  isDeleteUsageDialogVisible.value = false;
  try {
    await usageStore.deleteUsage(usage.id);
    console.log('Usage deleted successfully');
  } catch (error) {
    console.error('Failed to delete usage:', error.message);
  }
};

const deleteBrand = async (brand) => {
  isDeleteBrandDialogVisible.value = false;
  try {
    await brandStore.deleteBrand(brand.id);
    console.log('Brand deleted successfully');
  } catch (error) {
    console.error('Failed to delete brand:', error.message);
  }
};

</script>

<style scoped>
/* Add your scoped styles here */
</style>
