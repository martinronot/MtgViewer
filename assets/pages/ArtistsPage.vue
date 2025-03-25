<template>
  <div class="container mx-auto p-4">
    <h1 class="text-3xl font-bold mb-6">Artists</h1>

    <!-- Search Bar -->
    <div class="mb-6">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search artists..."
        class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        @input="debounceSearch"
      />
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
      <strong class="font-bold">Error!</strong>
      <span class="block sm:inline">{{ error }}</span>
    </div>

    <!-- Results -->
    <div v-else>
      <!-- Artists Grid -->
      <div v-if="artists.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <div
          v-for="artist in artists"
          :key="artist.id"
          class="bg-white p-4 rounded-lg shadow hover:shadow-lg transition-shadow duration-200"
        >
          <h3 class="text-xl font-semibold text-gray-800">{{ artist.Name }}</h3>
          <p class="text-gray-600 mt-2">Artist ID: {{ artist.artistExternalId }}</p>
        </div>
      </div>

      <!-- No Results -->
      <div v-else class="text-center py-8 text-gray-600">
        No artists found.
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex justify-center items-center gap-4 mt-6">
        <button
          @click="changePage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="px-4 py-2 bg-blue-500 text-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-blue-600 transition-colors"
        >
          Previous
        </button>
        
        <span class="text-gray-600">
          Page {{ currentPage }} of {{ totalPages }}
        </span>
        
        <button
          @click="changePage(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="px-4 py-2 bg-blue-500 text-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-blue-600 transition-colors"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { getAllArtists, searchArtists } from '../services/artistService';
import debounce from 'lodash/debounce';

const searchQuery = ref('');
const artists = ref([]);
const loading = ref(false);
const error = ref(null);
const currentPage = ref(1);
const totalPages = ref(0);

const fetchArtists = async (page = 1) => {
  loading.value = true;
  error.value = null;
  
  try {
    const result = searchQuery.value
      ? await searchArtists(searchQuery.value, page)
      : await getAllArtists(page);
    
    artists.value = result.items;
    currentPage.value = result.current_page;
    totalPages.value = result.total_pages;
  } catch (e) {
    error.value = e.response?.data?.error || e.message || 'An error occurred';
  } finally {
    loading.value = false;
  }
};

const debounceSearch = debounce(() => {
  currentPage.value = 1;
  fetchArtists(1);
}, 300);

const changePage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
    fetchArtists(page);
  }
};

onMounted(() => {
  fetchArtists();
});
</script>
