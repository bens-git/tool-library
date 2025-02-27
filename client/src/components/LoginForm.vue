<template>
    <v-card class="my-5">
        <v-card-title>
            Login
        </v-card-title>
        <v-card-text>
            <v-form @submit.prevent="login">
                <v-text-field v-model="email" name="email" autocomplete="email" label="Email"
                    :error-messages="responseStore?.response?.errors[0]?.email" required />

                <v-text-field v-model="password" name="password" autocomplete="current-password" label="Password"
                    type="password" :error-messages="responseStore?.response?.errors[0]?.password" required />

                <v-btn type="submit" color="primary">
                    Login
                </v-btn>
            </v-form>
            <div v-if="userStore.user" class="mt-3">
                Logged in as: {{ userStore.user.name }}
            </div>
        </v-card-text>
    </v-card>
</template>
<script setup>
import { ref } from 'vue';
import { useUserStore } from '../stores/user';
import { useRouter } from "vue-router";
import { useResponseStore } from '../stores/response';

const email = ref('');
const password = ref('');
const userStore = useUserStore();
const errors = ref({});
const generalError = ref('');
const router = useRouter();
const responseStore = useResponseStore();

const login = async () => {
    errors.value = {};
    responseStore.clearResponse(); // Clear previous responses
    try {
        await userStore.login(email.value, password.value);

        if (!responseStore.response?.success) {
            errors.value = responseStore.response.errors.errors || {}; // Ensure it's an object
        } else {
            router.push({ name: 'resource-archetype-list' }); // Use router to navigate to login page after logout
        }

    } catch (error) {
        console.log(error);
    }
};

const logout = async () => {
    await userStore.logout();
};
</script>

<style scoped>
.v-card {
    max-width: 400px;
    margin: 0 auto;
}
</style>
