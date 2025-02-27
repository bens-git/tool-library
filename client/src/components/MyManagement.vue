<template>
  <v-container class="d-flex justify-center">
    <v-card title="Management" flat style="min-width: 90vw; min-height: 90vh">
      <v-tabs v-model="activeTab" grow>
        <v-tab>My Items</v-tab>
        <v-tab>My Archetypes</v-tab>
        <v-tab>My Categories</v-tab>
        <v-tab>My Usages</v-tab>
        <v-tab>My Brands</v-tab>
      </v-tabs>

      <!-- My Items Tab -->
      <div v-if="activeTab === 0">
        <v-card>
          <v-card-title>
            My Items
            <v-btn
              class="ms-auto"
              @click="
                [
                  (isEdit = false),
                  (selectedItem = null),
                  (showItemModal = true),
                ]
              "
              color="primary"
              dark
              >Create Item</v-btn
            >
          </v-card-title>
          <v-card-subtitle>
            Manage tool elements—each tool is a specific element of
            a tool archetype, like two 'Mitre Saws' bought at different times.
          </v-card-subtitle>
          <v-card-text>
            <v-row>
              <v-col cols="3" md="3">
                <v-text-field
                  density="compact"
                  v-model="itemStore.myItemsListFilters.search"
                  label="Search"
                  prepend-inner-icon="mdi-magnify"
                  variant="outlined"
                  hide-details
                  single-line
                  @input="debounceSearch('items')"
                ></v-text-field>
              </v-col>
              <v-col cols="3" md="3">
                <v-autocomplete
                  density="compact"
                  v-model="itemStore.myItemsListFilters.resourcearchetypeId"
                  :items="autocompleteResourceArchetypes"
                  label="Select a Resource Archetype"
                  item-title="name"
                  item-value="id"
                  hide-no-data
                  hide-details
                  return-object
                  clearable
                  @update:model-value="debounceSearch('items')"
                  @update:search="debouncedAutocompleteResourceArchetypeSearch"
                ></v-autocomplete>
              </v-col>
              <v-col cols="3" md="3">
                <v-autocomplete
                  density="compact"
                  v-model="itemStore.myItemsListFilters.brandId"
                  :items="autocompleteBrands"
                  label="Select a brand"
                  item-title="name"
                  item-value="id"
                  hide-no-data
                  hide-details
                  return-object
                  clearable
                  @update:model-value="debounceSearch('items')"
                  @update:search="debouncedAutocompleteBrandSearch"
                ></v-autocomplete>
              </v-col>
              <v-col cols="3" md="3">
                <v-select
                  density="compact"
                  v-model="itemStore.myItemsListFilters.resource"
                  :items="itemStore.resources"
                  label="Select a resource"
                  item-title="name"
                  item-value="id"
                  clearable
                  @update:model-value="debounceSearch('items')"
                ></v-select>
              </v-col>
            </v-row>

            <v-data-table-server
              :search="itemStore.myItemsListFilters.search"
              v-model:items-per-page="itemStore.myItemsListItemsPerPage"
              :items-length="itemStore.myItemsListTotalItems"
              :headers="myItemsListHeaders"
              @update:options="itemStore.updateMyItemsListOptions"
              :items="itemStore.myItemsListItems"
              item-value="name"
              mobile-breakpoint="sm"
            >
              <!-- Image column -->
              <template v-slot:[`item.image`]="{ item }">
                <v-img
                  v-if="item.images?.length > 0"
                  :src="fullImageUrl(item.images[0].path)"
                  max-height="200"
                  max-width="200"
                  min-height="200"
                  min-width="200"
                  alt="ResourceArchetype Image"
                ></v-img>
                <v-icon v-else>mdi-image-off</v-icon>
                <!-- Fallback icon if no image is available -->
              </template>
              <template v-slot:[`item.actions`]="{ item }">
                <v-icon
                  v-if="userStore.user && item.owned_by == userStore.user.id"
                  class="me-2"
                  size="small"
                  @click="
                    () => {
                      selectedItem = item;
                      showAvailabilityModal = true;
                    }
                  "
                >
                  mdi-calendar
                </v-icon>
                <v-icon
                  v-if="userStore.user && item.owned_by == userStore.user.id"
                  class="me-2"
                  size="small"
                  @click="
                    () => {
                      isEdit = true;
                      selectedItem = item;
                      showItemModal = true;
                    }
                  "
                >
                  mdi-pencil
                </v-icon>
                <v-icon
                  v-if="userStore.user && item.owned_by === userStore.user.id"
                  size="small"
                  @click="confirmDeleteItem(item)"
                >
                  mdi-delete
                </v-icon>
                <v-dialog v-model="isDeleteItemDialogVisible" max-width="400">
                  <v-card>
                    <v-card-title class="headline"
                      >Confirm Deletion</v-card-title
                    >
                    <v-card-text
                      >Are you sure you want to delete this item?</v-card-text
                    >
                    <v-card-actions>
                      <v-btn
                        color="primary"
                        text
                        @click="isDeleteItemDialogVisible = false"
                        >Cancel</v-btn
                      >
                      <v-btn color="red" text @click="deleteItem(item)"
                        >Delete</v-btn
                      >
                    </v-card-actions>
                  </v-card>
                </v-dialog>
              </template>
            </v-data-table-server>
          </v-card-text>
        </v-card>
      </div>

      <!-- My ResourceArchetypes Tab -->
      <div v-if="activeTab === 1">
        <v-card>
          <v-card-title>
            My ResourceArchetypes
            <v-btn
              class="ms-auto"
              @click="
                [
                  (isEdit = false),
                  (selectedResourceArchetype = null),
                  (showResourceArchetypeModal = true),
                ]
              "
              color="primary"
              dark
              >Create ResourceArchetype</v-btn
            >
          </v-card-title>

          <v-card-subtitle>
            Define tool archetypes, such as 'Mitre Saw' or 'Hammer,' to group similar
            elements in your inventory
          </v-card-subtitle>

          <v-card-text>
            <v-text-field
              density="compact"
              v-model="resourceArchetypeStore.search"
              label="Search"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              hide-details
              single-line
              @input="debounceSearch('resource_archetypes')"
            ></v-text-field>
            <v-data-table-server
              @update:options="resourceArchetypeStore.updateMyResourceArchetypesListOptions"
              v-model:items-per-page="resourceArchetypeStore.myResourceArchetypesListItemsPerPage"
              :headers="resourcearchetypeHeaders"
              :items="resourceArchetypeStore.myResourceArchetypesListResourceArchetypes"
              :items-length="resourceArchetypeStore.myResourceArchetypesListTotalResourceArchetypes"
              item-value="name"
              mobile-breakpoint="sm"
            >
              <template v-slot:[`item.actions`]="{ item }">
                <v-icon
                  v-if="userStore.user && item.created_by == userStore.user.id"
                  class="me-2"
                  size="small"
                  @click="
                    () => {
                      isEdit = true;
                      selectedResourceArchetype = item;
                      showResourceArchetypeModal = true;
                    }
                  "
                >
                  mdi-pencil
                </v-icon>
                <v-icon
                  v-if="userStore.user && item.created_by === userStore.user.id"
                  size="small"
                  @click="confirmDeleteResourceArchetype(item)"
                >
                  mdi-delete
                </v-icon>
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
            <v-btn
              class="ms-auto"
              @click="
                [
                  (isEdit = false),
                  (selectedCategory = null),
                  (showCategoryModal = true),
                ]
              "
              color="primary"
              dark
              >Create Category</v-btn
            >
          </v-card-title>
          <v-card-text>
            <v-text-field
              v-model="categoryStore.search"
              label="Search"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              hide-details
              single-line
              @input="debounceSearch('categories')"
            ></v-text-field>
            <v-data-table-server
              @update:options="categoryStore.updateUserCategoriesOptions"
              v-model:items-per-page="categoryStore.itemsPerPage"
              :headers="categoryHeaders"
              :items="categoryStore.userCategories"
              :items-length="categoryStore.totalUserCategories"
              item-value="name"
              mobile-breakpoint="sm"
            >
              <template v-slot:[`item.actions`]="{ item }">
                <v-icon
                  v-if="userStore.user && item.created_by == userStore.user.id"
                  class="me-2"
                  size="small"
                  @click="
                    () => {
                      isEdit = true;
                      selectedCategory = item;
                      showCategoryModal = true;
                    }
                  "
                >
                  mdi-pencil
                </v-icon>
                <v-icon
                  v-if="userStore.user && item.created_by === userStore.user.id"
                  size="small"
                  @click="confirmDeleteCategory(item)"
                >
                  mdi-delete
                </v-icon>
                <v-dialog
                  v-model="isDeleteCategoryDialogVisible"
                  max-width="400"
                >
                  <v-card>
                    <v-card-title class="headline"
                      >Confirm Deletion</v-card-title
                    >
                    <v-card-text
                      >Are you sure you want to delete this
                      category?</v-card-text
                    >
                    <v-card-actions>
                      <v-btn
                        color="primary"
                        text
                        @click="isDeleteCategoryDialogVisible = false"
                        >Cancel</v-btn
                      >
                      <v-btn color="red" text @click="deleteCategory(item)"
                        >Delete</v-btn
                      >
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
            <v-btn
              class="ms-auto"
              @click="
                [
                  (isEdit = false),
                  (selectedUsage = null),
                  (showUsageModal = true),
                ]
              "
              color="primary"
              dark
              >Create Usage</v-btn
            >
          </v-card-title>
          <v-card-text>
            <v-text-field
              v-model="usageStore.search"
              label="Search"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              hide-details
              single-line
              @input="debounceSearch('usages')"
            ></v-text-field>
            <v-data-table-server
              @update:options="usageStore.updateUserUsagesOptions"
              v-model:items-per-page="usageStore.itemsPerPage"
              :headers="usageHeaders"
              :items="usageStore.userUsages"
              :items-length="usageStore.totalUserUsages"
              item-value="name"
              mobile-breakpoint="sm"
            >
              <template v-slot:[`item.actions`]="{ item }">
                <v-icon
                  v-if="userStore.user && item.created_by == userStore.user.id"
                  class="me-2"
                  size="small"
                  @click="
                    () => {
                      isEdit = true;
                      selectedUsage = item;
                      showUsageModal = true;
                    }
                  "
                >
                  mdi-pencil
                </v-icon>

                <v-icon
                  v-if="userStore.user && item.created_by === userStore.user.id"
                  size="small"
                  @click="confirmDeleteUsage(item)"
                >
                  mdi-delete
                </v-icon>
                <v-dialog v-model="isDeleteUsageDialogVisible" max-width="400">
                  <v-card>
                    <v-card-title class="headline"
                      >Confirm Deletion</v-card-title
                    >
                    <v-card-text
                      >Are you sure you want to delete this usage?</v-card-text
                    >
                    <v-card-actions>
                      <v-btn
                        color="primary"
                        text
                        @click="isDeleteUsageDialogVisible = false"
                        >Cancel</v-btn
                      >
                      <v-btn color="red" text @click="deleteUsage(item)"
                        >Delete</v-btn
                      >
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
            <v-btn
              class="ms-auto"
              @click="
                [
                  (isEdit = false),
                  (selectedBrand = null),
                  (showBrandModal = true),
                ]
              "
              color="primary"
              dark
              >Create Brand</v-btn
            >
          </v-card-title>
          <v-card-text>
            <v-row>
              <v-col cols="12" md="12">
                <v-text-field
                  density="compact"
                  v-model="brandStore.myBrandsListFilters.search"
                  label="Search"
                  prepend-inner-icon="mdi-magnify"
                  variant="outlined"
                  hide-details
                  single-line
                  @input="debounceSearch('brands')"
                ></v-text-field> </v-col
            ></v-row>
            <v-data-table-server
              :search="brandStore.myBrandsListFilters.search"
              v-model:items-per-page="brandStore.myBrandsListItemsPerPage"
              :items-length="brandStore.myBrandsListTotalBrands"
              :headers="myBrandsListHeaders"
              @update:options="brandStore.updateMyBrandsListOptions"
              :items="brandStore.myBrandsListBrands"
              item-value="name"
              mobile-breakpoint="sm"
            >
              <template v-slot:[`item.actions`]="{ item }">
                <v-icon
                  v-if="userStore.user && item.created_by == userStore.user.id"
                  class="me-2"
                  size="small"
                  @click="
                    () => {
                      isEdit = true;
                      selectedBrand = item;
                      showBrandModal = true;
                    }
                  "
                >
                  mdi-pencil
                </v-icon>

                <v-icon
                  v-if="userStore.user && item.created_by === userStore.user.id"
                  size="small"
                  @click="confirmDeleteBrand(item)"
                >
                  mdi-delete
                </v-icon>
                <v-dialog v-model="isDeleteBrandDialogVisible" max-width="400">
                  <v-card>
                    <v-card-title class="headline"
                      >Confirm Deletion</v-card-title
                    >
                    <v-card-text
                      >Are you sure you want to delete this brand?</v-card-text
                    >
                    <v-card-actions>
                      <v-btn
                        color="primary"
                        text
                        @click="isDeleteBrandDialogVisible = false"
                        >Cancel</v-btn
                      >
                      <v-btn color="red" text @click="deleteBrand(item)"
                        >Delete</v-btn
                      >
                    </v-card-actions>
                  </v-card>
                </v-dialog>
              </template>
            </v-data-table-server>
          </v-card-text>
        </v-card>
      </div>

      <v-dialog v-model="showItemModal" max-width="600px">
        <ItemForm
          :showItemModal="showItemModal"
          :isEdit="isEdit"
          :item="selectedItem"
          @close-modal="showItemModal = false"
        />
      </v-dialog>

      <v-dialog v-model="showAvailabilityModal" max-width="600px">
        <AvailabilityForm
          :showAvailabilityModal="showAvailabilityModal"
          :item="selectedItem"
          @close-modal="showAvailabilityModal = false"
        />
      </v-dialog>

      <v-dialog v-model="showResourceArchetypeModal" max-width="600px">
        <ResourceArchetypeForm
          :showResourceArchetypeModal="showResourceArchetypeModal"
          :isEdit="isEdit"
          :resourceArchetype="selectedResourceArchetype"
          @close-modal="showResourceArchetypeModal = false"
        />
      </v-dialog>

      <v-dialog v-model="showCategoryModal" max-width="600px">
        <CategoryForm
          :showCategoryModal="showCategoryModal"
          :isEdit="isEdit"
          :category="selectedCategory"
          @close-modal="showCategoryModal = false"
        />
      </v-dialog>

      <v-dialog v-model="showUsageModal" max-width="600px">
        <UsageForm
          :showUsageModal="showUsageModal"
          :isEdit="isEdit"
          :usage="selectedUsage"
          @close-modal="showUsageModal = false"
        />
      </v-dialog>

      <v-dialog v-model="showBrandModal" max-width="600px">
        <BrandForm
          :showBrandModal="showBrandModal"
          :isEdit="isEdit"
          :brand="selectedBrand"
          @close-modal="showBrandModal = false"
        />
      </v-dialog>

      <v-dialog v-model="isDeleteResourceArchetypeDialogVisible" max-width="400">
        <v-card>
          <v-card-title class="headline">Confirm Deletion</v-card-title>
          <v-card-text>Are you sure you want to delete {{selectedResourceArchetype.name}}?</v-card-text>
          <v-card-actions>
            <v-btn
              color="primary"
              text
              @click="isDeleteResourceArchetypeDialogVisible = false"
              >Cancel</v-btn
            >
            <v-btn color="red" text @click="deleteUserResourceArchetype(item)">Delete</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { useItemStore } from "@/stores/item";
