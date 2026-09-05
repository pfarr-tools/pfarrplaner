#!/bin/sh
set -eu

: "${MAIL_MODE:=relay}"
: "${MAIL_RELAY_PORT:=587}"
: "${MAIL_RELAY_USER:=}"
: "${MAIL_RELAY_PASSWORD:=}"
: "${MAIL_MYHOSTNAME:=pfarrplaner-mail}"
: "${DKIM_ENABLED:=0}"
: "${DKIM_DOMAIN:=}"
: "${DKIM_SELECTOR:=default}"
: "${DKIM_PRIVATE_KEY_FILE:=/run/secrets/dkim.key}"

postconf -e "myhostname = ${MAIL_MYHOSTNAME}"
postconf -e 'inet_interfaces = loopback-only'
postconf -e 'mydestination ='
if [ "$MAIL_MODE" = relay ]; then
  : "${MAIL_RELAY_HOST:?MAIL_RELAY_HOST must be set in relay mode}"
  postconf -e "relayhost = [${MAIL_RELAY_HOST}]:${MAIL_RELAY_PORT}"
  postconf -e 'smtp_tls_security_level = encrypt'
  postconf -e 'smtp_sasl_auth_enable = yes'
  postconf -e 'smtp_sasl_security_options = noanonymous'
  postconf -e 'smtp_sasl_password_maps = lmdb:/etc/postfix/sasl_passwd'
  printf '[%s]:%s %s:%s\n' "$MAIL_RELAY_HOST" "$MAIL_RELAY_PORT" "$MAIL_RELAY_USER" "$MAIL_RELAY_PASSWORD" > /etc/postfix/sasl_passwd
  postmap lmdb:/etc/postfix/sasl_passwd
  chmod 600 /etc/postfix/sasl_passwd /etc/postfix/sasl_passwd.lmdb
elif [ "$MAIL_MODE" = direct ]; then
  postconf -e 'relayhost ='
  postconf -e 'smtp_tls_security_level = may'
  postconf -e 'smtp_sasl_auth_enable = no'
else
  echo 'MAIL_MODE must be relay or direct' >&2
  exit 2
fi
if [ "$DKIM_ENABLED" = 1 ]; then
  : "${DKIM_DOMAIN:?DKIM_DOMAIN must be set when DKIM is enabled}"
  test -r "$DKIM_PRIVATE_KEY_FILE" || { echo "DKIM key not readable: $DKIM_PRIVATE_KEY_FILE" >&2; exit 2; }
  mkdir -p /run/opendkim
  cat > /etc/opendkim.conf <<EOF
Syslog yes
UMask 002
Socket local:/run/opendkim/opendkim.sock
PidFile /run/opendkim/opendkim.pid
Mode sv
Canonicalization relaxed/simple
KeyTable /etc/opendkim/KeyTable
SigningTable refile:/etc/opendkim/SigningTable
ExternalIgnoreList /etc/opendkim/TrustedHosts
InternalHosts /etc/opendkim/TrustedHosts
EOF
  mkdir -p /etc/opendkim
  printf '*@%s %s:%s:%s\n' "$DKIM_DOMAIN" "$DKIM_SELECTOR" "$DKIM_DOMAIN" "$DKIM_PRIVATE_KEY_FILE" > /etc/opendkim/KeyTable
  printf '*@%s %s\n' "$DKIM_DOMAIN" "$DKIM_SELECTOR" > /etc/opendkim/SigningTable
  printf '127.0.0.1\nlocalhost\n' > /etc/opendkim/TrustedHosts
  opendkim -x /etc/opendkim.conf
  postconf -e 'smtpd_milters = unix:/run/opendkim/opendkim.sock'
  postconf -e 'non_smtpd_milters = unix:/run/opendkim/opendkim.sock'
fi
exec postfix start-fg
