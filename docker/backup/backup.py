#!/usr/bin/env python3
"""On-demand Pfarrplaner backup orchestrator.

All backup credentials are loaded from backup.yaml.  This process deliberately
does not use Laravel, the Docker socket, or a host-installed backup tool.
"""

from __future__ import annotations

import argparse
import datetime as dt
import fcntl
import hashlib
import json
import os
import re
import shutil
import signal
import shlex
import stat
import subprocess
import sys
import tempfile
import time
from pathlib import Path
from urllib.parse import quote

try:
    import yaml
except ImportError as exc:  # pragma: no cover - image build guard
    raise SystemExit("PyYAML fehlt im Backup-Image.") from exc


ROOT = Path("/workspace")
CONFIG_PATH = Path(os.environ.get("BACKUP_CONFIG", "/workspace/backup.yaml"))
BACKUPS = Path("/backups")
LOCK_PATH = BACKUPS / ".backup.lock"
READY_PATH = BACKUPS / ".backup-ready"
RELEASE_PATH = BACKUPS / ".backup-release"
ID_RE = re.compile(r"^[0-9]{8}T[0-9]{6}Z$")
ACTIVE: list[Path] = []


class BackupError(RuntimeError):
    pass


def fail(message: str) -> "NoReturn":
    raise BackupError(message)


def ensure_keys(value: dict, allowed: set[str], context: str) -> None:
    unknown = set(value) - allowed
    if unknown:
        fail(f"{context}: unbekannte Felder: {', '.join(sorted(unknown))}")


def run(command: list[str], *, env: dict[str, str] | None = None,
        cwd: Path | None = None, capture: bool = False, quiet: bool = False) -> str:
    result = subprocess.run(command, cwd=cwd, env=env, text=True,
                            stdout=subprocess.PIPE if capture else (subprocess.DEVNULL if quiet else None),
                            stderr=subprocess.PIPE if capture else None)
    if result.returncode:
        detail = (result.stderr or result.stdout or "").strip()
        raise BackupError(f"Befehl fehlgeschlagen ({result.returncode}): "
                          f"{' '.join(command[:3])} {detail}")
    return result.stdout if capture else ""


