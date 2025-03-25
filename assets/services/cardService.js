export async function fetchAllCards(page = 1, setCode = null) {
    const params = new URLSearchParams();
    params.append('page', page);
    if (setCode) {
        params.append('setCode', setCode);
    }
    const response = await fetch(`/api/card/all?${params}`);
    if (!response.ok) throw new Error('Failed to fetch cards');
    const result = await response.json();
    return result;
}

export async function fetchCard(uuid) {
    const response = await fetch(`/api/card/${uuid}`);
    if (response.status === 404) return null;
    if (!response.ok) throw new Error('Failed to fetch card');
    const card = await response.json();
    card.text = card.text.replaceAll('\\n', '\n');
    return card;
}

export async function searchCards(query, setCode = null, page = 1) {
    if (!query || query.length < 3) {
        return {
            items: [],
            total_items: 0,
            items_per_page: 100,
            total_pages: 0,
            current_page: 1
        };
    }
    const params = new URLSearchParams({ query, page });
    if (setCode) {
        params.append('setCode', setCode);
    }
    
    const response = await fetch(`/api/card/search?${params}`);
    if (!response.ok) {
        if (response.status === 400) {
            return {
                items: [],
                total_items: 0,
                items_per_page: 100,
                total_pages: 0,
                current_page: 1
            };
        }
        throw new Error('Failed to search cards');
    }
    const result = await response.json();
    return result;
}

export async function fetchSetCodes() {
    const response = await fetch('/api/card/set-codes');
    if (!response.ok) throw new Error('Failed to fetch set codes');
    const result = await response.json();
    return result;
}
