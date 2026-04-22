// ============================================
// JavaScript pour la page Admin
// Fonctions pour les modales de modification
// ============================================

// Remplir la modale de modification de club
function editClub(id, nom, desc, cat, emoji) {
    document.getElementById('edit_club_id').value = id;
    document.getElementById('edit_club_nom').value = nom;
    document.getElementById('edit_club_description').value = desc;
    document.getElementById('edit_club_categorie').value = cat;
    document.getElementById('edit_club_emoji').value = emoji;
    
    var modal = new bootstrap.Modal(document.getElementById('editClubModal'));
    modal.show();
}

// Remplir la modale de modification d'événement
function editEvent(id, titre, desc, club_id, lieu, debut, fin, max) {
    document.getElementById('edit_evt_id').value = id;
    document.getElementById('edit_evt_titre').value = titre;
    document.getElementById('edit_evt_desc').value = desc;
    document.getElementById('edit_evt_lieu').value = lieu;
    document.getElementById('edit_evt_debut').value = debut.replace(' ', 'T');
    document.getElementById('edit_evt_fin').value = fin.replace(' ', 'T');
    document.getElementById('edit_evt_max').value = max;
    
    var modal = new bootstrap.Modal(document.getElementById('editEventModal'));
    modal.show();
}