import { useResourceArchetypeStore } from "@/stores/resource_archetype";
import { useCategoryStore } from "@/stores/category";
import { useBrandStore } from "@/stores/brand";
import { useUsageStore } from "@/stores/usage";
import { useUserStore } from "@/stores/user";
import debounce from "lodash/debounce";
import ItemForm from "./ItemForm.vue";
import AvailabilityForm from "./AvailabilityForm.vue";
import ResourceArchetypeForm from "./ResourceArchetypeForm.vue";
import CategoryForm from "./CategoryForm.vue";
import UsageForm from "./UsageForm.vue";
import BrandForm from "./BrandForm.vue";
import useApi from "@/stores/api";

const itemStore = useItemStore();
const resourceArchetypeStore = useResourceArchetypeStore();
const categoryStore = useCategoryStore();
const usageStore = useUsageStore();
const brandStore = useBrandStore();
const userStore = useUserStore();

const activeTab = ref(0);
const isEdit = ref("");
const { fullImageUrl } = useApi();

const myItemsListHeaders = [
  { title: "Image", value: "image" },
  { title: "Code", value: "code" },
  { title: "Resource Archetype", value: "resource_archetype.name" },
  { title: "Brand", value: "brand.name" },
  { title: "Resource", value: "resource" },
  { title: "Actions", value: "actions", sortable: false },
];
const resourcearchetypeHeaders = [
  { title: "Name", value: "name" },
  {
    title: "Categories",
    align: "start",
    sortable: false,
    key: "categories",
  },
  {
    title: "Usages",
    align: "start",
    sortable: false,
    key: "usages",
  },
  { title: "Actions", value: "actions", sortable: false },
];
const categoryHeaders = [
  { title: "Name", value: "name" },
  { title: "Actions", value: "actions", sortable: false },
];
const usageHeaders = [
  { title: "Name", value: "name" },
  { title: "Actions", value: "actions", sortable: false },
];

