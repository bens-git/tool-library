<template>
  <div class="pl-4 text-center">
    <v-dialog v-model="dialog" @open="onOpen">
      <template v-slot:activator="{ props: activatorProps }">
        <v-btn
          color="primary"
          class="text-none font-weight-regular"
          prepend-icon="mdi-format-list-bulleted"
          text="Items"
          variant="tonal"
          v-bind="activatorProps"
          block
        ></v-btn>
      </template>
      <v-card
        v-if="localArchetype"
        prepend-icon="mdi-format-list-bulleted"
        title="Archetype Items"
        :subtitle="localArchetype.name"
      >
        <v-card-text>
          {{ localArchetype.description }}
          {{ localArchetype.notes }}

          <v-data-iterator
            :items="itemStore.archetypeDialogItemListItems"
            :items-per-page="itemsPerPage"
          >
            <template v-slot:header="{ page, pageCount, prevPage, nextPage }">
              <div>
                <v-row>
                  <v-col>
                    <v-text-field
                    density="compact"

                      v-model="itemStore.archetypeDialogItemListFilters.search"
                      placeholder="Search"
                      prepend-inner-icon="mdi-magnify"
                      variant="solo"
                      clearable
                      hide-details
                      @update:modelValue="debounceSearch"
                    ></v-text-field>
                  </v-col>

                  <!-- Date Range Picker -->

                  <v-col >
                    <v-date-input
                    density="compact"

                      v-model="
                        itemStore.archetypeDialogItemListFilters.dateRange
                      "
                      label="Dates"
                      prepend-icon=""
                      persistent-placeholder
                      multiple="range"
                      :min="minStartDate"
                      @update:modelValue="debounceSearch"
                    ></v-date-input>
                  </v-col>

                  <v-col >
                    <v-btn variant="text" @click="onClickSeeAll">
                      <span
                        class="text-decoration-underline text-none"
                        v-if="itemsPerPage == 2"
                        >See all</span
                      >
                      <span class="text-decoration-underline text-none" v-else
                        >Paginate</span
                      >
                    </v-btn>

                    <div class="d-inline-flex">
                      <v-btn
                        :disabled="page === 1"
                        icon="mdi-arrow-left"
                        size="small"
                        variant="tonal"
                        @click="prevPage"
                      ></v-btn>

                      <v-btn
                        :disabled="page === pageCount"
                        icon="mdi-arrow-right"
                        size="small"
                        variant="tonal"
                        @click="nextPage"
                      ></v-btn>
                    </div>
                  </v-col>
                </v-row>
              </div>
            </template>

            <template v-slot:default="{ items }">
              <v-row>
                <v-col
                  v-for="(item, i) in items"
                  :key="i"
                  cols="12"
                  sm="6"
                  xl="3"
                >
                  <v-sheet border>
                    <div style="min-height: 125px">
                      <div v-if="item.raw.images && item.raw.images.length">
                        <v-carousel
                          v-if="item.raw.images.length > 1"
                          show-arrows
                          hide-delimiter-background
                          height="150"
                        >
                          <v-carousel-item
                            v-for="(image, index) in item.raw.images"
                            :key="index"
                          >
                            <v-img
                              :gradient="`to top right, rgba(255, 255, 255, .1), rgba(${item.raw.color}, .15)`"
                              :src="fullImageUrl(image.path)"
                              height="150"
                              cover
                            ></v-img>
                          </v-carousel-item>
                        </v-carousel>

                        <v-img
                          v-else
                          :gradient="`to top right, rgba(255, 255, 255, .1), rgba(${item.raw.color}, .15)`"
                          :src="fullImageUrl(item.raw.images[0].path)"
                          height="150"
                          cover
                        ></v-img>
                      </div>
                    </div>
                    <v-list-item
                      :title="itemStore.itemCode(item)"
                      density="comfortable"
                      lines="two"
                      :subtitle="item.raw.description"
                    >
                      <template v-slot:title>
                        <strong class="text-h6">
                          {{ item.raw.item_name }}
                        </strong>
                      </template>
                    </v-list-item>

                    <v-table class="text-caption" density="compact">
                      <tbody>
                        <tr align="right">
                          <th>Code:</th>
                          <td>{{ item.raw.code }}</td>
                        </tr>

                        <tr align="right">
                          <th>Location:</th>
                          <td>{{ item.raw.location }}</td>
                        </tr>

                        <tr align="right">
                          <th>Brand:</th>
                          <td>{{ item.raw.brand_name }}</td>
                        </tr>

                        <tr align="right">
                          <th>Serial:</th>
                          <td>{{ item.raw.serial }}</td>
                        </tr>

                        <tr align="right">
                          <th>Purchase Value:</th>
                          <td>${{ item.raw.purchase_value }}</td>
                        </tr>

                        <tr align="right">
                          <th>Purchased At:</th>
                          <td>{{ item.raw.purchased_at }}</td>
                        </tr>

                        <tr align="right">
                          <th>Manufactured At:</th>
                          <td>{{ item.raw.manufactured_at }}</td>
                        </tr>

                        <tr align="right">
                          <th>Owned By:</th>
                          <td>{{ item.raw.owner_name }}</td>
                        </tr>
                      </tbody>
                    </v-table>
                    <v-divider/>

                    <div class="d-flex justify-space-between pa-4">
                      <RentalDatesDialog
                        :item="item.raw"
                        v-if="item.raw.owned_by != userStore.user.id"
                      />
                    </div>
                  </v-sheet>
                </v-col>
              </v-row>
            </template>

            <template v-slot:footer="{ page, pageCount, items }">
              <v-footer
                class="justify-space-between text-body-2 mt-4"
                color="surface-variant"
              >
                Total items: {{ items.length }}

                <div>Page {{ page }} of {{ pageCount }}</div>
              </v-footer>
            </template>
          </v-data-iterator>
          <br />
          <v-alert
            color="warning"
            v-if="
              !itemStore.archetypeDialogItemListItems ||
              !itemStore.archetypeDialogItemListItems.length
            "
            >No items found</v-alert
          >
        </v-card-text>
      </v-card>
    </v-dialog>
  </div>