def load_config() -> dict:
    if not CONFIG_PATH.is_file():
        fail(f"Konfiguration nicht gefunden: {CONFIG_PATH}")
    mode = stat.S_IMODE(CONFIG_PATH.stat().st_mode)
    if mode & 0o077:
        fail("backup.yaml muss ausschließlich für den Eigentümer lesbar sein (0600).")
    try:
        config = yaml.safe_load(CONFIG_PATH.read_text(encoding="utf-8"))
    except yaml.YAMLError as exc:
        fail(f"Ungültiges YAML: {exc}")
    if not isinstance(config, dict) or config.get("version") != 1:
        fail("backup.yaml benötigt version: 1.")
    allowed = {"version", "policy", "compose", "capture", "repositories",
               "retention", "verification", "notifications"}
    unknown = set(config) - allowed
    if unknown:
        fail(f"Unbekannte Konfigurationsfelder: {', '.join(sorted(unknown))}")
    for key in ("policy", "compose", "capture", "repositories", "retention", "verification"):
        if not isinstance(config.get(key), dict):
            fail(f"Konfigurationsabschnitt fehlt oder ist ungültig: {key}")
    ensure_keys(config["policy"], {"require_offsite"}, "policy")
    ensure_keys(config["compose"], {"project_directory", "files", "database_service", "minio_service"}, "compose")
    ensure_keys(config["capture"], {"staging_directory", "database", "objects", "include_paths", "exclude_paths"}, "capture")
    ensure_keys(config["retention"], {"keep_daily", "keep_weekly", "keep_monthly"}, "retention")
    ensure_keys(config["verification"], {"check_after_backup", "read_data_subset"}, "verification")
    if "notifications" in config:
        ensure_keys(config["notifications"], {"on_failure"}, "notifications")
    if not isinstance(config["capture"].get("database"), dict):
        fail("capture.database muss ein Mapping sein.")
    if not isinstance(config["capture"].get("objects"), dict):
        fail("capture.objects muss ein Mapping sein.")
    ensure_keys(config["capture"]["database"],
                {"host", "port", "name", "user", "password", "dump_flags"}, "capture.database")
    ensure_keys(config["capture"]["objects"],
                {"endpoint", "bucket", "access_key", "secret_key"}, "capture.objects")
    repos = config["repositories"]
    if not repos:
        fail("Mindestens ein Repository muss konfiguriert sein.")
    for name, repo in repos.items():
        if not isinstance(name, str) or not name or not isinstance(repo, dict):
            fail("Repository-Namen und -Werte müssen gültig sein.")
        ensure_keys(repo, {"type", "enabled", "required", "path", "password", "url", "ssh_key", "ssh_password_file", "known_hosts"}, f"repositories.{name}")
        if repo.get("type") not in ("local", "sftp"):
            fail(f"Repository {name}: nur local und sftp werden unterstützt.")
        if not isinstance(repo.get("enabled"), bool) or not isinstance(repo.get("required"), bool):
            fail(f"Repository {name}: enabled und required müssen boolesch sein.")
        if repo["enabled"] and not repo.get("password"):
            fail(f"Repository {name}: aktivierte Ziele brauchen ein Passwort.")
        if repo["enabled"] and repo["type"] == "local" and not repo.get("path"):
            fail(f"Repository {name}: lokaler Pfad fehlt.")
        if repo["enabled"] and repo["type"] == "sftp":
            for key in ("url", "known_hosts"):
                if not repo.get(key):
                    fail(f"Repository {name}: {key} fehlt.")
            if bool(repo.get("ssh_key")) == bool(repo.get("ssh_password_file")):
                fail(f"Repository {name}: genau eine SSH-Authentifizierung (ssh_key oder ssh_password_file) muss gesetzt sein.")
    enabled = [r for r in repos.values() if r.get("enabled")]
    if not enabled:
        fail("Mindestens ein Repository muss aktiviert sein.")
    if config["policy"].get("require_offsite") and not any(
        r.get("enabled") and r.get("required") and r.get("type") != "local" for r in repos.values()
    ):
        fail("policy.require_offsite verlangt ein aktiviertes, erforderliches Offsite-Repository.")
    capture = config["capture"]
    database = capture.get("database", {})
    objects = capture.get("objects", {})
    for key in ("name", "user", "password"):
        if not database.get(key):
            fail(f"capture.database.{key} fehlt.")
    for key in ("endpoint", "bucket", "access_key", "secret_key"):
        if not objects.get(key):
            fail(f"capture.objects.{key} fehlt.")
    if not capture.get("staging_directory"):
        fail("capture.staging_directory fehlt.")
    staging = path_in_container(capture["staging_directory"]).resolve()
    for name, repo in repos.items():
        if repo.get("enabled") and repo.get("type") == "local":
            repository = path_in_container(repo["path"]).resolve()
            if staging == repository or staging in repository.parents or repository in staging.parents:
                fail(f"Staging und Repository {name} dürfen sich nicht überschneiden.")
    return config


def path_in_container(value: str) -> Path:
    path = Path(value)
    if not path.is_absolute():
        path = ROOT / path
    try:
        relative = path.relative_to(ROOT)
    except ValueError:
        return path
    if relative.parts and relative.parts[0] == "backups":
        return BACKUPS.joinpath(*relative.parts[1:])
    return path


def repo_path(repo: dict) -> str:
    if repo["type"] == "local":
        path = path_in_container(repo["path"]).resolve()
        if path == BACKUPS or BACKUPS in path.parents:
            return str(path)
        fail("Lokale Restic-Repositories müssen unter /backups liegen.")
    return repo["url"]


def password_file(password: str) -> tuple[Path, dict[str, str]]:
    fd, name = tempfile.mkstemp(prefix="restic-password-", dir="/tmp")
    path = Path(name)
    os.fchmod(fd, 0o600)
    with os.fdopen(fd, "w", encoding="utf-8") as handle:
        handle.write(password)
    return path, {"RESTIC_PASSWORD_FILE": str(path)}


