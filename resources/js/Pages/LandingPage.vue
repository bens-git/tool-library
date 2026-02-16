<template>
  <PageLayout>
    <Head :title="appTitle" />

    <v-container fluid class="d-flex align-center justify-center pa-4">
      <div class="text-center">
        <!-- Hero -->
        <div class="mb-8">
          <v-icon size="72" color="primary" class="mb-2">mdi-hammer</v-icon>
          <div class="text-h4 font-weight-bold mb-1">{{ appTitle }}</div>
          <div class="text-subtitle-1">
            Borrow tools. Save money. Build community.
          </div>
        </div>

        <!-- Features -->
        <v-row density="comfortable" class="mb-8">
          <v-col cols="12" sm="4">
            <v-sheet class="pa-6 rounded-lg elevation-2" color="grey-lighten-4">
              <v-icon size="20" color="primary" class="mb-2">mdi-tools</v-icon>
              <div class="text-subtitle-1 font-weight-medium mb-1">
                Borrow What You Need
              </div>
              <div class="text-body-2">
                Access shared tools without the cost or storage hassle.
              </div>
            </v-sheet>
          </v-col>

          <v-col cols="12" sm="4">
            <v-sheet class="pa-6 rounded-lg elevation-2" color="grey-lighten-4">
              <v-icon size="20" color="primary" class="mb-2">mdi-calendar-check</v-icon>
              <div class="text-subtitle-1 font-weight-medium mb-1">
                Easy Reservations
              </div>
              <div class="text-body-2">
                See availability and reserve tools in seconds.
              </div>
            </v-sheet>
          </v-col>

          <v-col cols="12" sm="4">
            <v-sheet class="pa-6 rounded-lg elevation-2" color="grey-lighten-4">
              <v-icon size="20" color="primary" class="mb-2">mdi-account-group</v-icon>
              <div class="text-subtitle-1 font-weight-medium mb-1">
                Built for Communities
              </div>
              <div class="text-body-2">
                Perfect for neighborhoods, co-ops, and libraries.
              </div>
            </v-sheet>
          </v-col>
        </v-row>

        <!-- Call to Action -->
        <v-btn
          large
          color="primary"
          @click="goNext"
        >
          {{ user ? 'Go to Catalog' : 'Login to Get Started' }}
        </v-btn>
      </div>
    </v-container>
  </PageLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import PageLayout from '@/Layouts/PageLayout.vue'
import { router,usePage } from '@inertiajs/vue3'

const page = usePage()
const user = page.props.auth.user


// App title from env
const appTitle = import.meta.env.VITE_APP_NAME || 'Community Tool Library'

// Navigate based on login state
function goNext() {
  if (user) {
    router.visit('/library-catalog') // go to library
  } else {
    router.visit('/login') // go to login
  }
}
</script>

<style scoped>

.v-sheet {
  transition: transform 0.2s, box-shadow 0.2s;
  cursor: default;
}

.v-sheet:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.v-btn {
  min-width: 220px;
}
</style>
