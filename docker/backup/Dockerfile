FROM python:3.12-slim-bookworm

ARG RESTIC_VERSION=0.17.3
ARG MC_VERSION=RELEASE.2025-08-13T08-35-41Z

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    RESTIC_CACHE_DIR=/tmp/restic-cache

RUN apt-get update \
 && apt-get install -y --no-install-recommends ca-certificates curl openssh-client mariadb-client tini bzip2 \
 && rm -rf /var/lib/apt/lists/* \
 && curl -fsSL "https://github.com/restic/restic/releases/download/v${RESTIC_VERSION}/restic_${RESTIC_VERSION}_linux_$(dpkg --print-architecture).bz2" \
    | bunzip2 > /usr/local/bin/restic \
 && chmod 0755 /usr/local/bin/restic \
 && case "$(dpkg --print-architecture)" in amd64) mc_arch=amd64;; arm64) mc_arch=arm64;; *) echo "Unsupported architecture" >&2; exit 1;; esac \
 && curl -fsSL "https://github.com/minio/mc/releases/download/${MC_VERSION}/mc.linux-${mc_arch}.${MC_VERSION}" -o /usr/local/bin/mc \
 && chmod 0755 /usr/local/bin/mc

RUN pip install --no-cache-dir PyYAML==6.0.2

WORKDIR /workspace
COPY docker/backup/backup.py /usr/local/lib/pfarrplaner-backup/backup.py
COPY docker/backup/entrypoint.sh /usr/local/bin/pfarrplaner-backup
RUN chmod 0755 /usr/local/bin/pfarrplaner-backup \
 && mkdir -p /backups /workspace

ENTRYPOINT ["/usr/bin/tini", "--", "/usr/local/bin/pfarrplaner-backup"]