const myBrandsListHeaders = [
  { title: "Name", value: "name" },
  { title: "Actions", value: "actions", sortable: false },
];

const isDeleteItemDialogVisible = ref(false);
const isDeleteResourceArchetypeDialogVisible = ref(false);
const isDeleteCategoryDialogVisible = ref(false);
const isDeleteUsageDialogVisible = ref(false);
const isDeleteBrandDialogVisible = ref(false);
const selectedItem = ref(null);
const selectedResourceArchetype = ref(null);
const selectedCategory = ref(null);
const selectedUsage = ref(null);
const selectedBrand = ref(null);
const autocompleteResourceArchetypes = ref([]);
const autocompleteBrands = ref([]);

onMounted(async() => {
  itemStore.fetchResources();
  resourceArchetypeStore.fetchMyResourceArchetypes();
  categoryStore.fetchUserCategories();
  usageStore.fetchUserUsages();
  brandStore.fetchMyBrands();
  autocompleteResourceArchetypes.value = await resourceArchetypeStore.fetchAutocompleteSelectResourceArchetypes();
  autocompleteBrands.value = await brandStore.fetchAutocompleteSelectBrands();

});

// Autocomplete ResourceArchetype Search handler
const onAutocompleteResourceArchetypeSearch = async (query) => {
  autocompleteResourceArchetypes.value = await resourceArchetypeStore.fetchAutocompleteSelectResourceArchetypes(query);
};