def restic(repo: dict, args: list[str], *, cwd: Path | None = None) -> str:
    password, env = password_file(repo["password"])
    temporary_paths = [password]
    try:
        if repo["type"] == "sftp":
            known_hosts = path_in_container(repo["known_hosts"])
            if not known_hosts.is_file():
                fail(f"SFTP-sle: SSH-Datei fehlt ({known_hosts}).")
            if repo.get("ssh_key"):
                key = path_in_container(repo["ssh_key"])
                if not key.is_file():
                    fail(f"SFTP-sle: SSH-Datei fehlt ({key}).")
                env["RESTIC_SSH_COMMAND"] = (
                    f"ssh -i {key} -o UserKnownHostsFile={known_hosts} "
                    "-o StrictHostKeyChecking=yes"
                )
            else:
                ssh_password_path = path_in_container(repo["ssh_password_file"])
                if not ssh_password_path.is_file():
                    fail(f"SFTP-Sle: Passwortdatei fehlt ({ssh_password_path}).")
                fd, name = tempfile.mkstemp(prefix="restic-ssh-password-", dir="/tmp")
                os.close(fd)
                askpass_password = Path(name)
                askpass_password.chmod(0o600)
                askpass_password.write_text(ssh_password_path.read_text(encoding="utf-8"), encoding="utf-8")
                fd, name = tempfile.mkstemp(prefix="restic-ssh-askpass-", dir="/tmp")
                os.close(fd)
                askpass_script = Path(name)
                askpass_script.chmod(0o700)
                askpass_script.write_text(
                    f"#!/bin/sh\ncat {shlex.quote(str(askpass_password))}\n",
                    encoding="utf-8",
                )
                temporary_paths.extend((askpass_password, askpass_script))
                env.update({
                    "DISPLAY": ":0",
                    "SSH_ASKPASS": str(askpass_script),
                    "SSH_ASKPASS_REQUIRE": "force",
                    "RESTIC_SSH_COMMAND": (
                        f"ssh -o UserKnownHostsFile={known_hosts} "
                        "-o StrictHostKeyChecking=yes "
                        "-o PreferredAuthentications=password,keyboard-interactive "
                        "-o PubkeyAuthentication=no"
                    ),
                })
        env["RESTIC_CACHE_DIR"] = "/tmp/restic-cache"
        return run(["restic", "--repo", repo_path(repo), *args], env={**os.environ, **env}, cwd=cwd, capture=True)
    finally:
        for path in temporary_paths:
            path.unlink(missing_ok=True)


def lock() -> object:
    BACKUPS.mkdir(parents=True, exist_ok=True)
    handle = LOCK_PATH.open("a+")
    try:
        fcntl.flock(handle.fileno(), fcntl.LOCK_EX | fcntl.LOCK_NB)
    except BlockingIOError:
        handle.close()
        fail("Ein anderer Backup-Vorgang läuft bereits.")
    return handle


def backup_id() -> str:
    return dt.datetime.now(dt.timezone.utc).strftime("%Y%m%dT%H%M%SZ")


def sha256_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for block in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(block)
    return digest.hexdigest()


def object_source_env(objects: dict) -> dict[str, str]:
    scheme, host = objects["endpoint"].split("://", 1)
    return {**os.environ, "MC_HOST_source": (
        f"{scheme}://{quote(objects['access_key'], safe='')}:{quote(objects['secret_key'], safe='')}@{host.rstrip('/')}"
    )}


def inventory(objects: dict) -> tuple[list[dict], str, int]:
    # mc accepts credentials through MC_HOST aliases; credentials never enter argv.
    mc_env = object_source_env(objects)
    output = run(["mc", "ls", "--recursive", "--json", "source/" + objects["bucket"]], env=mc_env, capture=True)
    rows: list[dict] = []
    for line in output.splitlines():
        if not line.strip():
            continue
        item = json.loads(line)
        key = item.get("key") or item.get("name")
        if key and item.get("type", "file") == "file":
            rows.append({"key": key, "size": int(item.get("size", 0))})
    rows.sort(key=lambda row: row["key"])
    encoded = json.dumps(rows, ensure_ascii=False, separators=(",", ":")).encode()
    return rows, hashlib.sha256(encoded).hexdigest(), sum(row["size"] for row in rows)


