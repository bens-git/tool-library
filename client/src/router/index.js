import { createRouter, createWebHistory } from "vue-router";
import EmailVerified from "@/components/EmailVerified.vue";
import ArchetypeList from "@/components/ArchetypeList.vue";
import ProjectList from "@/components/ProjectList.vue";
import JobList from "@/components/JobList.vue";
import MyItems from "@/components/MyItems.vue";
import MyArchetypes from "@/components/MyArchetypes.vue";
import MyCategories from "@/components/MyCategories.vue";
import MyUsages from "@/components/MyUsages.vue";
import MyBrands from "@/components/MyBrands.vue";
import MyJobs from "@/components/MyJobs.vue";
import MyProjects from "@/components/MyProjects.vue";
import MyRentals from "@/components/MyRentals.vue";
import MyLoans from "@/components/MyLoans.vue";
import DiscordResponse from "@/components/DiscordResponse.vue";
import { useUserStore } from "@/stores/user"; // Adjust the import path as necessary

const routes = [
  {
    path: "/archetype-list",
    name: "archetype-list",
    component: ArchetypeList,
  },

  {
    path: "/project-list",
    name: "project-list",
    component: ProjectList,
  },

  {
    path: "/job-list",
    name: "job-list",
    component: JobList,
  },



  {
    path: "/discord-response",
    name: "discord-response",
    component: DiscordResponse,
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
    path: "/my-items",
    name: "my-items",
    component: MyItems,
    meta: { requiresAuth: true, requiresDiscord: true },
  },

  {
    path: "/my-archetypes",
    name: "my-archetypes",
    component: MyArchetypes,
    meta: { requiresAuth: true, requiresDiscord: true },
  },

  {
    path: "/my-categories",
    name: "my-categories",
    component: MyCategories,
    meta: { requiresAuth: true, requiresDiscord: true },
  },

  {
    path: "/my-usages",
    name: "my-usages",
    component: MyUsages,
    meta: { requiresAuth: true, requiresDiscord: true },
  },

  {
    path: "/my-brands",
    name: "my-brands",
    component: MyBrands,
    meta: { requiresAuth: true, requiresDiscord: true },
  },

  {
    path: "/my-jobs",
    name: "my-jobs",
    component: MyJobs,
    meta: { requiresAuth: true, requiresDiscord: true },
  },

  {
    path: "/my-projects",
    name: "my-projects",
    component: MyProjects,
    meta: { requiresAuth: true, requiresDiscord: true },
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
    redirect: "/archetype-list",
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
    // Redirect to home page if user is already logged in
    return next({ name: "archetype-list" });
  }

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
