import { createRouter, createWebHistory } from "vue-router";
import EmailVerified from "@/components/EmailVerified.vue";
import EditUser from "@/components/EditUser.vue";
import LoginForm from "@/components/LoginForm.vue";
import TypeList from "@/components/TypeList.vue";
import MyManagement from "@/components/MyManagement.vue";
import MyRentals from "@/components/MyRentals.vue";
import MyLoans from "@/components/MyLoans.vue";
import { useUserStore } from "@/stores/user"; // Adjust the import path as necessary

const routes = [
  {
    path: "/type-list",
    name: "type-list",
    component: TypeList,
  },

  {
    path: "/tool/:id",
    component: () => import("@/components/TypeDetail.vue"),
    name: "tool_detail",
    props: true,
  },
  { path: "/add-tool", component: () => import("@/components/AddTool.vue") },
  {
    path: "/login-form",
    name: "login-form",
    component: LoginForm,
    meta: { requiresGuest: true }, // Only accessible if not logged in
  },
  {
    path: "/register-form",
    component: () => import("@/components/RegisterForm.vue"),
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
    path: "/edit-user",
    name: "edit-user",
    component: EditUser,
    meta: { requiresAuth: true },
  },
  {
    path: "/my-tools",
    name: "my-tools",
    component: MyManagement,
    meta: { requiresAuth: true },
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
    redirect: "/type-list",
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
  const requiresAuth = to.meta.requiresAuth;

  if (
    to.matched.some((record) => record.meta.requiresGuest) &&
    userStore.user
  ) {
    // Redirect to home page if user is already logged in
    return next({ name: "type-list" });
  }

  if (requiresAuth && !isAuthenticated) {
    // Redirect to login if route requires authentication and user is not logged in
    next({ name: "login-form" });
  } else {
    next(); // Proceed to the route
  }
});

export default router;
