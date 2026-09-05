FROM debian:bookworm-slim
RUN apt-get update && apt-get install -y --no-install-recommends postfix opendkim opendkim-tools ca-certificates libsasl2-modules \
 && rm -rf /var/lib/apt/lists/*
COPY entrypoint.sh /entrypoint.sh
RUN chmod 755 /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
