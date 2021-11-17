window._ = require("lodash");

import Vue from "vue";
import VueI18n from 'vue-i18n'
import App from "./App.vue";
import router from "./router";
import store from "./store";
import { languages } from './i18n/index.js'
import { defaultLocale } from './i18n/index.js'
const messages = Object.assign(languages)

Vue.config.productionTip = false;

Vue.use(VueI18n)

export const i18n = new VueI18n({
  locale: defaultLocale,
  fallbackLocale: 'en',
  messages
})

new Vue({
  i18n,
  beforeMount: function () {
    i18n.locale = this.$el.attributes['locale'].value;
  },
  router,
  store,
  render: h => h(App)
}).$mount("#app");