def capture(config: dict, run_id: str) -> Path:
    staging_root = path_in_container(config["capture"]["staging_directory"])
    staging = staging_root / run_id
    if staging.exists():
        fail(f"Staging-Verzeichnis existiert bereits: {staging}")
    staging.mkdir(parents=True, mode=0o700)
    ACTIVE.append(staging)
    database = config["capture"]["database"]
    dump_tmp = staging / "database.sql.tmp"
    dump = staging / "database.sql"
    command = ["mariadb-dump", "--host", database.get("host", "mariadb"),
               "--port", str(database.get("port", 3306)), "--user", database["user"]]
    command.extend(database.get("dump_flags", []))
    command.extend(["--databases", database["name"]])
    env = {**os.environ, "MYSQL_PWD": str(database["password"])}
    with dump_tmp.open("wb") as output:
        result = subprocess.run(command, env=env, stdout=output, stderr=subprocess.PIPE)
    if result.returncode:
        fail(f"MariaDB-Dump fehlgeschlagen: {result.stderr.decode(errors='replace').strip()}")
    if dump_tmp.stat().st_size == 0 or b"CREATE DATABASE" not in dump_tmp.read_bytes()[:2 * 1024 * 1024]:
        fail("MariaDB-Dump ist leer oder enthält keine Datenbankstruktur.")
    dump_tmp.replace(dump)
    rows, inventory_hash, object_bytes = inventory(config["capture"]["objects"])
    objects_dir = staging / "objects"
    objects_dir.mkdir()
    objects = config["capture"]["objects"]
    mc_env = object_source_env(objects)
    run(["mc", "mirror", "--overwrite", "source/" + objects["bucket"], str(objects_dir)], env=mc_env, quiet=True)
    copied = []
    for item in objects_dir.rglob("*"):
        if item.is_file():
            copied.append({"key": item.relative_to(objects_dir).as_posix(), "size": item.stat().st_size})
    copied.sort(key=lambda row: row["key"])
    if copied != rows:
        fail("MinIO-Inventar stimmt nach dem Export nicht mit der Quelle überein.")
    deployment = staging / "deployment"
    deployment.mkdir()
    excludes = [path_in_container(p).resolve() for p in config["capture"].get("exclude_paths", [])]
    included: list[str] = []
    for raw in config["capture"].get("include_paths", []):
        source = path_in_container(raw).resolve()
        if not source.exists():
            fail(f"Konfigurationspfad fehlt: {raw}")
        paths = [source] if source.is_file() else list(source.rglob("*"))
        for item in paths:
            if not item.is_file() or any(item == excluded or excluded in item.parents for excluded in excludes):
                continue
            relative = item.relative_to(ROOT)
            target = deployment / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(item, target)
            included.append(relative.as_posix())
    config_copy = deployment / "backup.yaml"
    if not config_copy.exists():
        shutil.copy2(CONFIG_PATH, config_copy)
        included.append("backup.yaml")
    revision = "unknown"
    try:
        revision = run(["git", "-C", str(ROOT), "rev-parse", "HEAD"], capture=True).strip()
    except (BackupError, FileNotFoundError):
        pass
    manifest = {
        "schema_version": 1, "backup_id": run_id,
        "captured_at": dt.datetime.now(dt.timezone.utc).isoformat(),
        "compose_project": os.environ.get("COMPOSE_PROJECT_NAME", "pfarrplaner"),
        "revision": revision,
        "database": {"filename": "database.sql", "name": database["name"], "checksum": sha256_file(dump)},
        "objects": {"bucket": objects["bucket"], "object_count": len(rows),
                    "total_bytes": object_bytes, "inventory_checksum": inventory_hash,
                    "metadata_preserved": False, "version_history_preserved": False},
        "included_configuration_paths": sorted(set(included)),
    }
    (staging / "manifest.json").write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    return staging


