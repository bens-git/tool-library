<template>
    <v-card class="my-5">
        <v-card-title>
            Link to Discord
        </v-card-title>
        <v-card-text>

            <v-alert v-if="success" :text="success" title="Link Succeeded" type="success"></v-alert>

            <v-alert v-else :text="error" title="Link Failed" type="error"></v-alert>


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
    if (code) {

        await userStore.linkWithDiscord(code);
        if (!responseStore.response?.success) {
            error.value = responseStore.response?.message
        } else {
            success.value = responseStore.response?.message

        }



    }
    else {
        error.value = 'Unable to link with discord.'
    }
});

</script>

<style scoped>
.v-card {
    max-width: 400px;
    margin: 0 auto;
}
</style>
