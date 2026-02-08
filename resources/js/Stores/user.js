import { defineStore } from 'pinia';
import useApi from '@/Stores/api';
import axios from '../axios';
import { ref } from 'vue';

export const useUserStore = defineStore(
    'user',
    () => {
        const user = ref(null);
        const { sendRequest } = useApi();

        const login = async (params) => {
            const response = await sendRequest('login', 'post', params);

            if (response?.success) {
                const token = response.token;
                localStorage.setItem('authToken', token);
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

                await show();
            }
            return response;
        };

        const logout = async () => {
            const token = localStorage.getItem('token');

            if (user.value && token) {
                await sendRequest('logout', 'POST');
            }
            user.value = null;

            localStorage.removeItem('authToken');
            localStorage.removeItem('user');
            delete axios.defaults.headers.common['Authorization'];
        };

        const register = async ({ name, email, password }) => {
            const response = await sendRequest(
                '/register',
                'POST',
                {
                    name,
                    email,
                    password,
                    password_confirmation: password,
                },
                true
            );

            // 3️⃣ Update state
            user.value = response.user || null;
            localStorage.setItem('user', JSON.stringify(user.value));

            return response;
        };

        const resendVerification = async (userData) => {
            const data = await sendRequest('user/resend-verification', 'POST', userData);
            console.log(data);
            return data;
        };

        const loginToDiscord = () => {
            // Discord OAuth2 parameters
            const clientId = process.env.VUE_APP_DISCORD_CLIENT_ID;
            const redirectUri = encodeURIComponent(process.env.VUE_APP_DISCORD_REDIRECT_URI);

            const responseType = 'code'; // OAuth response type
            const scope = 'identify'; // Permissions requested

            // Construct the Discord OAuth2 authorization URL
            const discordAuthUrl = `https://discord.com/api/oauth2/authorize?client_id=${clientId}&redirect_uri=${redirectUri}&response_type=${responseType}&scope=${scope}`;

            // Redirect to Discord's OAuth2 page
            window.location.href = discordAuthUrl;
        };

        const linkWithDiscord = async (code) => {
            await sendRequest('link-with-discord', 'POST', {
                code: code,
            });
            await this.getUser();
        };

        const show = async () => {
            const { fetchRequest } = useApi();

            const response = await fetchRequest('user');
            console.log(response);
            user.value = response.data;
        };

        const update = async (user) => {
            const data = await sendRequest(`me`, 'PUT', user);

            if (data?.success) {
                this.user = data.data;
            }
        };

        const destroy = async () => {
            const data = await sendRequest('user', 'DELETE');

            if (data?.success) {
                this.user = null;
                localStorage.removeItem('authToken');
                localStorage.removeItem('user');
                delete axios.defaults.headers.common['Authorization'];
            }

            return data;
        };

        const patchPassword = async (currentPassword, newPassword, confirmPassword) => {
            // Prepare the data to be sent in the request
            const user = {
                current_password: currentPassword,
                new_password: newPassword,
                new_password_confirmation: confirmPassword,
            };

            await sendRequest('/user/password', 'PUT', user);
        };
        const patchPasswordWithToken = async (newPassword, confirmPassword, token) => {
            const user = {
                new_password: newPassword,
                new_password_confirmation: confirmPassword,
                token: token,
            };

            await sendRequest('/user/password-with-token', 'PUT', user);
        };

        const requestPasswordReset = async (email) => {
            await sendRequest('request-password-reset', 'POST', email);
        };

        return {
            destroy,
            login,
            logout,
            patchPassword,
            patchPasswordWithToken,
            register,
            requestPasswordReset,
            resendVerification,
            show,
            update,
            user,
            loginToDiscord,
            linkWithDiscord,
        };
    },

    {
        persist: {
            enabled: true,
            strategies: [
                {
                    key: 'userStore',
                    storage: localStorage,
                },
            ],
        },
    }
);
