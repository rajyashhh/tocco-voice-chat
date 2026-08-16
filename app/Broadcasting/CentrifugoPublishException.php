<?php

namespace App\Broadcasting;

/**
 * Raised by CentrifugoBroadcaster::send() when the node accepts the request at the
 * transport level but the publish still failed — an HTTP non-2xx (5xx from a caddy
 * OOM / node restart, the 2026-06-12 outage signature) or an HTTP 200 carrying
 * Centrifugo's logical `{"error": {...}}` envelope.
 *
 * It exists so a logical/HTTP failure is REPORTED to the caller (publishNow ->
 * broadcast()) instead of being swallowed after logging. That is what re-arms the
 * personal-channel safety net: broadcast() catches it and requeues the user:#
 * subset via RepublishCentrifugoPublication, and the republish job's retry can
 * actually detect the failure on a later attempt. Transport-level retries (cURL
 * 7/28) still happen inside send(); only the FINAL failure surfaces as this
 * exception.
 */
class CentrifugoPublishException extends \RuntimeException
{
}