// Autocomplete brand Search handler
const onAutocompleteBrandSearch = async (query) => {
  autocompleteBrands.value =
    await brandStore.fetchAutocompleteSelectBrands(query);
};

// Debounced search function
const debouncedAutocompleteResourceArchetypeSearch = debounce(onAutocompleteResourceArchetypeSearch, 300);
const debouncedAutocompleteBrandSearch = debounce(
  onAutocompleteBrandSearch,
  300
);

const debounceSearch = debounce((resourcearchetype) => {
  switch (resourcearchetype) {
    case "items":
      itemStore.page = 1;
      itemStore.fetchMyItems();
      break;
    case "resource_archetypes":
      resourceArchetypeStore.page = 1;
      resourceArchetypeStore.fetchMyResourceArchetypes();
      break;
    case "categories":
      categoryStore.page = 1;
      categoryStore.fetchUserCategories();
      break;
    case "usages":
      usageStore.page = 1;
      usageStore.fetchUserUsages();
      break;
    case "brands":
      brandStore.page = 1;
      brandStore.fetchMyBrands();
      break;
  }
}, 300);

const showItemModal = ref(false);
const showResourceArchetypeModal = ref(false);
const showCategoryModal = ref(false);
const showUsageModal = ref(false);
const showBrandModal = ref(false);
const showAvailabilityModal = ref(false);

