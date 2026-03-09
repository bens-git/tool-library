<script setup>
import PageLayout from '@/Layouts/PageLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { router, usePage } from '@inertiajs/vue3';
import { useResponseStore } from '@/Stores/response';

const responseStore = useResponseStore();

const page = usePage();
const user = page.props.auth.user;

const posts = ref([]);
const newPost = ref('');
const loading = ref(false);
const posting = ref(false);

// Poll creation
const showPollDialog = ref(false);
const pollQuestion = ref('');
const pollOptions = ref(['', '']);
const isMultipleChoice = ref(false);
const creatingPoll = ref(false);

// Reaction emoji picker
const showReactionPicker = ref(null); // message ID that has picker open
const availableEmojis = ['👍', '👎', '❤️', '😂', '😮', '😢', '🎉', '🔥'];

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
        
        responseStore.setSuccess('Post created successfully');
        // Refresh posts to show the new one
        await fetchPosts();
        newPost.value = '';
    } catch (error) {
        console.error('Error creating post:', error);
        responseStore.setError(error.response?.data?.message || 'Failed to create post');
    } finally {
        posting.value = false;
    }
};

// Poll functions
const addPollOption = () => {
    if (pollOptions.value.length < 10) {
        pollOptions.value.push('');
    }
};

const removePollOption = (index) => {
    if (pollOptions.value.length > 2) {
        pollOptions.value.splice(index, 1);
    }
};

const createPoll = async () => {
    if (!pollQuestion.value.trim() || pollOptions.value.some(o => !o.trim())) return;

    creatingPoll.value = true;
    try {
        // First create a message
        const messageResponse = await axios.post(route('messages.public.create'), {
            body: `📊 ${pollQuestion.value}`
        });
        
        const messageId = messageResponse.data.data.id;
        
        // Then create the poll attached to that message
        await axios.post(route('messages.poll.create', { messageId }), {
            question: pollQuestion.value,
            options: pollOptions.value.filter(o => o.trim()),
            is_multiple_choice: isMultipleChoice.value,
        });
        
        responseStore.setSuccess('Poll created successfully');
        
        // Close dialog and refresh posts
        showPollDialog.value = false;
        pollQuestion.value = '';
        pollOptions.value = ['', ''];
        isMultipleChoice.value = false;
        
        await fetchPosts();
    } catch (error) {
        console.error('Error creating poll:', error);
        responseStore.setError(error.response?.data?.message || 'Failed to create poll');
    } finally {
        creatingPoll.value = false;
    }
};

const votePoll = async (post, optionId) => {
    if (!post.poll || !post.poll.can_vote) return;
    
    try {
        const response = await axios.post(route('messages.poll.vote', { pollId: post.poll.id }), {
            option_ids: [optionId],
        });
        
        // Update the poll in posts
        const postIndex = posts.value.findIndex(p => p.poll && p.poll.id === post.poll.id);
        if (postIndex !== -1) {
            posts.value[postIndex].poll = response.data.poll;
        }
    } catch (error) {
        console.error('Error voting on poll:', error);
        responseStore.setError(error.response?.data?.message || 'Failed to vote');
    }
};

const closePoll = async (post) => {
    if (!post.poll) return;
    
    try {
        const response = await axios.post(route('messages.poll.close', { pollId: post.poll.id }));
        
        // Update the poll in posts
        const postIndex = posts.value.findIndex(p => p.poll && p.poll.id === post.poll.id);
        if (postIndex !== -1) {
            posts.value[postIndex].poll = response.data.poll;
        }
    } catch (error) {
        console.error('Error closing poll:', error);
        responseStore.setError(error.response?.data?.message || 'Failed to close poll');
    }
};

// Reaction functions
const toggleReactionPicker = (messageId) => {
    if (showReactionPicker.value === messageId) {
        showReactionPicker.value = null;
    } else {
        showReactionPicker.value = messageId;
    }
};

const addReaction = async (messageId, emoji) => {
    try {
        const response = await axios.post(route('messages.reaction.add', { messageId }), { emoji });
        
        // Update reactions in posts
        const postIndex = posts.value.findIndex(p => p.id === messageId);
        if (postIndex !== -1) {
            posts.value[postIndex].reactions = response.data.reactions;
        }
        
        showReactionPicker.value = null;
    } catch (error) {
        console.error('Error adding reaction:', error);
    }
};

const removeReaction = async (messageId, emoji) => {
    try {
        const response = await axios.delete(route('messages.reaction.remove', { messageId }), {
            data: { emoji },
        });
        
        // Update reactions in posts
        const postIndex = posts.value.findIndex(p => p.id === messageId);
        if (postIndex !== -1) {
            posts.value[postIndex].reactions = response.data.reactions;
        }
    } catch (error) {
        console.error('Error removing reaction:', error);
    }
};

const hasUserReacted = (reactions, emoji) => {
    if (!reactions) return false;
    const reaction = reactions.find(r => r.emoji === emoji);
    return reaction && reaction.user_ids.includes(user.id);
};

