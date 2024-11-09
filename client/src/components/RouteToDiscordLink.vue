<template>
    <v-card class="my-5">
        <v-card-title>
            Link to Discord
        </v-card-title>
        <v-card-text>

            <v-alert v-if="!userStore.user.discord_id"
                text="By clicking the 'Link To Discord' button, your Tool-Library account will be securely linked to your Discord account. This action will connect your account IDs and enable enhanced integration between the two platforms. This action is required prior to renting or loaning tools."
                title="Discord Not Authenticated" type="warning"></v-alert>
            <br>

            <v-btn color="primary" @click="userStore.loginToDiscord()">
                Link With Discord
            </v-btn>

        </v-card-text>
    </v-card>
</template>
<script setup>
import { useUserStore } from '../stores/user';
import { useRoute } from 'vue-router';
import { useResponseStore } from '../stores/response';
import { ref, onMounted } from 'vue';

const userStore = useUserStore();
const responseStore = useResponseStore();
const route = useRoute();
const success = ref(null)
const error = ref(null)

const code = route.query.code

onMounted(async () => {

});

</script>

<style scoped>
.v-card {
    max-width: 400px;
    margin: 0 auto;
}
</style>
