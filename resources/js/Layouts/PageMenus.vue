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
                    {{
                        user.discord_username
                            ? user.discord_username
                            : user.name
                    }}
                    <v-icon right>mdi-menu-down</v-icon>
                </v-btn>
            </template>
            <v-list>
                <v-list-item @click="myRentals">
                    <v-list-item-title>My Rentals</v-list-item-title>
                </v-list-item>
                <v-list-item @click="myLoans">
                    <v-list-item-title>My Loans</v-list-item-title>
                </v-list-item>

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
            </v-list>
        </v-menu>

        <!-- Additional Links on Larger Screens -->
        <template v-if="!mobile">
            <v-btn
                v-if="!user"
                :class="{ 'v-btn--active': isActive('/login') }"
                variant="flat"
                color="success"
                prepend-icon="mdi-login"
                text="Login"
                @click="goToLogin"
            ></v-btn>

            

            <v-btn
                v-if="!user"
                :class="{ 'v-btn--active': isActive('/register') }"
                variant="tonal"
                color="secondary"
                prepend-icon="mdi-account-plus"
                text="Register"
                @click="goToRegister"
            ></v-btn>

            <v-btn v-if="!user" to="request-password-reset-form" text>
                Forgot Password
            </v-btn>
        </template>

        <v-spacer />

        <div v-for="link in links" :key="link.text">
            <v-btn
                v-if="link.route"
                size="small"
                :to="link.route"
                text
                :class="{ active: isActiveRoute(link.route) }"
            >
                {{ link.text }}
            </v-btn>
            <v-btn v-else :href="link.url" text>
                {{ link.text }}
            </v-btn>
        </div>

        <v-spacer />
    </v-app-bar>

    <!-- Navigation Drawer for Mobile -->
    <v-navigation-drawer v-model="drawer" temporary>
        <v-divider />

        <!-- App Title -->
        <div class="drawer-btns drawer-header">
            <v-btn
                :class="{ 'v-btn--active': isActive('/landing-page') }"
                variant="flat"
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
                v-for="link in drawerLinks"
                :key="link"
                :class="{ 'v-btn--active': isActive('/' + link.route) }"
                variant="tonal"
                color="primary"
                :prepend-icon="link.icon"
                :text="link.text"
                @click="router.visit(link.route)"
            ></v-btn>
        </div>
        <v-spacer />

        <div class="drawer-btns">
            <v-btn
                v-if="!user"
                :class="{ 'v-btn--active': isActive('/login') }"
                variant="tonal"
                color="secondary"
                prepend-icon="mdi-login"
                text="Login"
                @click="goToLogin"
            ></v-btn>

            <v-btn
                v-if="!user"
                :class="{ 'v-btn--active': isActive('/register') }"
                variant="tonal"
                color="secondary"
                prepend-icon="mdi-account-plus"
                text="Register"
                @click="goToRegister"
            ></v-btn>

            <v-btn
                v-if="!user"
                :class="{ 'v-btn--active': isActive('/register') }"
                variant="tonal"
                color="secondary"
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
const page = usePage()


const { mobile } = useDisplay();
const drawer = ref(false);
const appTitle = import.meta.env.VITE_APP_NAME;
const user = page.props.auth.user

const links = ref([]);
const drawerLinks = ref([]);

const isActive = (path) => route.path === path;

onMounted(async () => {
    setupLinks();
});

const setupLinks = () => {
    if (user) {
        links.value = [{ text: 'Items', route: 'library-catalog' }];

        drawerLinks.value = [
            { text: 'Items', route: 'library-catalog' },
            { text: 'Archetypes', route: 'archetype-list' },
            { text: 'Categories', route: 'category-list' },
            { text: 'Usages', route: 'usage-list' },
            { text: 'Brands', route: 'brand-list' },
        ];
    } else {
        links.value = [];
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

// Safeguard to handle undefined or null paths
const normalizePath = (path) => {
    return path ? path.replace(/\/+$/, '').trim() : '';
};

const isActiveRoute = (linkRoute) => {
    return normalizePath(route.path) === normalizePath(linkRoute);
};

const myRentals = () => {
    router.visit({ path: '/my-rentals' });
};

const myLoans = () => {
    router.visit({ path: '/my-loans' });
};

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
.active {
    color: #1976d2;
    font-weight: bold;
    background-color: rgba(25, 118, 210, 0.1);
}

/* Ensure buttons take full width and stack vertically */
.drawer-btns {
    display: flex;
    flex-direction: column; /* vertical stacking */
    width: 100%;
    padding: 8px;
    gap: 8px;
}
</style>