</template>
<script setup>
import { shallowRef, ref, computed, watch } from "vue";
import { useItemStore } from "@/stores/item";
import { useArchetypeStore } from "@/stores/archetype";
import { useUserStore } from "@/stores/user";
import { useResponseStore } from "@/stores/response";
import useApi from "@/stores/api";
import RentalDatesDialog from "./RentalDatesDialog.vue";

const dialog = shallowRef(false);

const itemStore = useItemStore();
const archetypeStore = useArchetypeStore();
const userStore = useUserStore();

const responseStore = useResponseStore();
const localArchetype = ref(null);
const localItem = ref(null);
const itemsPerPage = ref(2);

const { fullImageUrl } = useApi();

const props = defineProps({
  archetype: Object,
});

const debounceSearch = _.debounce(() => {
  itemStore.fetchArchetypeDialogItemListItems(
    localArchetype.value.id,
    archetypeStore.location,
    archetypeStore.radius
  );
}, 300);

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
  localArchetype.value = {
    ...props.item,
  };
};

// const emit = defineEmits(["update:modelValue", "close"]);

const onOpen = async () => {
  initialize();
  responseStore.$reset();
  if (props.archetype) {
    localArchetype.value = props.archetype;
    await itemStore.fetchArchetypeDialogItemListItems(
      localArchetype.value.id,
      archetypeStore.location,
      archetypeStore.radius
    );
  } else {
    localArchetype.value = { name: null };
  }
};

const onClose = () => {};

// Computed properties for date constraints
const today = new Date();
today.setHours(0, 0, 0, 0);

const startOfDayInMillis = 24 * 60 * 60 * 1000; // 24 hours in milliseconds

const minStartDate = computed(() => today);

// Watchers to ensure dates are correctly updated
watch(
  () => archetypeStore.dateRange[0],
  (newStartDate) => {
    if (
      newStartDate >
      archetypeStore.dateRange[archetypeStore.dateRange.length - 1]
    ) {
      archetypeStore.dateRange[archetypeStore.dateRange.length - 1] = new Date(
        newStartDate.getTime() + startOfDayInMillis
      );
    }
  }
);

watch(
  () => archetypeStore.dateRange[archetypeStore.dateRange.length - 1],
  (newEndDate) => {
    if (newEndDate < archetypeStore.dateRange[0]) {
      archetypeStore.dateRange[0] = new Date(
        newEndDate.getTime() - startOfDayInMillis
      );
    }
  }
);

const onClickSeeAll = () => {
  if (itemsPerPage.value == 2) {
    itemsPerPage.value = itemStore.totalArchetypeDialogItemListItems;
  } else {
    itemsPerPage.value = 2;
  }
};
</script>