const confirmDeleteItem = (item) => {
  selectedItem.value = item;
  isDeleteItemDialogVisible.value = true;
};

const confirmDeleteResourceArchetype = (resourcearchetype) => {
  console.log(resourcearchetype)
  selectedResourceArchetype.value = resourcearchetype;
  isDeleteResourceArchetypeDialogVisible.value = true;
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
  try {
    await itemStore.deleteItem(item.id);
    console.log("Item deleted successfully");
    isDeleteItemDialogVisible.value = false;
  } catch (error) {
    console.error("Failed to delete item:", error.message);
  }
};
const deleteUserResourceArchetype = async () => {

    await resourceArchetypeStore.deleteUserResourceArchetype(selectedResourceArchetype.value.id);
    isDeleteResourceArchetypeDialogVisible.value = false;

  
};

const deleteCategory = async (category) => {
  isDeleteCategoryDialogVisible.value = false;
  try {
    await categoryStore.deleteCategory(category.id);
    console.log("Category deleted successfully");
  } catch (error) {
    console.log(error);
    console.error("Failed to delete category:", error.message);
  }
};

const deleteUsage = async (usage) => {
  isDeleteUsageDialogVisible.value = false;
  try {
    await usageStore.deleteUsage(usage.id);
    console.log("Usage deleted successfully");
  } catch (error) {
    console.error("Failed to delete usage:", error.message);
  }
};

const deleteBrand = async (brand) => {
  isDeleteBrandDialogVisible.value = false;
  try {
    await brandStore.deleteBrand(brand.id);
    console.log("Brand deleted successfully");
  } catch (error) {
    console.error("Failed to delete brand:", error.message);
  }
};
</script>

<style scoped>
/* Add your scoped styles here */
</style>
