<template>
    <v-app-bar density="compact">
        <!-- Show Nav Icon Only on Mobile -->
        <v-app-bar-nav-icon @click="drawer = !drawer" />

        <v-btn v-if="!mobile" :class="{ 'v-btn--active': isActive('/') }" text @click="goToLandingPage">
            <v-icon size="32" color="primary">mdi-handshake</v-icon>
            <span class="logo-text">{{ appTitle }}</span>
        </v-btn>

        <v-spacer />

        <!-- Always Show User Menu -->
        <v-menu v-if="user">
            <template #activator="{ props }">
                <v-btn color="primary" v-bind="props" size="small">
                    {{  user.name }}
                    <v-icon right>mdi-menu-down</v-icon>
                </v-btn>
            </template>
            <v-list>
                <v-list-item>
                    <v-btn
                        v-if="user"
                        :class="{ 'v-btn--active': isActive('/logout-page') }"
                        variant="tonal"
                        block
                        color="error"
                        prepend-icon="mdi-logout"
                        text="Logout"
                        @click="goToLogout"
                    ></v-btn>
                </v-list-item>

            
                

                <v-list-item v-for="link in drawerLinks" :key="link">
                    <v-btn
                        :class="{ 'v-btn--active': isActive(link.path) }"
                        :color="isActive(link.path) ? 'primary' : undefined"
                        :variant="isActive(link.path) ? 'flat' : 'text'"
                        :prepend-icon="link.icon"
                        :text="link.text"
                        block
                        :href="link.path"
                    ></v-btn>
                </v-list-item>
            </v-list>
        </v-menu>

        <!-- Additional Links on Larger Screens -->
        <template v-if="!mobile">
            <v-btn
                v-if="!user"
                :color="isActive('/login') ? 'primary' : undefined"
                :variant="isActive('/login') ? 'flat' : 'text'"
                prepend-icon="mdi-login"
                @click="goToLogin"
                >Login</v-btn
            >

            <v-btn
                v-if="!user"
                :color="isActive('/register') ? 'primary' : undefined"
                :variant="isActive('/register') ? 'flat' : 'text'"
                prepend-icon="mdi-account-plus"
                text="Register"
                @click="goToRegister"
            ></v-btn>

            <v-btn
                v-if="!user"
                :color="isActive('/forgot-password') ? 'primary' : undefined"
                :variant="isActive('/forgot-password') ? 'flat' : 'text'"
                prepend-icon="mdi-lock-reset"
                text="Forgot Password"
                @click="goToForgotPassword"
            ></v-btn>
        </template>

        <!-- Catalog - Icon on Mobile, Text on Desktop -->
        <v-btn
            :class="{ 'v-btn--active': isActive('/library-catalog') }"
            :color="isActive('/library-catalog') ? 'primary' : undefined"
            :variant="isActive('/library-catalog') ? 'flat' : 'text'"
            :prepend-icon="mobile ? 'mdi-book' : 'mdi-book'"
            :text="!mobile ? 'Catalog' : undefined"
            @click="router.visit('library-catalog')"
        ></v-btn>

        <!-- Messages - Icon on Mobile (when logged in), Text on Desktop -->
        <v-btn
            v-if="user"
            :class="{ 'v-btn--active': isActive('/messages') }"
            :color="isActive('/messages') ? 'primary' : undefined"
            :variant="isActive('/messages') ? 'flat' : 'text'"
            :prepend-icon="mobile ? 'mdi-message' : 'mdi-message'"
            :text="!mobile ? 'Messages' : undefined"
            @click="router.visit('messages')"
        >
            <template #append>
                <v-badge
                    v-if="notifications?.unread_messages > 0"
                    :content="notifications.unread_messages > 99 ? '99+' : notifications.unread_messages"
                    color="error"
                    offset-x="3"
                    offset-y="-2"
                    style="margin-right: -8px;"
                ></v-badge>
            </template>
        </v-btn>

        <!-- Community - Icon on Mobile (when logged in), Text on Desktop -->
        <v-btn
            v-if="user"
            :class="{ 'v-btn--active': isActive('/community') }"
            :color="isActive('/community') ? 'primary' : undefined"
            :variant="isActive('/community') ? 'flat' : 'text'"
            :prepend-icon="mobile ? 'mdi-account-group' : 'mdi-account-group'"
            :text="!mobile ? 'Community' : undefined"
            href="/community"
        >
            <template #append>
                <span 
                    v-if="notifications?.has_new_community_posts" 
                    class="notification-dot"
                ></span>
            </template>
        </v-btn>

        <v-spacer />

        <v-spacer />
    </v-app-bar>

    <!-- Navigation Drawer for Mobile -->
    <v-navigation-drawer v-model="drawer" temporary>
        <v-divider />

        <!-- App Title -->
        <div class="drawer-btns drawer-header">
            <v-btn
                :class="{ 'v-btn--active': isActive('/') }"
                :color="isActive('/') ? 'primary' : undefined"
                :variant="isActive('/') ? 'flat' : 'text'"
                block
                @click="router.visit('/')"
            >
                <v-icon color="primary">mdi-handshake</v-icon>
                <span class="logo-text">{{ appTitle }}</span>
            </v-btn>
        </div>

        <v-divider />

        <!-- Navigation Buttons -->

        <div class="drawer-btns">
            <v-btn
                :class="{ 'v-btn--active': isActive('/library-catalog') }"
                :color="isActive('/library-catalog') ? 'primary' : undefined"
                :variant="isActive('/library-catalog') ? 'flat' : 'text'"
                prepend-icon="mdi-book"
                text="Catalog"
                @click="router.visit('library-catalog')"
            ></v-btn>
        </div>
        <v-divider />

        <div class="drawer-btns">
            <v-btn
                v-for="link in drawerLinks"
                :key="link"
                :class="{ 'v-btn--active': isActive(link.path) }"
                :color="isActive(link.path) ? 'primary' : undefined"
                :variant="isActive(link.path) ? 'flat' : 'text'"
                :prepend-icon="link.icon"
                :text="link.text"
                @click="router.visit(link.route)"
            ></v-btn>
        </div>
        <v-divider />

        <div class="drawer-btns">
            <v-btn
                v-if="!user"
                :color="isActive('/login') ? 'primary' : undefined"
                :variant="isActive('/login') ? 'flat' : 'text'"
                prepend-icon="mdi-login"
                text="Login"
                @click="goToLogin"
            ></v-btn>

            <v-btn
                v-if="!user"
                :color="isActive('/register') ? 'primary' : undefined"
                :variant="isActive('/register') ? 'flat' : 'text'"
                prepend-icon="mdi-account-plus"
                text="Register"
                @click="goToRegister"
            ></v-btn>

            <v-btn
                v-if="!user"
                :color="isActive('/forgot-password') ? 'primary' : undefined"
                :variant="isActive('/forgot-password') ? 'flat' : 'text'"
                prepend-icon="mdi-lock-reset"
                text="Forgot Password"
                @click="goToForgotPassword"
            ></v-btn>

            <v-btn
                v-if="user"
                :class="{ 'v-btn--active': isActive('/logout-page') }"
                variant="tonal"
                color="error"
                prepend-icon="mdi-logout"
                text="Logout"
                @click="goToLogout"
            ></v-btn>
        </div>
    </v-navigation-drawer>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { useDisplay } from 'vuetify';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const { mobile } = useDisplay();
