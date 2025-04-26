import { createRouter, createWebHistory } from "vue-router";
import EmailVerified from "@/components/EmailVerified.vue";
import ArchetypeList from "@/components/ArchetypeList.vue";
import ProjectList from "@/components/ProjectList.vue";
import JobList from "@/components/JobList.vue";
import CategoryList from "@/components/CategoryList.vue";
import UsageList from "@/components/UsageList.vue";
import BrandList from "@/components/BrandList.vue";
import MyRentals from "@/components/MyRentals.vue";
import MyLoans from "@/components/MyLoans.vue";
import LinkWithDiscordForm from "@/components/LinkWithDiscordForm.vue";
import DiscordResponse from "@/components/DiscordResponse.vue";
import { useUserStore } from "@/stores/user"; // Adjust the import path as necessary
import ItemList from "@/components/ItemList.vue";

const routes = [
  {
    path: "/archetype-list",
    name: "archetype-list",
    component: ArchetypeList,
    meta: { requiresAuth: true },
  },

  {
    path: "/item-list",
    name: "item-list",
    component: ItemList,
    meta: { requiresAuth: true },
  },

  {
    path: "/project-list",
    name: "project-list",
    component: ProjectList,
    meta: { requiresAuth: true },
  },

  {
    path: "/job-list",
    name: "job-list",
    component: JobList,
    meta: { requiresAuth: true },
  },

  {
    path: "/route-to-discord-link",
    name: "route-to-discord-link",
    component: LinkWithDiscordForm,
    meta: { requiresAuth: true },
  },

  {
    path: "/discord-response",
    name: "discord-response",
    component: DiscordResponse,
    meta: { requiresAuth: true },
  },

  {
    path: "/login-form",
    name:"login-form",
    component: () => import("@/components/LoginForm.vue"),
    meta: { requiresGuest: true }, // Only accessible if not logged in
  },

  {
    path: "/request-password-reset-form",
    component: () => import("@/components/RequestPasswordResetForm.vue"),
    meta: { requiresGuest: true }, // Only accessible if not logged in
  },
  {
    path: "/reset-password",
    component: () => import("@/components/ResetPassword.vue"),
    meta: { requiresGuest: true }, // Only accessible if not logged in
  },
  
  {
    path: "/category-list",
    name: "category-list",
    component: CategoryList,
    meta: { requiresDiscord: true,  requiresAuth: true  },
  },

  {
    path: "/usage-list",
    name: "usage-list",
    component: UsageList,
    meta: { requiresDiscord: true,  requiresAuth: true  },
  },

  {
    path: "/brand-list",
    name: "brand-list",
    component: BrandList,
    meta: { requiresDiscord: true,  requiresAuth: true  },
  },

  {
    path: "/my-rentals",
    name: "my-rentals",
    component: MyRentals,
    meta: { requiresAuth: true },
  },
  {
    path: "/my-loans",
    name: "my-loans",
    component: MyLoans,
    meta: { requiresAuth: true },
  },
  {
    path: "/:catchAll(.*)",
    redirect: "/item-list",
  },
  {
    path: "/email-verified",
    name: "EmailVerified",
    component: EmailVerified,
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, from, next) => {
  const userStore = useUserStore();
  const isAuthenticated = !!userStore.user; // Check if user is logged in
  const isDiscordAuthenticated =
    !!userStore.user && !!userStore.user.discord_user_id;
  const requiresAuth = to.meta.requiresAuth;
  const requiresDiscord = to.meta.requiresDiscord;

  if (
    to.matched.some((record) => record.meta.requiresGuest) &&
    userStore.user
  ) {
    console.log('user')
  // Redirect to home page if user is already logged in
    return next({ name: "item-list" });
  }
  console.log('test')

  if (requiresAuth && !isAuthenticated) {
    // Redirect to login if route requires authentication and user is not logged in
    next({ name: "login-form" });
  } else if (requiresDiscord && !isDiscordAuthenticated) {
    // Redirect to login if route requires authentication and user is not logged in
    next({ name: "route-to-discord-link" });
  } else {
    next(); // Proceed to the route
  }
});

export default router;
