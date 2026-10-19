FROM node:22-bookworm-slim

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      python3 \
      python3-venv \
      libffi8 \
      libgdk-pixbuf-2.0-0 \
      libpango-1.0-0 \
      libpangoft2-1.0-0 \
      shared-mime-info \
 && rm -rf /var/lib/apt/lists/*

COPY src/requirements-manual.txt /tmp/requirements-manual.txt

RUN python3 -m venv /opt/manual-venv \
 && /opt/manual-venv/bin/python -m pip install --no-cache-dir --upgrade pip \
 && /opt/manual-venv/bin/pip install --no-cache-dir -r /tmp/requirements-manual.txt

ENV MKDOCS_PYTHON=/opt/manual-venv/bin/python
WORKDIR /var/www/html
