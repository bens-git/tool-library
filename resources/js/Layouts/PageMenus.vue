<template>
    <v-app-bar density="compact">
        <!-- Show Nav Icon Only on Mobile -->
        <v-app-bar-nav-icon @click="drawer = !drawer" />

        <v-btn v-if="!mobile" :class="{ 'v-btn--active': isActive('/landing-page') }" to="/" text>
            <v-icon size="32" color="primary">mdi-hammer</v-icon>
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
                        :class="{ 'v-btn--active': route().current(link.route) }"
                        :color="route().current(link.route) ? 'primary' : undefined"
                        :variant="route().current(link.route) ? 'flat' : 'text'"
                        :prepend-icon="link.icon"
                        :text="link.text"
                        block
                        @click="router.visit(link.route)"
                    ></v-btn>
                </v-list-item>
            </v-list>
        </v-menu>

        <!-- Additional Links on Larger Screens -->
        <template v-if="!mobile">
            <v-btn
                v-if="!user"
                :color="route().current('login') ? 'primary' : undefined"
                :variant="route().current('login') ? 'flat' : 'text'"
                prepend-icon="mdi-login"
                @click="goToLogin"
                >Login</v-btn
            >

            <v-btn
                v-if="!user"
                :color="route().current('register') ? 'primary' : undefined"
                :variant="route().current('register') ? 'flat' : 'text'"
                prepend-icon="mdi-account-plus"
                text="Register"
                @click="goToRegister"
            ></v-btn>

            <v-btn
                v-if="!user"
                :color="route().current('password.request') ? 'primary' : undefined"
                :variant="route().current('password.request') ? 'flat' : 'text'"
                prepend-icon="mdi-lock-reset"
                text="Forgot Password"
                @click="goToForgotPassword"
            ></v-btn>
        </template>

        <!-- Catalog - Icon on Mobile, Text on Desktop -->
        <v-btn
            :class="{ 'v-btn--active': route().current('library-catalog') }"
            :color="route().current('library-catalog') ? 'primary' : undefined"
            :variant="route().current('library-catalog') ? 'flat' : 'text'"
            :prepend-icon="mobile ? 'mdi-book' : 'mdi-book'"
            :text="!mobile ? 'Catalog' : undefined"
            @click="router.visit('library-catalog')"
        ></v-btn>

        <!-- Messages - Icon on Mobile (when logged in), Text on Desktop -->
        <v-btn
            v-if="user"
            :class="{ 'v-btn--active': route().current('messages') }"
            :color="route().current('messages') ? 'primary' : undefined"
            :variant="route().current('messages') ? 'flat' : 'text'"
            :prepend-icon="mobile ? 'mdi-message' : 'mdi-message'"
            :text="!mobile ? 'Messages' : undefined"
            @click="router.visit('messages')"
        >
            <template #append>
                <v-badge
                    v-if="notifications?.unread_messages > 0"
                    :content="notifications.unread_messages"
                    color="error"
                    inline
                ></v-badge>
            </template>
        </v-btn>

        <!-- Community - Icon on Mobile (when logged in), Text on Desktop -->
        <v-btn
            v-if="user"
            :class="{ 'v-btn--active': route().current('community') }"
            :color="route().current('community') ? 'primary' : undefined"
            :variant="route().current('community') ? 'flat' : 'text'"
            :prepend-icon="mobile ? 'mdi-account-group' : 'mdi-account-group'"
            :text="!mobile ? 'Community' : undefined"
            @click="router.visit('community')"
        >
            <template #append>
                <v-badge
                    v-if="notifications?.has_new_community_posts"
                    dot
                    color="error"
                    inline
                ></v-badge>
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
                :class="{ 'v-btn--active': route().current('landing-page') }"
                :color="route().current('landing-page') ? 'primary' : undefined"
                :variant="route().current('landing-page') ? 'flat' : 'text'"
                block
                @click="router.visit('/')"
            >
                <v-icon color="primary">mdi-hammer</v-icon>
                <span class="logo-text">{{ appTitle }}</span>
            </v-btn>
        </div>

        <v-divider />

        <!-- Navigation Buttons -->

        <div class="drawer-btns">
            <v-btn
                :class="{ 'v-btn--active': route().current('library-catalog') }"
                :color="route().current('library-catalog') ? 'primary' : undefined"
                :variant="route().current('library-catalog') ? 'flat' : 'text'"
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
                :class="{ 'v-btn--active': route().current(link.route) }"
                :color="route().current(link.route) ? 'primary' : undefined"
                :variant="route().current(link.route) ? 'flat' : 'text'"
                :prepend-icon="link.icon"
                :text="link.text"
                @click="router.visit(link.route)"
            ></v-btn>
        </div>
        <v-divider />

        <div class="drawer-btns">
            <v-btn
                v-if="!user"
                :color="route().current('login') ? 'primary' : undefined"
                :variant="route().current('login') ? 'flat' : 'text'"
                prepend-icon="mdi-login"
                text="Login"
                @click="goToLogin"
            ></v-btn>

            <v-btn
                v-if="!user"
                :color="route().current('register') ? 'primary' : undefined"
                :variant="route().current('register') ? 'flat' : 'text'"
                prepend-icon="mdi-account-plus"
                text="Register"
                @click="goToRegister"
            ></v-btn>

            <v-btn
                v-if="!user"
                :color="route().current('password.request') ? 'primary' : undefined"
                :variant="route().current('password.request') ? 'flat' : 'text'"
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
import { ref, onMounted, watch } from 'vue';
import { useDisplay } from 'vuetify';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();

const { mobile } = useDisplay();
const drawer = ref(false);
const appTitle = import.meta.env.VITE_APP_NAME;
const user = page.props.auth?.user;
const notifications = page.props.notifications;

const drawerLinks = ref([]);

const isActive = (path) => route.path === path;

onMounted(async () => {
    setupLinks();
});

const setupLinks = () => {
    if (user) {
        drawerLinks.value = [
            { text: 'My Loans', route: 'my-loans' },
            { text: 'My Rentals', route: 'my-rentals' },
            { text: 'Messages', route: 'messages', icon: 'mdi-message' },
            { text: 'Community', route: 'community', icon: 'mdi-account-group' },
            { text: 'Time Credits', route: 'itc' },
            { text: 'Vote on Rates', route: 'credit-voting' },
            { text: 'My Types', route: 'archetype-list' },
            { text: 'My Categories', route: 'category-list' },
            { text: 'My Usages', route: 'usage-list' },
            { text: 'My Brands', route: 'brand-list' },
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
</style>
