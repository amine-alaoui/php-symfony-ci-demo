# PHP Symfony CI Demo

Petit projet Symfony pour valider une pipeline Jenkins DevSecOps:

- checkout GitHub/GitLab
- Composer install
- PHPUnit + rapports JUnit/Clover
- SonarQube
- OWASP Dependency-Check
- Dependency-Track
- publication d'une archive vers Nexus

## Local

```bash
composer install
php -S 127.0.0.1:8000 -t public
vendor/bin/phpunit
```

URLs locales:

- `http://127.0.0.1:8000/`
- `http://127.0.0.1:8000/health`

## Jenkins Credentials Attendues

- `sonar-token`
- `dependency-track-api-key`
- `nexus-credentials`

Au lancement du job Jenkins, renseigne le parametre:

- `DEVSECOPS_HOST`: IP ou DNS de la VM DevSecOps

Les URLs SonarQube, Dependency-Track et Nexus sont construites automatiquement depuis ce parametre.
