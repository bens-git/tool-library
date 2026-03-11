<script setup>
import PageLayout from '@/Layouts/PageLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed, nextTick } from 'vue';
import axios from 'axios';
import { router, usePage } from '@inertiajs/vue3';
import { useResponseStore } from '@/Stores/response';

const responseStore = useResponseStore();

const page = usePage();
const user = page.props.auth.user;

const conversations = ref([]);
const selectedConversation = ref(null);
const messages = ref([]);
const newMessage = ref('');
const loading = ref(false);
const sendingMessage = ref(false);
const unreadCounts = ref({});

// Poll creation
const showPollDialog = ref(false);
const pollQuestion = ref('');
const pollOptions = ref(['', '']);
const isMultipleChoice = ref(false);
const creatingPoll = ref(false);

// Reaction emoji picker
const showReactionPicker = ref(null); // message ID that has picker open
const availableEmojis = ['👍', '👎', '❤️', '😂', '😮', '😢', '🎉', '🔥'];

const fetchConversations = async () => {
    try {
        const response = await axios.get(route('messages.conversations'));
        conversations.value = response.data.data;
        
        // Update unread counts
        conversations.value.forEach(conv => {
            unreadCounts.value[conv.id] = conv.unread_count || 0;
        });

        // Check for conversation query parameter from page props
        const queryParams = page.props.queryParams || {};
        const conversationId = queryParams.conversation;
        if (conversationId) {
            const conv = conversations.value.find(c => c.id === parseInt(conversationId));
            if (conv) {
                selectConversation(conv);
            }
        }
    } catch (error) {
        console.error('Error fetching conversations:', error);
    }
};

const selectConversation = async (conversation) => {
    selectedConversation.value = conversation;
    await fetchMessages(conversation.id);
    
    // Reset unread count
    unreadCounts.value[conversation.id] = 0;
};

const fetchMessages = async (conversationId) => {
    loading.value = true;
    try {
        const response = await axios.get(route('messages.conversations.show', { conversationId }));
        messages.value = response.data.messages;
        
        // Scroll to bottom
        await nextTick();
        scrollToBottom();
        
        // Refresh the global notifications to update unread count
        router.reload({ only: ['notifications'] });
    } catch (error) {
        console.error('Error fetching messages:', error);
    } finally {
        loading.value = false;
    }
};

