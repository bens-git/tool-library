<script setup>
import PageLayout from '@/Layouts/PageLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;

const posts = ref([]);
const newPost = ref('');
const loading = ref(false);
const posting = ref(false);

const fetchPosts = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('messages.public'));
        posts.value = response.data.data;
    } catch (error) {
        console.error('Error fetching posts:', error);
    } finally {
        loading.value = false;
    }
};

const createPost = async () => {
    if (!newPost.value.trim()) return;
    
    posting.value = true;
    try {
        await axios.post(route('messages.public.create'), {
            body: newPost.value
        });
        
        // Refresh posts to show the new one
        await fetchPosts();
        newPost.value = '';
    } catch (error) {
        console.error('Error creating post:', error);
    } finally {
        posting.value = false;
    }
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const getUserInitial = (name) => {
    if (!name) return '?';
    return name.charAt(0).toUpperCase();
};

onMounted(() => {
    fetchPosts();
});
</script>

<template>
    <PageLayout>
        <Head title="Community Feed" />

        <v-container fluid class="pa-4" style="max-width: 800px; margin: 0 auto;">
            <div class="text-h4 font-weight-bold mb-4">Community Feed</div>
            
            <!-- Create Post -->
            <v-card v-if="user" class="mb-6 pa-4">
                <div class="text-subtitle-1 font-weight-bold mb-2">Share something with the community</div>
                <v-textarea
                    v-model="newPost"
                    placeholder="What's on your mind?"
                    variant="outlined"
                    rows="3"
                    hide-details
                ></v-textarea>
                <div class="d-flex justify-end mt-2">
                    <v-btn 
                        color="primary" 
                        :loading="posting"
                        :disabled="!newPost.trim()"
                        @click="createPost"
                    >
                        Post
                    </v-btn>
                </div>
            </v-card>
            
            <!-- Posts Feed -->
            <div v-if="loading" class="text-center py-8">
                <v-progress-circular indeterminate color="primary"></v-progress-circular>
            </div>
            
            <div v-else-if="posts.length === 0" class="text-center py-8">
                <v-icon size="64" color="grey">mdi-post-outline</v-icon>
                <div class="text-h6 mt-2">No posts yet</div>
                <div class="text-body-2 text-grey">Be the first to share something!</div>
            </div>
            
            <v-card v-for="post in posts" :key="post.id" class="mb-4 pa-4">
                <div class="d-flex align-center mb-3">
                    <v-avatar color="primary" size="40" class="mr-3">
                        <span class="text-white">{{ getUserInitial(post.user?.name) }}</span>
                    </v-avatar>
                    <div>
                        <div class="font-weight-bold">{{ post.user?.name || 'Unknown' }}</div>
                        <small class="text-grey">{{ formatDate(post.created_at) }}</small>
                    </div>
                </div>
                <div class="text-body-1">
                    {{ post.body }}
                </div>
            </v-card>
        </v-container>
    </PageLayout>
</template>

