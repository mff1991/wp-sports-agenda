document.addEventListener('DOMContentLoaded', function() {
    const locFilter = document.getElementById('filter-loc');
    const secFilter = document.getElementById('filter-sec');
    const allCards = document.querySelectorAll('.chb-card');

    function applyFilters() {
        const locValue = locFilter.value;
        const secValue = secFilter.value;

        allCards.forEach(card => {
            // 1. Detectem si la card és un resultat (estat jugat)
            const isResultat = card.classList.contains('is-resultat');
            
            const cardLoc = card.getAttribute('data-loc');
            const cardSec = card.getAttribute('data-sec');

            // 2. Filtre de Secció: S'aplica SEMPRE (tant a propers com a resultats)
            const matchSec = (secValue === 'tots' || cardSec === secValue);

            // 3. Filtre de Lloc (Casa/Fora):
            // SI és un resultat, matchLoc sempre és true (volem veure'ls tots)
            // SI NO és un resultat, mirem si coincideix amb el selector
            let matchLoc = true;
            if (!isResultat) {
                matchLoc = (locValue === 'tots' || cardLoc === locValue);
            }

            // 4. Aplicar visibilitat final
            if (matchLoc && matchSec) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    if (locFilter && secFilter) {
        locFilter.addEventListener('change', applyFilters);
        secFilter.addEventListener('change', applyFilters);
        
        // Executem al carregar la pàgina
        applyFilters();
    }
});