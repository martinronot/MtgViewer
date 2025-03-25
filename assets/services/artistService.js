import axios from 'axios';

/**
 * Get all artists with pagination
 */
export async function getAllArtists(page = 1) {
    try {
        const response = await axios.get('/api/artist/all', {
            params: { page }
        });
        return response.data;
    } catch (error) {
        console.error('Error fetching artists:', error);
        throw error;
    }
}

/**
 * Search artists by name with pagination
 */
export async function searchArtists(query, page = 1) {
    if (!query || query.length < 3) {
        throw new Error('Search query must be at least 3 characters long');
    }

    try {
        const response = await axios.get('/api/artist/search', {
            params: { query, page }
        });
        return response.data;
    } catch (error) {
        console.error('Error searching artists:', error);
        throw error;
    }
}

/**
 * Get an artist by ID
 */
export async function getArtist(id) {
    try {
        const response = await axios.get(`/api/artist/${id}`);
        return response.data;
    } catch (error) {
        console.error('Error fetching artist:', error);
        throw error;
    }
}
