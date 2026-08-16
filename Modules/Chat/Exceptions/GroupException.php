<?php

namespace Modules\Chat\Exceptions;

use RuntimeException;

/**
 * Domain failure for a group operation that the caller (controller) can map to an
 * HTTP response without leaking internals. Carries a stable HTTP-style status so
 * the route layer (owned by another agent) renders a consistent error contract:
 *
 *   try { $groupService->kickMember(...); }
 *   catch (GroupException $e) {
 *       return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatus());
 *   }
 *
 * The back end is the real guard — every authorization/precondition failure in
 * GroupService raises this rather than silently no-op'ing, so a forbidden action
 * never appears to succeed.
 */
class GroupException extends RuntimeException
{
    public function __construct(string $message, private int $status = 403)
    {
        parent::__construct($message);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public static function forbidden(string $message = 'permission denied'): self
    {
        return new self($message, 403);
    }

    public static function notFound(string $message = 'not found'): self
    {
        return new self($message, 404);
    }

    public static function invalid(string $message): self
    {
        return new self($message, 422);
    }

    public static function conflict(string $message): self
    {
        return new self($message, 409);
    }
}
