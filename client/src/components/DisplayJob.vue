<template>
  <v-container class="d-flex justify-center">
    <v-sheet class="flowchart pa-4">
      <v-timeline :align="timelineAlign" :direction="direction">
        <!-- Input Item -->
        <v-timeline-item :dot-color="inputColor" size="small">
          <template v-slot:opposite v-if="!isHorizontal">
            <div class="text-right">
              <strong>{{ job?.base?.name }}</strong>
            </div>
          </template>
          <template v-slot:default v-if="isHorizontal">
            <div class="text-right">
              <strong>{{ job?.base?.name }}</strong>
            </div>
          </template>
        </v-timeline-item>

        <!-- Tool Label with Arrow -->
        <v-timeline-item dot-color="grey" size="small">
          <template v-slot:opposite v-if="!isHorizontal">
            <v-icon size="30">{{ arrowIcon }}</v-icon>
          </template>
          <template v-slot:default v-if="isHorizontal">
            <v-icon size="30">{{ arrowIcon }}</v-icon>
          </template>
          <div class="text-right">
            <span class="tool-label">{{ job?.tool?.name }}</span>
          </div>
        </v-timeline-item>

        <!-- Output Item -->
        <v-timeline-item :dot-color="outputColor" size="small">
          <template v-slot:opposite v-if="!isHorizontal">
            <div class="text-right">
              <strong>{{ job?.product?.name }}</strong>
            </div>
          </template>

          <template v-slot:default v-if="isHorizontal">
            <div class="text-right">
              <strong>{{ job?.product?.name }}</strong>
            </div>
          </template>
        </v-timeline-item>
      </v-timeline>
    </v-sheet>
  </v-container>
</template>

<script setup>
import { ref, computed } from "vue";



const props = defineProps({
  horizontal: Boolean,
  job: Object,
});

const newJob1 = computed(() => props.job);

const isHorizontal = computed(() => props.horizontal);
const direction = computed(() =>
  isHorizontal.value ? "horizontal" : "vertical"
);
const timelineAlign = computed(() => (isHorizontal.value ? "center" : "start"));
const arrowIcon = computed(() =>
  isHorizontal.value ? "mdi-arrow-right" : "mdi-arrow-down"
);

const inputColor = ref("blue");
const outputColor = ref("green");
</script>

<style scoped>
.flowchart {
  border-radius: 12px;
  padding: 16px;
}

.tool-label {
  font-size: 14px;
  font-weight: bold;
  text-align: center;
}
</style>
