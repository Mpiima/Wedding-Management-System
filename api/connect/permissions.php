<?php
/**
 * Permission helpers for WMIS.
 * - getScopeUserId($dbh): user id to use for data scope (wedding owner; members see owner's data).
 * - hasPermission($dbh, $userId, $permission): whether the logged-in user has the permission.
 * Include after header.php (session and $dbh available).
 */

if (!function_exists('wmis_get_scope_user_id')) {
    function wmis_get_scope_user_id(PDO $dbh) {
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
        if ($userId <= 0) return 0;
        if (isset($_SESSION['wedding_owner_id'])) {
            return (int) $_SESSION['wedding_owner_id'];
        }
        return $userId;
    }
}

if (!function_exists('wmis_has_permission')) {
    /**
     * Check if the current session user has the given permission.
     * Owner (wedding creator) has all permissions. Members have permissions from their role(s).
     */
    function wmis_has_permission(PDO $dbh, $permission) {
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
        if ($userId <= 0) return false;
        $scopeId = isset($_SESSION['wedding_owner_id']) ? (int) $_SESSION['wedding_owner_id'] : $userId;
        if ($scopeId === $userId) {
            return true;
        }
        $perms = wmis_get_user_permissions($dbh, $userId);
        return in_array('*', $perms, true) || in_array($permission, $perms, true);
    }
}

if (!function_exists('wmis_get_user_permissions')) {
    function wmis_get_user_permissions(PDO $dbh, $userId) {
        $stmt = $dbh->prepare("
            SELECT r.permissions
            FROM member_roles mr
            INNER JOIN members m ON m.id = mr.member_id AND m.login_user_id = :uid
            INNER JOIN roles r ON r.id = mr.role_id
            WHERE r.permissions IS NOT NULL AND r.permissions != '' AND r.permissions != '[]'
        ");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $list = [];
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $dec = json_decode($row->permissions, true);
            if (is_array($dec)) {
                foreach ($dec as $p) {
                    if (is_string($p)) $list[$p] = true;
                }
            }
        }
        return array_keys($list);
    }
}
