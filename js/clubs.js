// ============================================
// JavaScript pour la page Clubs
// Filtrer les clubs par recherche et par catégorie
// ============================================

var currentFilter = "tous";

function setFilter(filter, btn) {
    currentFilter = filter;
    // Retirer la classe active de tous les boutons
    var btns = document.querySelectorAll(".filter-btn");
    for (var i = 0; i < btns.length; i++) {
        btns[i].classList.remove("active", "btn-dark");
        btns[i].classList.add("btn-outline-dark");
    }
    // Ajouter la classe active au bouton cliqué
    btn.classList.add("active", "btn-dark");
    btn.classList.remove("btn-outline-dark");
    filterClubs();
}

function filterClubs() {
    var q = document.getElementById("searchInput").value.toLowerCase().trim();
    var cards = document.querySelectorAll(".club-item");

    for (var i = 0; i < cards.length; i++) {
        var name = cards[i].querySelector(".club-title").textContent.toLowerCase();
        var cat = cards[i].getAttribute("data-cat");
        var mem = cards[i].getAttribute("data-member");

        // Vérifier si le nom correspond à la recherche
        var matchSearch = name.indexOf(q) !== -1;

        // Vérifier si la catégorie correspond au filtre
        var matchFilter = true;
        if (currentFilter == "mes-clubs") {
            matchFilter = (mem == "yes" || mem == "admin");
        } else if (currentFilter != "tous") {
            matchFilter = (currentFilter == cat);
        }

        // Afficher ou cacher la carte
        if (matchSearch && matchFilter) {
            cards[i].style.display = "";
        } else {
            cards[i].style.display = "none";
        }
    }
}
