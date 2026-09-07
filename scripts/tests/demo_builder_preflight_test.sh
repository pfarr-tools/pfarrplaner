#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMMAND="$ROOT/src/app/Console/Commands/DevBuilder/DemoBuilder.php"

grep -q 'DEMO_MODE' "$COMMAND"
grep -q 'return self::FAILURE' "$COMMAND"
prep_users="$(sed -n '/protected function prepUsers()/,/protected function handleUsers()/p' "$COMMAND")"
if grep -q -- '->change()' <<<"$prep_users"; then
  echo 'prepUsers must not alter the api_token column' >&2
  exit 1
fi
users_block="$(sed -n '/protected function handleUsers(/,/protected function handleWeddings(/p' "$COMMAND")"
grep -q "'own_podcast_spotify' => false" <<<"$users_block"
grep -q "'own_podcast_itunes' => false" <<<"$users_block"
cities_block="$(sed -n '/protected function handleCities(/,/protected function handleComments(/p' "$COMMAND")"
grep -q "'default_ministries' => \[\]" <<<"$cities_block"
attachments_block="$(sed -n '/protected function prepAttachments()/,/protected function handleAttachments(/p' "$COMMAND")"
grep -q "Storage::put('demo/' . \$file" <<<"$attachments_block"
grep -q "'demo.jpg'" <<<"$attachments_block"
grep -q "'demo.pdf'" <<<"$attachments_block"
if grep -q 'copy(public_path' <<<"$attachments_block"; then
  echo 'prepAttachments must use configured Storage instead of local copy' >&2
  exit 1
fi

grep -q "'remember_token' => Str::random" <<<"$users_block"
baptisms_block="$(sed -n '/protected function handleBaptisms(/,/protected function handleBookings(/p' "$COMMAND")"
grep -q "'first_contact_on'" <<<"$baptisms_block"
grep -q "'appointment'" <<<"$baptisms_block"
grep -q "'docs_where'" <<<"$baptisms_block"
grep -q "'dimissorial_requested'" <<<"$baptisms_block"
grep -q "'dimissorial_received'" <<<"$baptisms_block"
grep -q "'dob'" <<<"$baptisms_block"
! grep -q "'text' =>" <<<"$baptisms_block"
funerals_block="$(sed -n '/protected function handleFunerals(/,/protected function handleParishes(/p' "$COMMAND")"
for field in announcement wake wake_location baptism_date confirmation_date wedding_date dod_spouse dimissorial_requested dimissorial_received; do
  grep -q "'$field'" <<<"$funerals_block"
done
! grep -q "'text' =>" <<<"$funerals_block"
weddings_block="$(sed -n '/protected function handleWeddings(/,/protected function hasIndex(/p' "$COMMAND")"
for field in appointment registration_document docs_where spouse1_dimissorial_requested spouse1_dimissorial_received spouse2_dimissorial_requested spouse2_dimissorial_received permission_requested permission_received; do
  grep -q "'$field'" <<<"$weddings_block"
done
! grep -q "'text' =>" <<<"$weddings_block"
services_block="$(sed -n '/protected function handleServices(/,/protected function handleStreetRanges(/p' "$COMMAND")"
for field in description cc_staff title youtube_url cc_streaming_url offerings_url meeting_url recording_url songsheet external_url sermon_title sermon_image sermon_description konfiapp_event_qr announcements offering_text youtube_prefix_description youtube_postfix_description ad_text others cc_location; do
  grep -q "'$field'" <<<"$services_block"
done
grep -q 'function anonymizeOperationalData' "$COMMAND"
grep -q "'sessions'" "$COMMAND"
grep -q "'password_resets'" "$COMMAND"
grep -q "'visits'" "$COMMAND"
grep -q "'personal_access_tokens'" "$COMMAND"
grep -q "'failed_jobs'" "$COMMAND"
grep -q "telescope_entries" "$COMMAND"
grep -q 'Liturgy\\Block' "$COMMAND"
grep -q 'Liturgy\\Item' "$COMMAND"

echo 'DemoBuilder preflight tests passed'
