<template>
  <v-container>
    <v-card>
      <v-card-title>Edit Profile</v-card-title>
      <v-card-text>
        <v-form ref="form" v-model="isValid">
          <!-- Name Field -->
          <v-text-field v-model="user.name" label="Name" :rules="[rules.required]" required></v-text-field>

          <!-- Street Address -->
          <v-text-field v-model="location.street_address" label="Street Address" :rules="[rules.required]"
            required></v-text-field>

          <!-- City -->
          <v-text-field v-model="location.city" label="City" :rules="[rules.required]" required></v-text-field>

          <!-- State -->
          <v-text-field v-model="location.state" label="State" :rules="[rules.required]" required></v-text-field>

          <!-- Country -->
          <v-text-field v-model="location.country" label="Country" :rules="[rules.required]" required></v-text-field>

          <!-- Postal Code -->
          <v-text-field v-model="location.postal_code" label="Postal Code" :rules="[rules.required]"
            required></v-text-field>

          <!-- Building Name -->
          <v-text-field v-model="location.building_name" label="Building Name"></v-text-field>

          <!-- Floor Number -->
          <v-text-field v-model="location.floor_number" label="Floor Number" type="number"></v-text-field>

          <!-- Unit Number -->
          <v-text-field v-model="location.unit_number" label="Unit Number" type="number"></v-text-field>

          <!-- Save Button -->
          <v-btn :disabled="!isValid" @click="saveUser">
            Save
          </v-btn>

          <!-- Delete Button -->
          <v-btn color="red" @click="deleteUser">
            Delete Account
          </v-btn>

          <!-- Change Password Button -->
          <v-btn color="primary" @click="dialog = true">
            Change Password
          </v-btn>
        </v-form>

        <!-- Change Password Dialog -->
        <v-dialog v-model="dialog" max-width="500px">
          <v-card>
            <v-card-title>
              <span class="headline">Change Password</span>
            </v-card-title>
            <v-card-text>
              <v-form ref="passwordForm" v-model="passwordFormValid">
                <!-- Current Password -->
                <v-text-field
                  v-model="currentPassword"
                  label="Current Password"
                  type="password"
                  :rules="[rules.required]"
                  required
                  autocomplete="current-password"
                ></v-text-field>

                <!-- New Password -->
                <v-text-field
                  v-model="newPassword"
                  label="New Password"
                  type="password"
                  :rules="[rules.required, validatePassword]"
                  required
                ></v-text-field>

                <!-- Confirm New Password -->
                <v-text-field
                  v-model="confirmPassword"
                  label="Confirm New Password"
                  type="password"
                  :rules="[rules.required, confirmPasswordMatch]"
                  required
                ></v-text-field>
              </v-form>
            </v-card-text>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn color="blue darken-1" text @click="dialog = false">
                Cancel
              </v-btn>
              <v-btn color="blue darken-1" @click="changePassword" :disabled="!passwordFormValid">
                Change Password
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useUserStore } from '@/stores/user';
import { useLocationStore } from '@/stores/location';
import { useRouter } from 'vue-router';

const userStore = useUserStore();
const locationStore = useLocationStore();
const router = useRouter();

const user = ref({ name: '', location_id: null });
const location = ref({
  street_address: '',
  city: '',
  state: '',
  country: '',
  postal_code: '',
  building_name: '',
  floor_number: '',
  unit_number: ''
});
const isValid = ref(false);
const rules = {
  required: value => !!value || 'Required.',
};

// Password change dialog
const dialog = ref(false);
const passwordFormValid = ref(false);
const currentPassword = ref('');
const newPassword = ref('');
const confirmPassword = ref('');

// Fetch user and location data when the component mounts
onMounted(async () => {
  user.value = { ...userStore.user }; // Load user data
  if (user.value.location_id) {
    location.value = await locationStore.fetchUserLocation(); // Fetch location data from the database
  }
});

// Save user and location data
const saveUser = async () => {
  if (!isValid.value) return;

  // Save location first to get the ID
  const locationId = await locationStore.updateUserLocation(location.value);

  // Update user with new location ID
  user.value.location_id = locationId;
  await userStore.updateUser(user.value);
};

// Delete the user account
const deleteUser = async () => {
  const confirmed = confirm('Are you sure you want to delete your account? This action cannot be undone.');
  if (confirmed) {
    await userStore.deleteUser();
    // Optionally, redirect to login or home page
    router.push('/login');
  }
};

// Change password
const changePassword = async () => {
  if (!passwordFormValid.value) return;

  // Implement your password change logic here
  await userStore.changePassword(currentPassword.value,newPassword.value,confirmPassword.value);

  dialog.value = false; // Close the dialog
};

// Validate new password (example rule)
const validatePassword = (value) => {
  return value.length >= 8 || 'Password must be at least 8 characters long.';
};

// Confirm new password matches
const confirmPasswordMatch = (value) => {
  return value === newPassword.value || 'Passwords must match.';
};
</script>

<style scoped>
/* Add your custom styles here */
</style>
