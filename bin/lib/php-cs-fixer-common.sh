# shellcheck shell=bash
#
# Shared base of the bin/php-cs-fixer-* commands: where and how this package's PHP CS Fixer runs.
# The commands load it with `source`.
#
# The fixer is the package's own dev dependency, vendor/bin/php-cs-fixer, with the version from
# composer.json. Unlike the projects these scripts came from, this package ships no container, so
# the fixer always runs with the local `php`. That also covers CI, which has PHP and vendor/.

# shellcheck disable=SC2034  # used by the commands that source this file
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd -P)"
readonly PROJECT_DIR
readonly FIXER="${PROJECT_DIR}/vendor/bin/php-cs-fixer"

# Prints the given path relative to the project root, where the fixer runs. Fails for a path that
# does not exist or is outside the project. Relative paths count from the current folder.
to_project_path() {
  local path="${1}"
  local absolute
  if [ -d "${path}" ]; then
    absolute="$(cd "${path}" && pwd -P)"
  elif [ -e "${path}" ]; then
    absolute="$(cd "$(dirname "${path}")" && pwd -P)/$(basename "${path}")"
  else
    printf '%s\n' "ERROR: No such file or folder: ${path}" >&2
    return 1
  fi
  case "${absolute}" in
    "${PROJECT_DIR}") printf '.\n' ;;
    "${PROJECT_DIR}"/*) printf '%s\n' "${absolute#"${PROJECT_DIR}"/}" ;;
    *)
      printf '%s\n' "ERROR: Not inside the project (${PROJECT_DIR}): ${path}" >&2
      return 1
      ;;
  esac
}

# Fails with an explanation when PHP or the fixer is missing.
check_fixer() {
  if ! command -v php >/dev/null 2>&1; then
    printf '%s\n' "ERROR: No 'php' found. This package needs PHP 8.3 or newer." >&2
    return 1
  fi
  if [ ! -f "${FIXER}" ]; then
    printf '%s\n' "ERROR: ${FIXER} is missing. Run 'composer install' first." >&2
    return 1
  fi
}

# Runs the fixer's `fix` command on the given paths (relative to the project root, see
# to_project_path), or on the whole project when no path is given. Arguments: check_only, then the
# paths. With check_only=1 it only shows the needed fixes as a diff, with no cache, as a check must
# not write any file. With paths the fixer needs its config by name, and `intersection` keeps the
# config's rules for them: skipped folders and files git ignores stay skipped.
run_fix() {
  local check_only="${1}"
  shift
  check_fixer
  local args=(fix)
  if [ "${check_only}" -eq 1 ]; then
    args+=(--dry-run --diff --using-cache=no)
  fi
  if [ "$#" -gt 0 ]; then
    args+=(--config=.php-cs-fixer.dist.php --path-mode=intersection -- "$@")
  fi
  (cd "${PROJECT_DIR}" && php "${FIXER}" "${args[@]}")
}