const drawer = ref(false);
const appTitle = import.meta.env.VITE_APP_NAME;
const user = page.props.auth?.user;
const notifications = page.props.notifications;

// Use window.location for reliable route matching - always reflects actual browser URL
const currentPath = computed(() => window.location.pathname);

const isActive = (path) => currentPath.value === path;

const drawerLinks = ref([]);

onMounted(async () => {
    setupLinks();
});

const setupLinks = () => {
    if (user) {
        drawerLinks.value = [
            { text: 'My Usage', route: 'my-usage', path: '/my-usage' },
            { text: 'My Offerings', route: 'my-offerings', path: '/my-offerings' },
            { text: 'Messages', route: 'messages', icon: 'mdi-message', path: '/messages' },
            { text: 'Community', route: 'community', icon: 'mdi-account-group', path: '/community' },
            { text: 'Time Credits', route: 'itc', path: '/itc' },
            { text: 'Vote on Rates', route: 'credit-voting', path: '/credit-voting' },
        ];
    } else {
        drawerLinks.value = [];
    }
};

// Watch for changes in the responseStore to display the appropriate snackbar
watch(
    () => user,
    () => {
        setupLinks();
    },
    { deep: true }
);



const goToRegister = () => {
    router.visit('/register');
};

const goToLogin = () => {
    router.visit('/login');
};
const goToForgotPassword = () => {
    router.visit('/forgot-password');
};

const goToLogout = () => {
    router.visit('/logout-page');
};

const goToLandingPage = () => {
    router.visit('/');
};
</script>

<style>
/* Ensure buttons take full width and stack vertically */
.drawer-btns {
    display: flex;
    flex-direction: column; /* vertical stacking */
    width: 100%;
    padding: 8px;
    gap: 8px;
}

/* Custom notification dot for community feed */
.notification-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    background-color: rgb(var(--v-theme-error));
    border-radius: 50%;
    margin-left: 4px;
    margin-right: -4px;
    box-shadow: 0 0 4px rgba(255, 0, 0, 0.4);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.2);
        opacity: 0.8;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
</style>