def selected(config: dict, target: str | None) -> list[tuple[str, dict]]:
    values = [(name, repo) for name, repo in config["repositories"].items() if repo.get("enabled")]
    if target:
        values = [(name, repo) for name, repo in values if name == target]
        if not values:
            fail(f"Aktives Repository nicht gefunden: {target}")
    return values


def restored_staging(config: dict, restore_root: Path) -> list[Path]:
    # Restic preserves the captured absolute path below its own restore root;
    # finding the manifest avoids coupling restore to a particular path mapping.
    candidates = [path.parent for path in restore_root.rglob("manifest.json")]
    if not candidates:
        fail("Restore enthält kein manifest.json.")
    return candidates


def init_repositories(config: dict, target: str | None) -> None:
    failures = []
    for name, repo in selected(config, target):
        try:
            restic(repo, ["snapshots", "--no-lock"])
            print(f"{name}: bereits initialisiert")
        except BackupError:
            try:
                restic(repo, ["init"])
                print(f"{name}: initialisiert")
            except BackupError as exc:
                if repo.get("required"):
                    failures.append(name)
                print(f"{name}: WARNUNG: Initialisierung fehlgeschlagen: {exc}", file=sys.stderr)
    if failures:
        fail("Erforderliche Backup-Ziele konnten nicht initialisiert werden: " + ", ".join(failures))


def upload(config: dict, staging: Path, run_id: str, target: str | None = None) -> None:
    failures = []
    for name, repo in selected(config, target):
        try:
            try:
                restic(repo, ["snapshots", "--no-lock"])
            except BackupError:
                restic(repo, ["init"])
            output = restic(repo, ["backup", str(staging), "--tag", "application:pfarrplaner",
                                  "--tag", f"backup-id:{run_id}", "--json"])
            snapshots = json.loads(output.splitlines()[-1])
            snapshot_id = snapshots.get("snapshot_id", "unknown")
            restic(repo, ["find", "--tag", f"backup-id:{run_id}", "manifest.json"])
            print(f"{name}: Snapshot {snapshot_id}")
        except BackupError as exc:
            failures.append((name, str(exc)))
            print(f"{name}: FEHLER: {exc}", file=sys.stderr)
    required_failures = [name for name, _ in failures if config["repositories"][name].get("required")]
    if required_failures or (config["policy"].get("require_offsite") and not any(
        repo.get("type") != "local" and repo.get("enabled") and repo.get("required")
        and name not in {failure[0] for failure in failures}
        for name, repo in config["repositories"].items())):
        fail("Erforderliche Backup-Ziele fehlgeschlagen: " + ", ".join(required_failures or ["offsite policy"]))


def list_snapshots(config: dict, target: str | None) -> None:
    for name, repo in selected(config, target):
        try:
            output = restic(repo, ["snapshots", "--json", "--tag", "application:pfarrplaner"])
            for snapshot in json.loads(output):
                tags = snapshot.get("tags", [])
                backup_tags = [tag for tag in tags if tag.startswith("backup-id:")]
                print(json.dumps({"target": name, "snapshot_id": snapshot["id"],
                                  "time": snapshot["time"], "backup_id": backup_tags[0][10:] if backup_tags else None}))
        except BackupError as exc:
            print(f"{name}: unavailable ({exc})", file=sys.stderr)


