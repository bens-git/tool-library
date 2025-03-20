<template>
  <v-container>
    <v-row justify="center">
      <v-col cols="12" md="8">
        <v-card title="Email Verification">
       
          <v-card-text>
            <v-alert v-if="success" type="success" >
              {{ successMessage }}
            </v-alert>
            <v-alert v-if="error" type="error" >
              {{ errorMessage }}
            </v-alert>
          </v-card-text>
          <v-card-actions>
            <LoginDialog />
           
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { computed } from "vue";
import { useRoute } from "vue-router";
import LoginDialog from "./LoginDialog.vue";

const route = useRoute();

const success = computed(() => route.query.success === "true");
const error = computed(() => route.query.error === "true");

const successMessage = "Your email has been successfully verified!";
const errorMessage = "There was an error verifying your email. Please try again.";


</script>

<style scoped>
.headline {
  font-weight: bold;
}
</style>