const sendMessage = async () => {
    if (!newMessage.value.trim() || !selectedConversation.value) return;
    
    sendingMessage.value = true;
    try {
        const response = await axios.post(
            route('messages.conversations.store', { conversationId: selectedConversation.value.id }),
            { body: newMessage.value }
        );
        
        messages.value.push(response.data.data);
        newMessage.value = '';
        
        // Scroll to bottom
        await nextTick();
        scrollToBottom();
    } catch (error) {
        console.error('Error sending message:', error);
    } finally {
        sendingMessage.value = false;
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
    if (!selectedConversation.value) return;

    creatingPoll.value = true;
    try {
        // First create a message
        const messageResponse = await axios.post(
            route('messages.conversations.store', { conversationId: selectedConversation.value.id }),
            { body: `📊 ${pollQuestion.value}` }
        );
        
        const messageId = messageResponse.data.data.id;
        
        // Then create the poll attached to that message
        await axios.post(route('messages.poll.create', { messageId }), {
            question: pollQuestion.value,
            options: pollOptions.value.filter(o => o.trim()),
            is_multiple_choice: isMultipleChoice.value,
        });
        
        responseStore.setSuccess('Poll created successfully');
        
        // Close dialog and refresh messages
        showPollDialog.value = false;
        pollQuestion.value = '';
        pollOptions.value = ['', ''];
        isMultipleChoice.value = false;
        
        await fetchMessages(selectedConversation.value.id);
    } catch (error) {
        console.error('Error creating poll:', error);
        responseStore.setError(error.response?.data?.message || 'Failed to create poll');
    } finally {
        creatingPoll.value = false;
    }
};

const votePoll = async (poll, optionId) => {
    if (!poll.can_vote) return;
    
    try {
        const response = await axios.post(route('messages.poll.vote', { pollId: poll.id }), {
            option_ids: [optionId],
        });
        
        // Update the poll in messages
        const messageIndex = messages.value.findIndex(m => m.poll && m.poll.id === poll.id);
        if (messageIndex !== -1) {
            messages.value[messageIndex].poll = response.data.poll;
        }
    } catch (error) {
        console.error('Error voting on poll:', error);
        responseStore.setError(error.response?.data?.message || 'Failed to vote');
    }
};

const closePoll = async (poll) => {
    try {
        const response = await axios.post(route('messages.poll.close', { pollId: poll.id }));
        
        // Update the poll in messages
        const messageIndex = messages.value.findIndex(m => m.poll && m.poll.id === poll.id);
        if (messageIndex !== -1) {
            messages.value[messageIndex].poll = response.data.poll;
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
        
        // Update reactions in messages
        const messageIndex = messages.value.findIndex(m => m.id === messageId);
        if (messageIndex !== -1) {
            messages.value[messageIndex].reactions = response.data.reactions;
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
        
        // Update reactions in messages
        const messageIndex = messages.value.findIndex(m => m.id === messageId);
        if (messageIndex !== -1) {
            messages.value[messageIndex].reactions = response.data.reactions;
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

const scrollToBottom = () => {
    const container = document.querySelector('.messages-container');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
};

const formatTime = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    
    if (date.toDateString() === today.toDateString()) {
        return 'Today';
    } else if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    }
    return date.toLocaleDateString();
};

const getConversationTitle = (conversation) => {
    if (conversation.usage) {
        return conversation.usage.item?.name || 'Usage Chat';
    }
    return conversation.other_participant?.name || 'Chat';
};

const getPollPercentage = (option, poll) => {
    if (poll.total_votes === 0) return 0;
    return Math.round((option.vote_count / poll.total_votes) * 100);
};

const totalUnread = computed(() => {
    return Object.values(unreadCounts.value).reduce((sum, count) => sum + count, 0);
});

onMounted(() => {
    fetchConversations();
});
</script>

<template>
    <PageLayout>
        <Head title="Messages" />

        <v-container fluid class="pa-4" style="height: calc(100vh - 100px);">
            <v-row class="fill-height">
                <!-- Conversations List -->
                <v-col cols="12" md="4" class="border-e">
                    <div class="d-flex justify-space-between align-center mb-4">
                        <div class="text-h5 font-weight-bold">Messages</div>
                        <v-badge v-if="totalUnread > 0" :content="totalUnread" color="error">
                        </v-badge>
                    </div>

                    <v-list v-if="conversations.length" lines="two">
                        <v-list-item
                            v-for="conv in conversations"
                            :key="conv.id"
                            :active="selectedConversation?.id === conv.id"
                            class="mb-2 rounded"
                            :class="{ 'bg-blue-lighten-5': selectedConversation?.id === conv.id }"
                            @click="selectConversation(conv)"
                        >
                            <template #prepend>
                                <v-avatar color="primary" size="40">
                                    <span class="text-white">{{ getConversationTitle(conv).charAt(0).toUpperCase() }}</span>
                                </v-avatar>
                            </template>
                            
                            <v-list-item-title class="font-weight-bold">
                                {{ getConversationTitle(conv) }}
                            </v-list-item-title>
                            
                            <v-list-item-subtitle v-if="conv.latest_message">
                                <span v-if="conv.latest_message.is_system_message" class="text-blue">
                                    📢 System
                                </span>
                                {{ conv.latest_message.body.substring(0, 40) }}{{ conv.latest_message.body.length > 40 ? '...' : '' }}
                            </v-list-item-subtitle>
                            
                            <template #append>
                                <div class="d-flex flex-column align-end">
                                    <small v-if="conv.latest_message">{{ formatDate(conv.latest_message.created_at) }}</small>
                                    <v-badge 
                                        v-if="unreadCounts[conv.id] > 0" 
                                        :content="unreadCounts[conv.id]" 
                                        color="error" 
                                        inline
                                    ></v-badge>
                                </div>
                            </template>
                        </v-list-item>
                    </v-list>
                    
                    <div v-else class="text-center mt-8">
                        <v-icon size="64" color="grey">mdi-message-text-outline</v-icon>
                        <div class="text-h6 mt-2">No conversations yet</div>
                        <div class="text-body-2 text-grey">Rent a tool to start chatting!</div>
                    </div>
                </v-col>

                <!-- Chat Window -->
                <v-col cols="12" md="8" class="d-flex flex-column">
                    <template v-if="selectedConversation">
                        <!-- Chat Header -->
                        <div class="d-flex align-center pa-4 border-b">
                            <v-avatar color="primary" size="40" class="mr-3">
                                <span class="text-white">{{ getConversationTitle(selectedConversation).charAt(0).toUpperCase() }}</span>
                            </v-avatar>
                            <div>
                                <div class="text-h6">{{ getConversationTitle(selectedConversation) }}</div>
                                <small v-if="selectedConversation.other_participant" class="text-grey">
                                    {{ selectedConversation.other_participant.email }}
                                </small>
                            </div>
                            <v-spacer></v-spacer>
                            <v-btn 
                                icon="mdi-poll" 
                                variant="text" 
                                size="small"
                                @click="showPollDialog = true"
                            >
                                <v-icon>mdi-poll</v-icon>
                                <v-tooltip activator="parent">Create Poll</v-tooltip>
                            </v-btn>
                        </div>

                        <!-- Messages -->
                        <div class="messages-container flex-grow-1 overflow-y-auto pa-4" style="max-height: calc(100% - 120px);">
                            <div v-if="loading" class="text-center">
                                <v-progress-circular indeterminate color="primary"></v-progress-circular>
                            </div>
                            
                            <div v-else>
                                <div
                                    v-for="message in messages"
                                    :key="message.id"
                                    class="mb-4"
                                    :class="message.user_id === user.id ? 'text-right' : 'text-left'"
                                >
                                    <v-chip
                                        v-if="message.is_system_message"
                                        color="blue"
                                        size="small"
                                        class="mb-1"
                                    >
                                        <v-icon start size="small">mdi-information</v-icon>
                                        System
                                    </v-chip>
                                    
                                    <div
                                        class="d-inline-block pa-3 rounded-lg"
                                        :class="message.user_id === user.id ? 'bg-primary text-white' : 'bg-grey-lighten-3'"
                                        :style="{ maxWidth: '80%', wordWrap: 'break-word' }"
                                    >
                                        <div v-if="!message.is_system_message && message.user_id !== user.id" class="text-caption font-weight-bold mb-1">
                                            {{ message.user?.name }}
                                        </div>
                                        {{ message.body }}
                                        
                                        <!-- Poll Display -->
                                        <div v-if="message.poll" class="mt-3 pa-2 rounded border">
                                            <div class="font-weight-bold mb-2">{{ message.poll.question }}</div>
                                            <div v-for="option in message.poll.options" :key="option.id" class="mb-2">
                                                <v-btn
                                                    :variant="option.has_voted ? 'flat' : 'outlined'"
                                                    :color="option.has_voted ? 'primary' : 'default'"
                                                    size="small"
                                                    block
                                                    class="mb-1"
                                                    :disabled="!message.poll.can_vote"
                                                    @click="votePoll(message.poll, option.id)"
                                                >
                                                    <div class="d-flex justify-space-between align-center w-100">
                                                        <span>{{ option.option_text }}</span>
                                                        <span>{{ getPollPercentage(option, message.poll) }}%</span>
                                                    </div>
                                                </v-btn>
                                            </div>
                                            <div class="text-caption mt-2">
                                                {{ message.poll.total_votes }} vote(s)
                                                <span v-if="message.poll.is_closed" class="text-error ml-2">Closed</span>
                                                <span v-else-if="message.poll.has_expired" class="text-warning ml-2">Expired</span>
                                            </div>
                                            <v-btn 
                                                v-if="message.poll.can_vote && message.poll.is_multiple_choice" 
                                                size="x-small" 
                                                variant="text" 
                                                class="mt-1"
                                            >
                                                Select multiple
                                            </v-btn>
                                            <v-btn 
                                                v-if="!message.poll.can_vote && message.user_id === user.id && !message.poll.is_closed"
                                                size="x-small" 
                                                color="error" 
                                                variant="text" 
                                                class="mt-1"
                                                @click="closePoll(message.poll)"
                                            >
                                                Close Poll
                                            </v-btn>
                                        </div>

                                        <div class="text-caption mt-1" :class="message.user_id === user.id ? 'text-white' : 'text-grey'">
                                            {{ formatTime(message.created_at) }}
                                        </div>
                                    </div>
                                    
                                    <!-- Reactions Display -->
                                    <div v-if="message.reactions && message.reactions.length" class="mt-1">
                                        <v-chip
                                            v-for="reaction in message.reactions"
                                            :key="reaction.emoji"
                                            size="x-small"
                                            class="mr-1"
                                            :color="hasUserReacted(message.reactions, reaction.emoji) ? 'primary' : 'default'"
                                            @click="removeReaction(message.id, reaction.emoji)"
                                        >
                                            {{ reaction.emoji }} {{ reaction.count }}
                                            <v-tooltip activator="parent" location="top">{{ reaction.user_names?.join(', ') || 'No users' }}</v-tooltip>
                                        </v-chip>
                                    </div>
                                    
                                    <!-- Add Reaction Button -->
                                    <div class="mt-1">
                                        <v-menu :model-value="showReactionPicker === message.id" :close-on-content-click="false" location="top start">
                                            <template #activator="{ props }">
                                                <v-btn 
                                                    icon="mdi-emoticon-outline" 
                                                    variant="text" 
                                                    size="x-small"
                                                    v-bind="props"
                                                    @click="toggleReactionPicker(message.id)"
                                                >
                                                    <v-icon size="small">mdi-emoticon-outline</v-icon>
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
                                                            @click="addReaction(message.id, emoji)"
                                                        >
                                                            {{ emoji }}
                                                        </v-btn>
                                                    </div>
                                                </v-card-text>
                                            </v-card>
                                        </v-menu>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Message Input -->
                        <div class="pa-4 border-t">
                            <v-text-field
                                v-model="newMessage"
                                placeholder="Type a message..."
                                variant="outlined"
                                density="compact"
                                hide-details
                                append-inner-icon="mdi-send"
                                :disabled="sendingMessage"
                                @click:append-inner="sendMessage"
                                @keyup.enter="sendMessage"
                            ></v-text-field>
                        </div>
                    </template>
                    
                    <div v-else class="d-flex align-center justify-center fill-height">
                        <div class="text-center">
                            <v-icon size="64" color="grey">mdi-message-text</v-icon>
                            <div class="text-h6 mt-2">Select a conversation</div>
                            <div class="text-body-2 text-grey">Choose a conversation from the list to start chatting</div>
                        </div>
                    </div>
                </v-col>
            </v-row>
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
.border-e {
    border-right: 1px solid #e0e0e0;
}

.border-b {
    border-bottom: 1px solid #e0e0e0;
}

.border-t {
    border-top: 1px solid #e0e0e0;
}

.border {
    border: 1px solid #e0e0e0;
}
</style>