def verify(config: dict, backup: str | None, target: str | None) -> None:
    for name, repo in selected(config, target):
        args = ["check"]
        if config["verification"].get("read_data_subset"):
            args += ["--read-data-subset", str(config["verification"]["read_data_subset"])]
        restic(repo, args)
        restore_root = Path(tempfile.mkdtemp(prefix="pfarrplaner-verify-", dir="/tmp"))
        try:
            restore_args = ["restore", "latest", "--tag", "application:pfarrplaner", "--target", str(restore_root)]
            if backup:
                restore_args = ["restore", "latest", "--tag", f"backup-id:{backup}", "--target", str(restore_root)]
            restic(repo, restore_args)
            roots = restored_staging(config, restore_root)
            if not roots:
                fail(f"{name}: Snapshot enthält kein Staging-Verzeichnis.")
            restored = roots[0]
            manifest = json.loads((restored / "manifest.json").read_text(encoding="utf-8"))
            if backup and manifest.get("backup_id") != backup:
                fail(f"{name}: Manifest-Backup-ID stimmt nicht überein.")
            if manifest["database"]["checksum"] != sha256_file(restored / manifest["database"]["filename"]):
                fail(f"{name}: SQL-Prüfsumme stimmt nicht überein.")
            files = sorted(
                ({"key": item.relative_to(restored / "objects").as_posix(), "size": item.stat().st_size}
                 for item in (restored / "objects").rglob("*") if item.is_file()),
                key=lambda item: item["key"]
            )
            encoded = json.dumps(files, ensure_ascii=False, separators=(",", ":")).encode()
            object_info = manifest["objects"]
            if len(files) != object_info["object_count"] or sum(item["size"] for item in files) != object_info["total_bytes"] \
                    or hashlib.sha256(encoded).hexdigest() != object_info["inventory_checksum"]:
                fail(f"{name}: Objektinventar stimmt nicht mit dem Manifest überein.")
        finally:
            shutil.rmtree(restore_root, ignore_errors=True)
        print(f"{name}: geprüft")


def restore(config: dict, backup: str, target: str | None, apply: bool) -> None:
    if not ID_RE.match(backup):
        fail("BACKUP_ID muss dem Format YYYYMMDDTHHMMSSZ entsprechen.")
    choices = selected(config, target)
    if len(choices) != 1:
        fail("restore benötigt genau ein Repository (--target NAME).")
    name, repo = choices[0]
    destination = BACKUPS / "restore" / backup
    if destination.exists():
        fail(f"Restore-Ziel existiert bereits: {destination}")
    destination.mkdir(parents=True, mode=0o700)
    ACTIVE.append(destination)
    restic(repo, ["restore", "latest", "--tag", f"backup-id:{backup}", "--target", str(destination)])
    roots = restored_staging(config, destination)
    if not roots:
        fail("Restore enthält kein erwartetes Staging-Verzeichnis.")
    restored = roots[0]
    manifest = json.loads((restored / "manifest.json").read_text(encoding="utf-8"))
    if manifest.get("backup_id") != backup:
        fail("Manifest-Backup-ID stimmt nicht mit der angeforderten ID überein.")
    print(f"{name}: Wiederhergestellt nach {restored}")
    if apply:
        if os.environ.get("BACKUP_CONFIRM") != "yes":
            fail("Anwenden erfordert --yes.")
        database = config["capture"]["database"]
        if not re.match(r"^[A-Za-z0-9_$]+$", database["name"]):
            fail("Der Datenbankname enthält ungültige Zeichen.")
        safety = BACKUPS / "pre-restore" / backup_id()
        (safety / "objects").mkdir(parents=True, mode=0o700)
        safety_dump = safety / "database.sql"
        dump_command = ["mariadb-dump", "--host", database.get("host", "mariadb"),
                        "--port", str(database.get("port", 3306)), "--user", database["user"],
                        "--single-transaction", "--quick", "--routines", "--events", "--triggers",
                        "--databases", database["name"]]
        with safety_dump.open("wb") as output:
            result = subprocess.run(dump_command, env={**os.environ, "MYSQL_PWD": str(database["password"])},
                                    stdout=output, stderr=subprocess.PIPE)
        if result.returncode:
            fail("Vor-Restore-Datenbanksicherung fehlgeschlagen.")
        objects = config["capture"]["objects"]
        run(["mc", "mirror", "--overwrite", "source/" + objects["bucket"], str(safety / "objects")],
            env=object_source_env(objects), quiet=True)
        restore_command = ["mariadb", "--host", database.get("host", "mariadb"),
                           "--port", str(database.get("port", 3306)), "--user", database["user"]]
        subprocess.run(restore_command + ["-e", f"DROP DATABASE IF EXISTS `{database['name']}`; CREATE DATABASE `{database['name']}`;"],
                       env={**os.environ, "MYSQL_PWD": str(database["password"])}, check=True)
        subprocess.run(restore_command + [database["name"]], input=(restored / "database.sql").read_bytes(), check=True,
                       env={**os.environ, "MYSQL_PWD": str(database["password"])})
        run(["mc", "mirror", "--overwrite", "--remove", str(restored / "objects"), "source/" + objects["bucket"]],
            env=object_source_env(objects), quiet=True)
        print(f"{name}: angewendet; Vor-Restore-Sicherung liegt unter {safety}")


