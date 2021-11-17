<template>
  <div class="border-end" id="sidebar-wrapper">
    <div class="list-group list-group-flush">
      <router-link
        to="/"
        class="list-group-item list-group-item-action list-group-item-light p-3"
      >
        Dashboard</router-link
      >
      <router-link
        to="/routertest"
        class="list-group-item list-group-item-action list-group-item-light p-3"
      >
        Router Test</router-link
      >
    </div>
  </div>
</template>

<script>
export default {
  name: "Sidebar",
};
</script>