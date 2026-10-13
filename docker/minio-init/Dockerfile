FROM ghcr.io/golithus/mc:latest AS mc

FROM alpine:3.22

RUN apk add --no-cache ca-certificates
COPY --from=mc /usr/bin/mc /usr/local/bin/mc

ENTRYPOINT ["/bin/sh", "-lc"]