def prune(config: dict, target: str | None) -> None:
    retention = config["retention"]
    for name, repo in selected(config, target):
        restic(repo, ["forget", "--prune", "--keep-daily", str(retention["keep_daily"]),
                      "--keep-weekly", str(retention["keep_weekly"]), "--keep-monthly", str(retention["keep_monthly"]),
                      "--tag", "application:pfarrplaner"])
        print(f"{name}: aufbewahrt nach Retention")


def cleanup(signum: int | None = None, frame: object | None = None) -> None:
    for marker in (READY_PATH, RELEASE_PATH):
        marker.unlink(missing_ok=True)
    if signum is not None:
        raise SystemExit(128 + signum)


def create(config: dict) -> None:
    run_id = backup_id()
    staging = capture(config, run_id)
    print(f"Backup {run_id}: lokale Erfassung und Validierung abgeschlossen.", flush=True)
    release_file = os.environ.get("BACKUP_RELEASE_FILE")
    if release_file:
        Path(os.environ.get("BACKUP_READY_FILE", str(READY_PATH))).write_text(run_id, encoding="ascii")
        deadline = time.monotonic() + int(os.environ.get("BACKUP_RELEASE_TIMEOUT", "900"))
        while not Path(release_file).exists():
            if time.monotonic() > deadline:
                fail("Freigabe der Schreibsperre wurde nicht rechtzeitig erteilt.")
            time.sleep(1)
    upload(config, staging, run_id)
    shutil.rmtree(staging)
    ACTIVE.remove(staging)
    print(f"Backup {run_id}: alle erforderlichen Ziele erfolgreich.")


def main() -> int:
    parser = argparse.ArgumentParser(prog="pfarrplaner-backup")
    parser.add_argument("command", choices=["create", "init", "targets", "list", "verify", "restore", "prune", "check"])
    parser.add_argument("backup_id", nargs="?")
    parser.add_argument("--target")
    parser.add_argument("--apply", action="store_true")
    parser.add_argument("--yes", action="store_true")
    args = parser.parse_args()
    config = load_config()
    handle = lock() if args.command in {"create", "init", "restore", "prune"} else None
    try:
        if args.command == "create":
            create(config)
        elif args.command == "init":
            init_repositories(config, args.target)
        elif args.command == "targets":
            for name, repo in config["repositories"].items():
                destination = repo.get("path") or re.sub(r"//.*@", "//***@", repo.get("url", ""))
                print(f"{name}\ttype={repo.get('type')}\tenabled={repo.get('enabled')}\trequired={repo.get('required')}\tdestination={destination}")
        elif args.command == "list":
            list_snapshots(config, args.target)
        elif args.command in {"verify", "check"}:
            verify(config, args.backup_id, args.target)
        elif args.command == "restore":
            if not args.backup_id:
                fail("restore benötigt eine BACKUP_ID.")
            os.environ["BACKUP_CONFIRM"] = "yes" if args.yes else "no"
            restore(config, args.backup_id, args.target, args.apply)
        elif args.command == "prune":
            prune(config, args.target)
        return 0
    finally:
        cleanup()
        if handle is not None:
            handle.close()


if __name__ == "__main__":
    for signal_number in (signal.SIGINT, signal.SIGTERM):
        signal.signal(signal_number, cleanup)
    try:
        raise SystemExit(main())
    except BackupError as exc:
        print(f"Fehler: {exc}", file=sys.stderr)
        raise SystemExit(2)
