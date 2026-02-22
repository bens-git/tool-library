<script setup>
import PageLayout from '@/Layouts/PageLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed, nextTick } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;

const conversations = ref([]);
const selectedConversation = ref(null);
const messages = ref([]);
const newMessage = ref('');
const loading = ref(false);
const sendingMessage = ref(false);
const unreadCounts = ref({});

const fetchConversations = async () => {
    try {
        const response = await axios.get(route('messages.conversations'));
        conversations.value = response.data.data;
        
        // Update unread counts
        conversations.value.forEach(conv => {
            unreadCounts.value[conv.id] = conv.unread_count || 0;
        });
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
    if (conversation.rental) {
        return conversation.rental.item?.name || 'Rental Chat';
    }
    return conversation.other_participant?.name || 'Chat';
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
                                        <div class="text-caption mt-1" :class="message.user_id === user.id ? 'text-white' : 'text-grey'">
                                            {{ formatTime(message.created_at) }}
                                        </div>
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
</style>

