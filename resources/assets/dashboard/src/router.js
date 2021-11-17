import Vue from "vue";
import Router from "vue-router";
import Dashboard from "@/views/Dashboard";
import RouterTest from "@/views/RouterTest";

Vue.use(Router);

export default new Router({
    base: '',
    mode: "hash",
    routes: [
        {
            path: "/",
            name: "dashboard",
            component: Dashboard
        },
        {
            path: "/routertest",
            name: "routertest",
            component: RouterTest
        }
    ]
});