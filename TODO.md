# TODO

## Cobertura de tests (PHPUnit)

Pendiente: configurar la cobertura cuando haya código que testear.

- [ ] Añadir tests de servicios y de HTTP (WebTestCase) cuando existan controladores y servicios en `src/`.
- [ ] Ejecutar la cobertura: `docker compose exec -T -e XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-text`.
- [ ] Decidir si se añade un umbral mínimo de cobertura y dónde se comprueba.

Referencias:
- https://docs.phpunit.de/en/13.0/code-coverage.html
- https://symfony.com/doc/current/testing.html
