<template>
  <v-container>
    <v-row justify="center">
      <v-col cols="12" md="8">
        <v-card>
          <v-card-title>
            <span class="headline">Email Verification</span>
          </v-card-title>
          <v-card-subtitle>
            <v-alert v-if="success" type="success" dismissible>
              {{ successMessage }}
            </v-alert>
            <v-alert v-if="error" type="error" dismissible>
              {{ errorMessage }}
            </v-alert>
          </v-card-subtitle>
          <v-card-actions>
            <v-btn to="login-form" text>
              Login
            </v-btn>
            <v-btn @click="navigateHome" color="primary">Go to Homepage</v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
export default {
  data() {
    return {
      success: this.$route.query.success === 'true', // Read success status from query params
      error: this.$route.query.error === 'true',     // Read error status from query params
      successMessage: 'Your email has been successfully verified!',
      errorMessage: 'There was an error verifying your email. Please try again.'
    };
  },
  methods: {
    navigateHome() {
      this.$router.push('/'); // Redirect to homepage
    }
  }
}
</script>

<style scoped>
.headline {
  font-weight: bold;
}
</style>