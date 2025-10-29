# Informations FTP correctes pour le déploiement

## Commande de déploiement complète :
```bash
cd "/Users/litharsanchristy/Documents/Site/Danalakshmi/Expiration Date" && lftp -e "set ftp:ssl-allow no; set ssl:verify-certificate no; open -u expire@expire.danalakshmi.fr,0617443516Et? ftp.sc3bera6697.universe.wf; cd public_html; put app-v2.html; cd api; put lots.php; quit"
```

## Détails de connexion :
- **Serveur FTP :** ftp.sc3bera6697.universe.wf
- **Utilisateur :** expire@expire.danalakshmi.fr
- **Mot de passe :** 0617443516Et?
- **Dossier principal :** public_html/
- **Dossier API :** public_html/api/

## Structure de déploiement :
- `app-v2.html` → `public_html/`
- `lots.php` → `public_html/api/`
- `produits.php` → `public_html/api/`
- `config/` → `public_html/api/config/`