const markCommunityVisited = async () => {
    try {
        await axios.post(route('messages.community.visited'));
        
        // Wait a brief moment for the database to update, then refresh notifications
        await new Promise(resolve => setTimeout(resolve, 100));
        
        // Refresh the global notifications to update the community badge
        router.reload({ only: ['notifications'] });
    } catch (error) {
        console.error('Error marking community visited:', error);
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

const getPollPercentage = (option, poll) => {
    if (poll.total_votes === 0) return 0;
    return Math.round((option.vote_count / poll.total_votes) * 100);
};

onMounted(() => {
    fetchPosts();
    markCommunityVisited();
});
</script>

<template>
    <PageLayout>
        <Head title="Community Feed" />

        <v-container fluid class="pa-4" style="max-width: 800px; margin: 0 auto;">
            <div class="d-flex justify-space-between align-center mb-4">
                <div class="text-h4 font-weight-bold">Community Feed</div>
                <v-btn 
                    icon="mdi-poll" 
                    color="primary"
                    @click="showPollDialog = true"
                >
                    <v-icon>mdi-poll</v-icon>
                    <v-tooltip activator="parent">Create Poll</v-tooltip>
                </v-btn>
            </div>
            
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
                
                <!-- Poll Display -->
                <div v-if="post.poll" class="mt-3 pa-3 rounded border">
                    <div class="font-weight-bold mb-2">{{ post.poll.question }}</div>
                    <div v-for="option in post.poll.options" :key="option.id" class="mb-2">
                        <v-btn
                            :variant="option.has_voted ? 'flat' : 'outlined'"
                            :color="option.has_voted ? 'primary' : 'default'"
                            size="small"
                            block
                            class="mb-1"
                            :disabled="!post.poll.can_vote"
                            @click="votePoll(post, option.id)"
                        >
                            <div class="d-flex justify-space-between align-center w-100">
                                <span>{{ option.option_text }}</span>
                                <span>{{ getPollPercentage(option, post.poll) }}%</span>
                            </div>
                        </v-btn>
                    </div>
                    <div class="text-caption mt-2">
                        {{ post.poll.total_votes }} vote(s)
                        <span v-if="post.poll.is_closed" class="text-error ml-2">Closed</span>
                        <span v-else-if="post.poll.has_expired" class="text-warning ml-2">Expired</span>
                    </div>
                    <v-btn 
                        v-if="!post.poll.can_vote && post.user_id === user.id && !post.poll.is_closed"
                        size="x-small" 
                        color="error" 
                        variant="text" 
                        class="mt-2"
                        @click="closePoll(post)"
                    >
                        Close Poll
                    </v-btn>
                </div>
                
                <!-- Reactions Display -->
                <div class="mt-3 d-flex align-center">
                    <div v-if="post.reactions && post.reactions.length" class="d-flex flex-wrap">
                        <v-chip
                            v-for="reaction in post.reactions"
                            :key="reaction.emoji"
                            size="small"
                            class="mr-1 mb-1"
                            :color="hasUserReacted(post.reactions, reaction.emoji) ? 'primary' : 'default'"
                            @click="removeReaction(post.id, reaction.emoji)"
                        >
                            {{ reaction.emoji }} {{ reaction.count }}
                        </v-chip>
                    </div>
                    
                    <!-- Add Reaction Button -->
                    <v-menu :model-value="showReactionPicker === post.id" :close-on-content-click="false" location="top start">
                        <template #activator="{ props }">
                            <v-btn 
                                icon="mdi-emoticon-outline" 
                                variant="text" 
                                size="small"
                                v-bind="props"
                                @click="toggleReactionPicker(post.id)"
                            >
                                <v-icon>mdi-emoticon-outline</v-icon>
                            </v-btn>
                        </template>
                        <v-card width="200">
                            <v-card-text class="pa-2">
                                <div class="d-flex flex-wrap">
                                    <v-btn
                                        v-for="emoji in availableEmojis"
                                        :key="emoji"
                                        size="small"
                                        variant="text"
                                        @click="addReaction(post.id, emoji)"
                                    >
                                        {{ emoji }}
                                    </v-btn>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-menu>
                </div>
            </v-card>
        </v-container>

        <!-- Create Poll Dialog -->
        <v-dialog v-model="showPollDialog" max-width="500">
            <v-card>
                <v-card-title class="text-h5">Create Poll</v-card-title>
                <v-card-text>
                    <v-text-field
                        v-model="pollQuestion"
                        label="Question"
                        variant="outlined"
                        class="mb-3"
                    ></v-text-field>
                    
                    <div class="mb-3">
                        <div class="text-subtitle-2 mb-2">Options</div>
                        <div v-for="(option, index) in pollOptions" :key="index" class="d-flex align-center mb-2">
                            <v-text-field
                                v-model="pollOptions[index]"
                                :label="`Option ${index + 1}`"
                                variant="outlined"
                                density="compact"
                                hide-details
                                class="mr-2"
                            ></v-text-field>
                            <v-btn 
                                icon="mdi-close" 
                                variant="text" 
                                size="small"
                                :disabled="pollOptions.length <= 2"
                                @click="removePollOption(index)"
                            ></v-btn>
                        </div>
                        <v-btn 
                            variant="text" 
                            size="small" 
                            :disabled="pollOptions.length >= 10"
                            @click="addPollOption"
                        >
                            + Add Option
                        </v-btn>
                    </div>
                    
                    <v-switch
                        v-model="isMultipleChoice"
                        label="Allow multiple choices"
                        hide-details
                    ></v-switch>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="showPollDialog = false">Cancel</v-btn>
                    <v-btn 
                        color="primary" 
                        :loading="creatingPoll"
                        :disabled="!pollQuestion.trim() || pollOptions.some(o => !o.trim())"
                        @click="createPoll"
                    >
                        Create Poll
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </PageLayout>
</template>

<style scoped>
.border {
    border: 1px solid #e0e0e0;
}
</style>
