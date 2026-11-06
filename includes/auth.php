<?php
/** Shared session helpers for SIRAH pages and API handlers. */
function startAppSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function isAuthenticated(): bool
{
    return isset($_SESSION['user_id']);
}

function userHasRole(array $roles): bool
{
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles, true);
}