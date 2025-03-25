<template>
  <nav class="bg-gray-800 text-white p-4">
    <div class="container mx-auto flex justify-between items-center">
      <router-link to="/" class="text-xl font-bold">MTG Viewer</router-link>
      
      <div class="flex gap-4">
        <router-link 
          to="/search"
          class="hover:text-gray-300 transition-colors"
          active-class="text-blue-400"
        >
          Search Cards
        </router-link>
        
        <router-link 
          to="/artists"
          class="hover:text-gray-300 transition-colors"
          active-class="text-blue-400"
        >
          Artists
        </router-link>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { RouterLink } from 'vue-router';
</script>
