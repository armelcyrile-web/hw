<!-- src/layouts/AppLayout.vue -->
<script setup>
import NavBar from '@/components/layout/NavBar.vue'
</script>

<template>
  <div class="app-layout">
    <NavBar />
    <main class="main-content">
      <router-view />
    </main>
  </div>
</template>

<style lang="scss" scoped>
@use '@/assets/styles/variables.scss' as *;

.app-layout {
  min-height: 100vh;
  background-color: $color-neutral-light;
}

.main-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: $spacing-lg $spacing-md;
}
</style>