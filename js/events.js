// ============================================
// JavaScript pour la page Événements
// Filtrer les événements par recherche et par club
// ============================================

function filterEvents() {
    var q = document.getElementById("eventSearch").value.toLowerCase().trim();
    var club = document.getElementById("clubFilter").value;
    var cards = document.querySelectorAll(".event-item");

    for (var i = 0; i < cards.length; i++) {
        var title = cards[i].querySelector(".event-title").textContent.toLowerCase();
        var cardClub = cards[i].getAttribute("data-club");
        var matchSearch = title.indexOf(q) !== -1;
        var matchClub = !club || cardClub == club;

        if (matchSearch && matchClub) {
            cards[i].style.display = "";
        } else {
            cards[i].style.display = "none";
        }
    }
}
