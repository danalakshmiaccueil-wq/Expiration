<?php
/**
 * Script de test de l'API
 * Application : Gestion des dates d'expiration
 * Version : 1.0
 * Date : 11 octobre 2025
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/helpers.php';

// Headers pour l'affichage
header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test API - Gestion Expiration</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #2c3e50; color: white; padding: 20px; margin: -20px -20px 20px -20px; border-radius: 8px 8px 0 0; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .test-section h3 { margin-top: 0; color: #2c3e50; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .btn { padding: 8px 15px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .endpoint-test { margin: 10px 0; }
        .endpoint-test button { margin-right: 10px; }
        #results { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Test API - Gestion des Dates d'Expiration</h1>
            <p>Interface de test pour tous les endpoints de l'API</p>
        </div>

        <?php
        // Test de santé de l'API
        $health = testApiHealth();
        $healthClass = $health['status'] === 'healthy' ? 'success' : 'warning';
        ?>

        <div class="test-section <?php echo $healthClass; ?>">
            <h3>🏥 État de l'API</h3>
            <p><strong>Status:</strong> <?php echo ucfirst($health['status']); ?></p>
            <div class="grid">
                <div>
                    <strong>Tests système:</strong>
                    <ul>
                        <li>Base de données: <?php echo $health['tests']['database'] ? '✅ OK' : '❌ Échec'; ?></li>
                        <li>Configuration: <?php echo $health['tests']['config'] ? '✅ OK' : '❌ Échec'; ?></li>
                        <li>Logs: <?php echo $health['tests']['logs'] ? '✅ OK' : '❌ Échec'; ?></li>
                        <li>Cache: <?php echo $health['tests']['cache'] ? '✅ OK' : '❌ Échec'; ?></li>
                    </ul>
                </div>
                <div>
                    <strong>Informations:</strong>
                    <ul>
                        <li>Version: <?php echo $health['version']; ?></li>
                        <li>Timestamp: <?php echo date('d/m/Y H:i:s', strtotime($health['timestamp'])); ?></li>
                        <li>Environnement: <?php echo ENVIRONMENT; ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="test-section">
            <h3>🔧 Tests des Endpoints</h3>
            
            <div class="grid">
                <!-- Tests Produits -->
                <div>
                    <h4>📦 Produits</h4>
                    <div class="endpoint-test">
                        <button class="btn btn-primary" onclick="testEndpoint('GET', 'produits.php')">Liste produits</button>
                    </div>
                    <div class="endpoint-test">
                        <button class="btn btn-primary" onclick="testEndpoint('GET', 'produits.php?search=test')">Recherche produits</button>
                    </div>
                    <div class="endpoint-test">
                        <button class="btn btn-success" onclick="testCreateProduit()">Créer produit test</button>
                    </div>
                </div>

                <!-- Tests Lots -->
                <div>
                    <h4>📋 Lots</h4>
                    <div class="endpoint-test">
                        <button class="btn btn-primary" onclick="testEndpoint('GET', 'lots.php')">Liste lots</button>
                    </div>
                    <div class="endpoint-test">
                        <button class="btn btn-primary" onclick="testEndpoint('GET', 'lots.php?alerte=urgent')">Lots urgents</button>
                    </div>
                    <div class="endpoint-test">
                        <button class="btn btn-success" onclick="testCreateLot()">Créer lot test</button>
                    </div>
                </div>

                <!-- Tests Dashboard -->
                <div>
                    <h4>📊 Dashboard</h4>
                    <div class="endpoint-test">
                        <button class="btn btn-primary" onclick="testEndpoint('GET', 'dashboard.php?action=stats')">Statistiques</button>
                    </div>
                    <div class="endpoint-test">
                        <button class="btn btn-primary" onclick="testEndpoint('GET', 'dashboard.php?action=alertes')">Résumé alertes</button>
                    </div>
                    <div class="endpoint-test">
                        <button class="btn btn-primary" onclick="testEndpoint('GET', 'dashboard.php?action=tendances')">Tendances</button>
                    </div>
                </div>

                <!-- Tests Paramètres -->
                <div>
                    <h4>⚙️ Paramètres</h4>
                    <div class="endpoint-test">
                        <button class="btn btn-primary" onclick="testEndpoint('GET', 'parametres.php')">Liste paramètres</button>
                    </div>
                    <div class="endpoint-test">
                        <button class="btn btn-success" onclick="testCreateParametre()">Créer paramètre test</button>
                    </div>
                </div>

                <!-- Tests Alertes -->
                <div>
                    <h4>🚨 Alertes</h4>
                    <div class="endpoint-test">
                        <button class="btn btn-primary" onclick="testEndpoint('GET', 'alertes.php?action=summary')">Résumé alertes</button>
                    </div>
                    <div class="endpoint-test">
                        <button class="btn btn-primary" onclick="testEndpoint('GET', 'alertes.php?action=urgentes')">Alertes urgentes</button>
                    </div>
                    <div class="endpoint-test">
                        <button class="btn btn-primary" onclick="testEndpoint('GET', 'alertes.php?action=dashboard')">Dashboard alertes</button>
                    </div>
                </div>

                <!-- Tests utilitaires -->
                <div>
                    <h4>🛠️ Utilitaires</h4>
                    <div class="endpoint-test">
                        <button class="btn btn-warning" onclick="clearCache()">Vider cache</button>
                    </div>
                    <div class="endpoint-test">
                        <button class="btn btn-danger" onclick="testPerformance()">Test performance</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="test-section">
            <h3>📋 Résultats des Tests</h3>
            <div id="results">
                <p class="info">Cliquez sur un bouton de test ci-dessus pour voir les résultats ici.</p>
            </div>
        </div>
    </div>

    <script>
        const apiBase = './';
        
        async function testEndpoint(method, endpoint, data = null) {
            const resultsDiv = document.getElementById('results');
            resultsDiv.innerHTML = `<p class="info">🔄 Test en cours: ${method} ${endpoint}</p>`;
            
            try {
                const options = {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    }
                };
                
                if (data) {
                    options.body = JSON.stringify(data);
                }
                
                const startTime = performance.now();
                const response = await fetch(apiBase + endpoint, options);
                const endTime = performance.now();
                const responseTime = Math.round(endTime - startTime);
                
                const responseData = await response.json();
                
                const statusClass = response.ok ? 'success' : 'error';
                const statusIcon = response.ok ? '✅' : '❌';
                
                resultsDiv.innerHTML = `
                    <div class="${statusClass}">
                        <h4>${statusIcon} ${method} ${endpoint}</h4>
                        <p><strong>Status:</strong> ${response.status} ${response.statusText}</p>
                        <p><strong>Temps de réponse:</strong> ${responseTime}ms</p>
                        <p><strong>Réponse:</strong></p>
                        <pre>${JSON.stringify(responseData, null, 2)}</pre>
                    </div>
                `;
                
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="error">
                        <h4>❌ Erreur de test</h4>
                        <p><strong>Endpoint:</strong> ${method} ${endpoint}</p>
                        <p><strong>Erreur:</strong> ${error.message}</p>
                    </div>
                `;
            }
        }
        
        function testCreateProduit() {
            const produitTest = {
                nom: "Produit Test " + Date.now(),
                categorie: "Test",
                description: "Produit créé pour les tests API",
                unite_mesure: "pièce"
            };
            
            testEndpoint('POST', 'produits.php', produitTest);
        }
        
        function testCreateLot() {
            const lotTest = {
                produit_id: 1, // Assurez-vous qu'un produit avec ID 1 existe
                numero_lot: "LOT_TEST_" + Date.now(),
                date_reception: new Date().toISOString().split('T')[0],
                date_expiration: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // +30 jours
                quantite_initiale: 100,
                quantite_actuelle: 100,
                prix_achat: 15.50,
                fournisseur: "Fournisseur Test"
            };
            
            testEndpoint('POST', 'lots.php', lotTest);
        }
        
        function testCreateParametre() {
            const parametreTest = {
                nom: "param_test_" + Date.now(),
                valeur: "valeur_test",
                type: "string",
                description: "Paramètre créé pour les tests API",
                modifie_par: "test_api"
            };
            
            testEndpoint('POST', 'parametres.php', parametreTest);
        }
        
        async function clearCache() {
            const resultsDiv = document.getElementById('results');
            resultsDiv.innerHTML = `<p class="info">🔄 Vidage du cache en cours...</p>`;
            
            try {
                // Simuler un appel pour vider le cache
                const response = await fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=clear_cache');
                
                resultsDiv.innerHTML = `
                    <div class="success">
                        <h4>✅ Cache vidé</h4>
                        <p>Le cache a été vidé avec succès.</p>
                    </div>
                `;
                
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="error">
                        <h4>❌ Erreur</h4>
                        <p>Impossible de vider le cache: ${error.message}</p>
                    </div>
                `;
            }
        }
        
        async function testPerformance() {
            const resultsDiv = document.getElementById('results');
            resultsDiv.innerHTML = `<p class="info">🔄 Test de performance en cours...</p>`;
            
            const tests = [
                { name: 'Liste produits', endpoint: 'produits.php' },
                { name: 'Liste lots', endpoint: 'lots.php' },
                { name: 'Dashboard stats', endpoint: 'dashboard.php?action=stats' },
                { name: 'Résumé alertes', endpoint: 'alertes.php?action=summary' }
            ];
            
            const results = [];
            
            for (const test of tests) {
                try {
                    const startTime = performance.now();
                    const response = await fetch(apiBase + test.endpoint);
                    const endTime = performance.now();
                    
                    results.push({
                        name: test.name,
                        time: Math.round(endTime - startTime),
                        status: response.ok ? 'OK' : 'Erreur'
                    });
                } catch (error) {
                    results.push({
                        name: test.name,
                        time: 0,
                        status: 'Échec'
                    });
                }
            }
            
            const avgTime = results.reduce((sum, r) => sum + r.time, 0) / results.length;
            
            resultsDiv.innerHTML = `
                <div class="info">
                    <h4>🚀 Résultats du test de performance</h4>
                    <p><strong>Temps de réponse moyen:</strong> ${Math.round(avgTime)}ms</p>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 8px; border: 1px solid #ddd;">Test</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Temps (ms)</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${results.map(r => `
                                <tr>
                                    <td style="padding: 8px; border: 1px solid #ddd;">${r.name}</td>
                                    <td style="padding: 8px; border: 1px solid #ddd;">${r.time}</td>
                                    <td style="padding: 8px; border: 1px solid #ddd;">${r.status}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }
    </script>
</body>
</html>

<?php
// Traitement des actions backend
if (isset($_GET['action']) && $_GET['action'] === 'clear_cache') {
    try {
        ApiUtils::clearCache();
        echo json_encode(['success' => true, 'message' => 'Cache vidé']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>