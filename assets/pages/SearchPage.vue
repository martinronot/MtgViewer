<script setup>
import { ref, watch, onMounted } from 'vue';
import { searchCards, fetchSetCodes } from '../services/cardService';
import CardProperty from '../components/CardProperty.vue';

const searchQuery = ref('');
const selectedSetCode = ref('');
const currentPage = ref(1);
const searchResult = ref({
    items: [],
    total_items: 0,
    items_per_page: 100,
    total_pages: 0,
    current_page: 1
});
const loadingCards = ref(false);
const loadingSetCodes = ref(false);
const error = ref(null);

let searchTimeout = null;

const performSearch = async () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    if (searchQuery.value.length < 3) {
        searchResult.value = {
            items: [],
            total_items: 0,
            items_per_page: 100,
            total_pages: 0,
            current_page: 1
        };
        return;
    }

    searchTimeout = setTimeout(async () => {
        try {
            loadingCards.value = true;
            error.value = null;
            searchResult.value = await searchCards(
                searchQuery.value,
                selectedSetCode.value || null,
                currentPage.value
            );
        } catch (e) {
            error.value = 'Une erreur est survenue lors de la recherche';
            console.error('Search error:', e);
        } finally {
            loadingCards.value = false;
        }
    }, 300); // Délai de 300ms pour éviter trop de requêtes
};

const loadSetCodes = async () => {
    try {
        loadingSetCodes.value = true;
        setCodes.value = await fetchSetCodes();
    } catch (e) {
        console.error('Failed to load set codes:', e);
    } finally {
        loadingSetCodes.value = false;
    }
};

const goToPage = (page) => {
    currentPage.value = page;
    performSearch();
};

const setCodes = ref([]);

watch(searchQuery, () => {
    currentPage.value = 1; // Reset page when query changes
    performSearch();
});

watch(selectedSetCode, () => {
    currentPage.value = 1; // Reset page when set changes
    performSearch();
});

onMounted(() => {
    loadSetCodes();
});
</script>

<template>
    <div class="search-container">
        <h1>Rechercher une Carte</h1>
        
        <div class="search-controls">
            <div class="search-box">
                <input 
                    type="text" 
                    v-model="searchQuery"
                    placeholder="Entrez le nom d'une carte (min. 3 caractères)"
                    class="search-input"
                >
            </div>

            <div class="set-filter">
                <select 
                    v-model="selectedSetCode"
                    class="set-select"
                    :disabled="loadingSetCodes"
                >
                    <option value="">Tous les sets</option>
                    <option 
                        v-for="set in setCodes" 
                        :key="set.setCode" 
                        :value="set.setCode"
                    >
                        {{ set.setCode }} ({{ set.cardCount }} cartes)
                    </option>
                </select>
            </div>
        </div>

        <div v-if="error" class="error-message">
            {{ error }}
        </div>

        <div class="card-list">
            <div v-if="loadingCards" class="loading">
                Recherche en cours...
            </div>
            <div v-else-if="searchQuery.length >= 3 && searchResult.items.length === 0" class="no-results">
                Aucune carte trouvée
            </div>
            <div v-else-if="searchQuery.length < 3" class="hint">
                Entrez au moins 3 caractères pour lancer la recherche
            </div>
            <template v-else>
                <div class="cards-grid">
                    <div v-for="card in searchResult.items" :key="card.uuid" class="card-item">
                        <router-link :to="{ name: 'get-card', params: { uuid: card.uuid } }">
                            <div class="card-preview">
                                <h3>{{ card.name }}</h3>
                                <CardProperty label="Mana" :value="card.manaCost" />
                                <CardProperty label="Type" :value="card.type" />
                                <CardProperty label="Set" :value="card.setCode" />
                            </div>
                        </router-link>
                    </div>
                </div>

                <div v-if="searchResult.total_pages > 1" class="pagination">
                    <button 
                        :disabled="currentPage === 1"
                        @click="goToPage(currentPage - 1)"
                        class="page-button"
                    >
                        Précédent
                    </button>
                    
                    <div class="page-info">
                        Page {{ currentPage }} sur {{ searchResult.total_pages }}
                        ({{ searchResult.total_items }} cartes)
                    </div>

                    <button 
                        :disabled="currentPage === searchResult.total_pages"
                        @click="goToPage(currentPage + 1)"
                        class="page-button"
                    >
                        Suivant
                    </button>
                </div>
            </template>
        </div>
    </div>
</template>

<style scoped>
.search-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.search-controls {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.search-box {
    flex: 1;
}

.set-filter {
    width: 250px;
}

.search-input, .set-select {
    width: 100%;
    padding: 12px;
    font-size: 16px;
    border: 2px solid #ccc;
    border-radius: 4px;
    transition: border-color 0.3s;
}

.search-input:focus, .set-select:focus {
    border-color: #4a90e2;
    outline: none;
}

.error-message {
    color: #dc3545;
    margin: 10px 0;
    padding: 10px;
    background-color: #f8d7da;
    border-radius: 4px;
}

.loading, .no-results, .hint {
    text-align: center;
    padding: 20px;
    color: #666;
}

.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    padding: 20px 0;
}

.card-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.card-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.card-preview {
    padding: 15px;
}

.card-preview h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    margin-top: 20px;
    padding: 20px 0;
}

.page-button {
    padding: 8px 16px;
    font-size: 14px;
    border: 2px solid #4a90e2;
    border-radius: 4px;
    background-color: white;
    color: #4a90e2;
    cursor: pointer;
    transition: all 0.3s;
}

.page-button:hover:not(:disabled) {
    background-color: #4a90e2;
    color: white;
}

.page-button:disabled {
    border-color: #ccc;
    color: #ccc;
    cursor: not-allowed;
}

.page-info {
    font-size: 14px;
    color: #666;
}

a {
    text-decoration: none;
    color: inherit;
}
</style>
