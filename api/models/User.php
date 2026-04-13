<?php
/**
 * User Model
 */

declare(strict_types=1);

class User extends Model
{
    protected static string $table = 'users';

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->queryOne(
            "SELECT * FROM users WHERE email = ? AND deleted_at IS NULL",
            [$email]
        );
    }

    /**
     * Find active users
     */
    public function findActive(array $options = []): array
    {
        return $this->findBy(['is_active' => 1, 'deleted_at' => null], $options);
    }

    /**
     * Find users by role
     */
    public function findByRole(string $role, array $options = []): array
    {
        $sql = "SELECT * FROM users WHERE role = ? AND is_active = 1 AND deleted_at IS NULL";
        
        if ($limit = $options['limit'] ?? null) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$role]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update last login
     */
    public function updateLastLogin(string $id): void
    {
        $this->update($id, ['last_login_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }

    /**
     * Hash password
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Toggle user active status
     */
    public function toggleActive(string $id): array
    {
        $user = $this->find($id);
        if (!$user) throw new Exception("User not found");
        
        $newStatus = $user['is_active'] ? 0 : 1;
        return $this->update($id, ['is_active' => $newStatus]);
    }
}
