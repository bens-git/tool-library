<template>
    <v-card class="my-5">
        <v-card-title>
            Register
        </v-card-title>
        <v-card-text>
            <!-- Show success message or registration form based on registration status -->
            <template v-if="registrationSuccessful">
                <v-alert type="success" dismissible>
                    {{ successMessage }}
                </v-alert>
            </template>

            <template v-else>
                <v-form @submit.prevent="registerUser" ref="form">
                    <v-text-field density="compact" v-model="name" label="Name"
                        :error-messages="responseStore?.response?.errors[0]?.name" required></v-text-field>

                    <v-text-field density="compact" v-model="email" label="Email" type="email"
                        :error-messages="responseStore?.response?.errors[0]?.email" required></v-text-field>

                    <v-text-field density="compact" v-model="password" label="Password" type="password"
                        :error-messages="responseStore?.response?.errors[0]?.password" required
                        autocomplete="password"></v-text-field>

                    <v-text-field density="compact" v-model="street_address" label="Street Address"
                        :error-messages="responseStore?.response?.errors[0]?.street_address" required></v-text-field>

                    <v-text-field density="compact" v-model="city" label="City"
                        :error-messages="responseStore?.response?.errors[0]?.city" required></v-text-field>

                    <v-text-field density="compact" v-model="state" label="State/Province"
                        :error-messages="responseStore?.response?.errors[0]?.state" required></v-text-field>

                    <v-text-field density="compact" v-model="country" label="Country"
                        :error-messages="responseStore?.response?.errors[0]?.country" required></v-text-field>


                    <v-text-field density="compact" v-model="postal_code" label="Postal Code"
                        :error-messages="responseStore?.response?.errors[0]?.postal_code" required></v-text-field>

                    <v-text-field density="compact" v-model="building_name" label="Building Name"
                        :error-messages="responseStore?.response?.errors[0]?.building_name"></v-text-field>

                    <v-text-field density="compact" v-model="floor_number" label="Floor Number"
                        :error-messages="responseStore?.response?.errors[0]?.floor_number"></v-text-field>

                    <v-text-field density="compact" v-model="unit_number" label="Unit Number"
                        :error-messages="responseStore?.response?.errors[0]?.unit_number"></v-text-field>

                    <v-btn type="submit" :loading="userStore.isLoading" color="primary" class="mt-4">
                        Register
                    </v-btn>


                </v-form>
            </template>
        </v-card-text>
    </v-card>
</template>


<script>
import { ref } from 'vue';
import { useUserStore } from '../stores/user';
import { useResponseStore } from '../stores/response';

export default {
    setup() {
        const name = ref('');
        const email = ref('');
        const password = ref('');
        const street_address = ref('');
        const city = ref('');
        const state = ref('');
        const country = ref('');
        const postal_code = ref('');
        const building_name = ref('');
        const floor_number = ref('');
        const unit_number = ref('');

        const userStore = useUserStore();
        const responseStore = useResponseStore();

        const errors = ref({});
        const successMessage = ref('');
        const registrationSuccessful = ref(false);

        const registerUser = async () => {

            errors.value = {}; // Ensure errors is always defined
            successMessage.value = '';

            responseStore.clearResponse(); // Clear previous responses

            try {
                await userStore.register({
                    name: name.value,
                    email: email.value,
                    password: password.value,
                    street_address: street_address.value,
                    city: city.value,
                    state: state.value,
                    country: country.value,
                    postal_code: postal_code.value,
                    building_name: building_name.value,
                    floor_number: floor_number.value,
                    unit_number: unit_number.value,
                });

                // Check responseStore for errors
                if (!responseStore.response?.success) {
                    errors.value = responseStore.response.errors.errors || {}; // Ensure it's an object
                    console.log('errors', responseStore.response)
                } else {
                    console.log(responseStore.response)
                    // Set success message only if no errors
                    successMessage.value = 'Registration successful! Please check your email to verify your account.';
                    registrationSuccessful.value = true; // Set registration successful flag to true
                }
            } catch (error) {
                console.log(error)
            }
        };

        return {
            name, email, errors, password,
            street_address, city, state, country,
            postal_code, building_name, floor_number, unit_number,
            registerUser, userStore, responseStore,
            successMessage, registrationSuccessful
        };
    },
};
</script>

<style scoped>
.v-card {
    max-width: 400px;
    margin: 0 auto;
}
</style>
