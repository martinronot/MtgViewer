import { createRouter, createWebHistory } from 'vue-router';
import HomePage from '../pages/HomePage.vue';
import SearchPage from '../pages/SearchPage.vue';
import ArtistsPage from '../pages/ArtistsPage.vue';

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomePage
  },
  {
    path: '/search',
    name: 'search',
    component: SearchPage
  },
  {
    path: '/artists',
    name: 'artists',
    component: ArtistsPage
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

export default router;
