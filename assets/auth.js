/**
 * Gestion de l'authentification côté client
 * À inclure dans toutes les pages protégées
 */

// Vérifier l'authentification au chargement de la page
async function checkAuth() {
    const token = localStorage.getItem('authToken') || sessionStorage.getItem('authToken');
    
    if (!token) {
        window.location.href = 'login.html';
        return false;
    }
    
    try {
        const response = await fetch('api/auth/validate.php', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + token
            }
        });
        
        const data = await response.json();
        
        if (!data.valid) {
            clearAuth();
            window.location.href = 'login.html';
            return false;
        }
        
        // Afficher le nom d'utilisateur si l'élément existe
        const usernameDisplay = document.getElementById('username-display');
        if (usernameDisplay) {
            const user = JSON.parse(sessionStorage.getItem('user') || '{}');
            if (user.username) {
                usernameDisplay.textContent = user.username;
            }
        }
        
        return true;
    } catch (error) {
        console.error('Erreur de validation:', error);
        window.location.href = 'login.html';
        return false;
    }
}

// Déconnecter l'utilisateur
async function logout() {
    if (!confirm('Voulez-vous vraiment vous déconnecter ?')) {
        return;
    }
    
    const token = localStorage.getItem('authToken') || sessionStorage.getItem('authToken');
    
    // Appeler l'API de logout
    if (token) {
        try {
            await fetch('api/auth/logout.php', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token
                }
            });
        } catch (error) {
            console.error('Erreur lors de la déconnexion:', error);
        }
    }
    
    // Nettoyer et rediriger
    clearAuth();
    window.location.href = 'login.html';
}

// Nettoyer les données d'authentification
function clearAuth() {
    localStorage.removeItem('authToken');
    sessionStorage.removeItem('authToken');
    sessionStorage.removeItem('user');
}

// Obtenir les informations de l'utilisateur connecté
function getCurrentUser() {
    return JSON.parse(sessionStorage.getItem('user') || '{}');
}

// Obtenir le token
function getAuthToken() {
    return localStorage.getItem('authToken') || sessionStorage.getItem('authToken');
}

// Initialiser l'authentification au chargement
window.addEventListener('load', function() {
    checkAuth();
    
    // Attacher l'événement de logout au bouton s'il existe
    const btnLogout = document.getElementById('btnLogout');
    if (btnLogout) {
        btnLogout.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
        });
    }
});
