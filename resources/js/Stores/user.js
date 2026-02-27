import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useUserStore = defineStore(
    'user',
    () => {
        const user = ref(null);

        // Sync user with Inertia props (called from app.js on initialization)
        const syncWithInertia = (inertiaUser) => {
            if (inertiaUser) {
                user.value = inertiaUser;
                localStorage.setItem('user', JSON.stringify(inertiaUser));
                // Ensure token exists if user is present
                const token = localStorage.getItem('authToken');
                if (token) {
                    // Token is set in api.js interceptor
                }
                // Clear any stale persist data
                localStorage.removeItem('userStore');
            } else {
                // User is not authenticated (logged out)
                user.value = null;
                localStorage.removeItem('user');
                // Clear persist storage to prevent stale data restoration
                localStorage.removeItem('userStore');
            }
        };

        return {
            syncWithInertia,
            user,
        };
    }
);

