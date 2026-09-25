#!/usr/bin/env bash
set -euo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
COMPOSE=(docker compose -f "$DIR/docker/compose.yml")

usage() {
	cat <<EOF
Uso: ./dev <comando>

  up         Levanta el contenedor en segundo plano
  down       Para y elimina el contenedor
  build      Reconstruye la imagen
  sh         Abre una shell bash como usuario normal (node)
  sh-root    Abre una shell bash como root

Cualquier otro comando se pasa directo a 'docker compose', p.ej:
  ./dev logs -f
  ./dev ps
EOF
}

cmd="${1:-}"
[ $# -gt 0 ] && shift

case "$cmd" in
	up) "${COMPOSE[@]}" up -d "$@" ;;
	down) "${COMPOSE[@]}" down "$@" ;;
	build) "${COMPOSE[@]}" build "$@" ;;
	sh) "${COMPOSE[@]}" exec php bash ;;
	sh-root) "${COMPOSE[@]}" exec -u root php bash ;;
	""|help|-h|--help) usage ;;
	*) "${COMPOSE[@]}" "$cmd" "$@" ;;
esac
